<?php

namespace App\Console\Commands;

use App\EzeeAssignmentLog;
use App\EzeeGroup;
use App\OtherModel\EzeeBooking;
use App\Support\EzeeAutoAssign;
use App\Support\EzeeUnitMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read EZEE's notification queue: the one feed that says a reservation was
 * cancelled. The date-range pull never does; a cancelled reservation simply
 * stops appearing, which is indistinguishable from a short pull, and acting
 * on absence once retired 85 live stays.
 *
 * Every event is stored before the batch is acknowledged, so nothing is
 * lost. A cancellation is applied only when EZEE says "Cancel" by name: the
 * reservation is retired and its live booking cancelled, with the reason and
 * time EZEE gave. Events older than the recent window, and any whose booking
 * has been touched by a person since, are raised for review instead of
 * applied, because the booking may have been reused. New and modified
 * reservations are recorded and left to the sync, which already refreshes
 * them by property; the reconcile then assigns them.
 */
class EzeeNotifications extends Command
{
    protected $signature = 'ezee:notifications
                            {--hotel= : one hotel code}
                            {--dry-run : pull and show, store nothing, acknowledge nothing}
                            {--no-ack : store and apply but leave the queue unacknowledged}
                            {--auto-cancel-days=14 : cancellations newer than this are applied; older ones go to review}';

    protected $description = 'Read the EZEE notification queue and apply cancellations EZEE reports explicitly';

    public function handle(): int
    {
        $dry  = (bool) $this->option('dry-run');
        $days = max(0, (int) $this->option('auto-cancel-days'));
        $groups = EzeeGroup::when($this->option('hotel'), fn ($q, $h) => $q->where('hotel_code', $h))->get();

        foreach ($groups as $g) {
            $res = $this->pull($g->hotel_code, $g->auth_key);
            if ($res === null) {
                $this->error("{$g->hotel_code} {$g->name}: pull failed, queue left as is");
                continue;
            }

            $events = []; $ack = [];
            foreach ($res as $block) {
                if (!is_array($block)) {
                    continue;
                }
                foreach ($block['CancelReservation'] ?? [] as $c) {
                    $events[] = ['status' => 'Cancel', 'res' => (string) ($c['UniqueID'] ?? ''), 'at' => $c['Canceldatetime'] ?? null, 'remark' => $c['Remark'] ?? null, 'voucher' => $c['VoucherNo'] ?? null, 'payload' => $c];
                }
                foreach ($block['Reservation'] ?? [] as $r) {
                    foreach ($r['BookingTran'] ?? [] as $t) {
                        $events[] = ['status' => (string) ($t['Status'] ?? 'New'), 'res' => (string) ($t['SubBookingId'] ?? ''), 'at' => $t['Modifydatetime'] ?? ($t['Createdatetime'] ?? null), 'remark' => null, 'voucher' => $t['VoucherNo'] ?? null, 'payload' => $t];
                    }
                }
            }

            $counts = ['Cancel' => 0, 'New' => 0, 'Modify' => 0];
            foreach ($events as $ev) {
                $counts[$ev['status']] = ($counts[$ev['status']] ?? 0) + 1;
            }
            $this->info(sprintf('%s %-16s cancellations %d, new %d, modified %d%s', $g->hotel_code, $g->name, $counts['Cancel'], $counts['New'], $counts['Modify'], $dry ? ' (dry run)' : ''));

            foreach ($events as $ev) {
                $note = $this->apply($g->hotel_code, $ev, $days, $dry);
                if ($ev['status'] === 'Cancel' || $this->getOutput()->isVerbose()) {
                    $this->line(sprintf('   %-7s %-12s %s  %s', $ev['status'], $ev['res'], substr((string) $ev['at'], 0, 16), $note));
                }
                // EZEE identifies the booking by its transaction number, not the RES
                // number; a cancellation carries only the RES, so the number comes
                // from our own row.
                $tid = $ev['payload']['TransactionId'] ?? EzeeBooking::where('TransactionId', 'like', $g->hotel_code . '%')
                    ->where('SubBookingId', $ev['res'])->value('TransactionId');
                if ($tid) {
                    $ack[] = ['BookingId' => (string) $tid, 'PMS_BookingId' => $ev['res'], 'Status' => $ev['status']];
                }
            }

            if ($ack && !$dry && !$this->option('no-ack')) {
                $ok = $this->acknowledge($g->hotel_code, $g->auth_key, $ack);
                if ($ok) {
                    DB::table('ezee_notifications')->where('hotel_code', $g->hotel_code)->whereNull('acknowledged_at')->update(['acknowledged_at' => now()]);
                }
                $this->line('   acknowledged ' . count($ack) . ' event(s)' . ($ok ? '' : ' FAILED; they will be delivered again'));
            }
        }

        return 0;
    }

    private function apply(string $hotel, array $ev, int $days, bool $dry): string
    {
        $base = preg_replace('/-\d+$/', '', $ev['res']);
        $rows = EzeeBooking::where('TransactionId', 'like', $hotel . '%')
            ->where(fn ($q) => $q->where('SubBookingId', $ev['res'])->orWhere('SubBookingId', $base)->orWhere('SubBookingId', 'like', $base . '-%'))
            ->get();

        if ($ev['status'] !== 'Cancel') {
            $t = $ev['payload'];
            if (empty($t['TransactionId'])) {
                $this->store($hotel, $ev, $dry, 'ignored', 'no transaction id in payload');

                return 'no transaction id; ignored';
            }
            $fields = [
                'SubBookingId' => $ev['res'], 'IsConfirmed' => $t['IsConfirmed'] ?? null, 'RateplanName' => $t['RateplanName'] ?? null, 'RoomTypeName' => $t['RoomTypeName'] ?? null,
                'RoomName' => $t['eZeePMSRoomid'] ?? ($t['RoomName'] ?? null), 'Start' => $t['Start'] ?? null, 'End' => $t['End'] ?? null, 'CurrencyCode' => $t['CurrencyCode'] ?? null,
                'TotalAmountAfterTax' => $t['TotalAmountAfterTax'] ?? null, 'TotalAmountBeforeTax' => $t['TotalAmountBeforeTax'] ?? null, 'TotalDiscount' => $t['TotalDiscount'] ?? null,
                'TotalExtraCharge' => $t['TotalExtraCharge'] ?? null, 'TotalPayment' => $t['TotalPayment'] ?? null, 'TACommision' => $t['TACommision'] ?? null,
                'FirstName' => $t['FirstName'] ?? null, 'LastName' => $t['LastName'] ?? null, 'Mobile' => $t['Mobile'] ?? null, 'Email' => $t['Email'] ?? null,
                'Country' => $t['Country'] ?? null, 'Source' => $t['Source'] ?? null, 'VoucherNo' => $t['VoucherNo'] ?? null,
                'ezee_status' => $ev['status'], 'ezee_current_status' => $t['CurrentStatus'] ?? null, 'ezee_modified_at' => $t['Modifydatetime'] ?? null,
            ];
            $existing = EzeeBooking::where('TransactionId', $t['TransactionId'])->first();
            if ($existing) {
                // Refresh dates, room and amounts; empty values never overwrite.
                if (!$dry) {
                    $existing->fill(array_filter($fields, fn ($v) => $v !== null && $v !== ''))->save();
                }
                $note = "refreshed {$existing->SubBookingId} (row {$existing->id})";
                $this->store($hotel, $ev, $dry, 'refreshed', $note);

                return $note;
            }
            if (!$dry) {
                EzeeBooking::create(array_merge($fields, ['TransactionId' => $t['TransactionId'], 'status' => 5]));
            }
            $note = "created {$ev['res']} " . ($fields['RoomName'] ?? '(no room)') . " {$fields['Start']}..{$fields['End']}; the reconcile assigns it";
            $this->store($hotel, $ev, $dry, 'created', $note);

            return $note;
        }

        if ($rows->isEmpty()) {
            $this->store($hotel, $ev, $dry, 'ignored', 'never reached MOKA');

            return 'not in MOKA; nothing to cancel';
        }

        $recent = $ev['at'] && strtotime($ev['at']) >= strtotime("-{$days} days");
        $done = [];
        foreach ($rows as $row) {
            if ((int) $row->status === 1) {
                $done[] = "{$row->SubBookingId} already retired";
                continue;
            }
            $booking = $row->book_id ? DB::table('bookings')->where('id', $row->book_id)->first() : null;
            $touched = $booking && $booking->updated_at && $ev['at'] && strtotime($booking->updated_at) > strtotime($ev['at']);

            if ($booking && (int) $booking->status !== 1 && (!$recent || $touched)) {
                // Old, or someone has worked on the booking since: a person decides.
                if (!$dry) {
                    $listing = EzeeUnitMap::make()->resolve($row);
                    EzeeAssignmentLog::create([
                        'ezee_booking_id' => $row->id, 'listing_id' => $booking->listing_id, 'old_listing_id' => null, 'assigned_by' => null, 'method' => 'conflict',
                        'note' => sprintf('EZEE cancelled %s on %s (%s), but booking #%d (%s to %s) is still live%s. Cancel it, or Mark done if the booking was reused.',
                            $row->SubBookingId, substr((string) $ev['at'], 0, 16), $ev['remark'] ?: 'no reason given', $booking->id, $booking->check_in, $booking->check_out,
                            $touched ? ' and was edited after the cancellation' : ' and the cancellation is older than the automatic window'),
                    ]);
                }
                $done[] = "{$row->SubBookingId} -> review (booking #{$booking->id} live)";
                continue;
            }

            if (!$dry) {
                EzeeBooking::where('id', $row->id)->update(['status' => 1]);
                if ($booking && (int) $booking->status !== 1) {
                    DB::table('bookings')->where('id', $booking->id)->update([
                        'status' => 1,
                        'remark' => DB::raw("LEFT(CONCAT(IFNULL(remark,''), ' | cancelled in EZEE " . substr((string) $ev['at'], 0, 16) . ": " . addslashes((string) ($ev['remark'] ?: '')) . "'), 255)"),
                        'updated_at' => now(),
                    ]);
                    if ($listing = EzeeUnitMap::make()->resolve($row)) {
                        EzeeAssignmentLog::create(['ezee_booking_id' => $row->id, 'listing_id' => $listing->id, 'old_listing_id' => null, 'assigned_by' => null, 'method' => 'cancelled',
                            'note' => sprintf('Cancelled in EZEE on %s: %s. Booking #%d cancelled.', substr((string) $ev['at'], 0, 16), $ev['remark'] ?: 'no reason given', $booking->id)]);
                    }
                }
                EzeeAssignmentLog::where('ezee_booking_id', $row->id)->where('method', 'conflict')->whereNull('resolved_at')
                    ->update(['resolved_at' => now(), 'resolved_by' => null, 'resolution_note' => 'Cancelled in EZEE on ' . substr((string) $ev['at'], 0, 16) . ' (' . ($ev['remark'] ?: 'no reason given') . ')']);
            }
            $done[] = "{$row->SubBookingId} retired" . ($booking && (int) $booking->status !== 1 ? ", booking #{$booking->id} cancelled" : '');
        }

        $note = implode('; ', $done);
        $this->store($hotel, $ev, $dry, str_contains($note, 'review') ? 'review' : (str_contains($note, 'cancelled') ? 'cancelled' : 'retired'), $note);

        return $note;
    }

    private function store(string $hotel, array $ev, bool $dry, string $applied, string $note): void
    {
        if ($dry) {
            return;
        }
        DB::table('ezee_notifications')->updateOrInsert(
            ['hotel_code' => $hotel, 'res_no' => $ev['res'], 'status' => $ev['status'], 'event_at' => $ev['at'] ? date('Y-m-d H:i:s', strtotime($ev['at'])) : null],
            ['remark' => $ev['remark'] ? substr((string) $ev['remark'], 0, 255) : null, 'voucher_no' => $ev['voucher'] ? substr((string) $ev['voucher'], 0, 100) : null,
             'payload' => json_encode($ev['payload']), 'applied' => $applied, 'applied_note' => $note, 'received_at' => now(), 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function pull(string $hotel, string $auth): ?array
    {
        $out = $this->post(['RES_Request' => ['Request_Type' => 'Bookings', 'Authentication' => ['HotelCode' => $hotel, 'AuthCode' => $auth]]]);
        $res = $out === null ? null : json_decode($out, true);

        return is_array($res) ? $res : null;
    }

    private function acknowledge(string $hotel, string $auth, array $ack): bool
    {
        $out = $this->post(['RES_Request' => ['Request_Type' => 'BookingRecdNotification', 'Authentication' => ['HotelCode' => $hotel, 'AuthCode' => $auth], 'Bookings' => ['Booking' => $ack]]]);

        // Kept so the identifier EZEE expects can be settled from real responses.
        \App\DataLog::create(['title' => 'ezee-ack', 'related_id' => $hotel, 'status' => $out !== null && stripos($out, '"ErrorCode"') === false ? 'ok' : 'failed',
            'data' => substr(json_encode(['sent' => array_slice($ack, 0, 3), 'count' => count($ack), 'response' => $out]), 0, 4000)]);

        // A clean acknowledgement comes back empty or as a Success block; a
        // 501 "Bookings not exists" means the identifiers were not recognised.
        return $out !== null && stripos($out, '"ErrorCode"') === false;
    }

    private function post(array $body): ?string
    {
        $ch = curl_init('https://live.ipms247.com/pmsinterface/pms_connectivity.php');
        curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => json_encode($body), CURLOPT_HTTPHEADER => ['Content-Type:application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120]);
        $out = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        return $err || $out === false ? null : (string) $out;
    }
}
