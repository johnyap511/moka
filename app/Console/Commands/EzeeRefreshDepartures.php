<?php

namespace App\Console\Commands;

use App\Booking;
use App\EzeeGroup;
use App\OtherModel\EzeeBooking;
use App\Support\EzeeAutoAssign;
use App\Support\EzeePricing;
use App\Support\Lock;
use Illuminate\Console\Command;

/**
 * Charges posted at checkout (a cleaning top-up, a late charge, a voided night) do
 * not always move eZee's Modifydatetime, so the hourly sync, which asks for changed
 * reservations, never sees them (RES20690-1: cleaning 41.85 → 61.85, 5 Oct 2026).
 * eZee does return a stay when the window reaches its departure, so once a day the
 * stays that departed yesterday and today are re-read, the stored record refreshed,
 * and a linked booking in an open month repriced from eZee by the usual rule.
 */
class EzeeRefreshDepartures extends Command
{
    protected $signature = 'ezee:refresh-departures {--days=2 : departures within the last N days} {--dry-run}';
    protected $description = 'Re-read stays that have just departed and reprice linked bookings from eZee';

    public function handle(): int
    {
        $dry  = (bool) $this->option('dry-run');
        $from = date('Y-m-d', strtotime('-' . max(1, (int) $this->option('days')) . ' days'));
        $to   = date('Y-m-d');
        $n    = ['seen' => 0, 'refreshed' => 0, 'repriced' => 0, 'failed' => 0];
        $auto = new EzeeAutoAssign(false, null);

        foreach (EzeeGroup::all() as $g) {
            $ch = curl_init();
            curl_setopt_array($ch, [CURLOPT_URL => 'https://live.ipms247.com/pmsinterface/getdataAPI.php', CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120, CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => "<RES_Request><Request_Type>Booking</Request_Type><Authentication><HotelCode>{$g->hotel_code}</HotelCode><AuthCode>{$g->auth_key}</AuthCode></Authentication><FromDate>{$from}</FromDate><ToDate>{$to}</ToDate></RES_Request>",
                CURLOPT_HTTPHEADER => ['Content-Type: application/xml']]);
            $xml = @simplexml_load_string(trim((string) curl_exec($ch)));
            curl_close($ch);
            if (!$xml) {
                $this->warn("{$g->hotel_code}: no answer");
                continue;
            }
            foreach ($xml->xpath('//BookingTran') as $t) {
                $tx = (string) $t->TransactionId;
                $eb = EzeeBooking::where('TransactionId', $tx)->first();
                if (!$eb || (int) $eb->status === 1) {
                    continue;
                }
                $n['seen']++;
                $tran = json_decode(json_encode($t), true);
                $list = EzeePricing::extraChargeList($tran);
                $new  = [
                    'TotalAmountAfterTax' => (string) $t->TotalAmountAfterTax, 'TotalAmountBeforeTax' => (string) $t->TotalAmountBeforeTax, 'TotalExtraCharge' => (string) $t->TotalExtraCharge,
                    'TotalDiscount' => (string) $t->TotalDiscount, 'extra_charges' => $list === null ? null : json_encode($list), 'extra_charge_tax' => EzeePricing::extraChargeTax($tran),
                    'ezee_current_status' => (string) $t->CurrentStatus ?: $eb->ezee_current_status, 'End' => (string) $t->End ?: $eb->End,
                ];
                $room = (string) $t->eZeePMSRoomid ?: (string) $t->RoomName;
                if ($room !== '') {
                    $new['RoomName'] = $room;
                }
                $changed = [];
                foreach ($new as $k => $v) {
                    $old = $eb->$k;
                    if (is_numeric($v) && is_numeric($old) ? abs((float) $v - (float) $old) > 0.009 : (string) $v !== (string) $old) {
                        $changed[$k] = [$old, $v];
                    }
                }
                if (!$changed) {
                    continue;
                }
                $n['refreshed']++;
                $this->line("  {$eb->SubBookingId}: " . json_encode($changed));
                if ($dry) {
                    continue;
                }
                EzeeBooking::where('id', $eb->id)->update($new + ['updated_at' => now()]);
                $b = $eb->book_id ? Booking::find($eb->book_id) : null;
                if ($b && (int) $b->status !== 1 && !Lock::isLocked($b->check_in) && array_intersect(array_keys($changed), ['TotalAmountAfterTax', 'TotalAmountBeforeTax', 'TotalExtraCharge', 'extra_charges', 'extra_charge_tax'])) {
                    try {
                        $r = $auto->acceptEzeeAmounts($eb->fresh());
                        if (!empty($r['changed'])) {
                            $n['repriced']++;
                            $this->line("    repriced #{$b->id}: {$r['note']}");
                        }
                    } catch (\Throwable $e) {
                        $n['failed']++;
                        $this->warn("    #{$b->id}: " . $e->getMessage());
                    }
                }
            }
            sleep(3);
        }
        $this->info(($dry ? 'DRY RUN ' : '') . json_encode($n));

        return 0;
    }
}
