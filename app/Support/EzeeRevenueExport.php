<?php

namespace App\Support;

use App\Booking;
use App\OtherModel\EzeeBooking;
use App\Listing;
use Illuminate\Support\Facades\DB;

/**
 * The revenue export finance reconciles against EZEE's "Detail Revenue
 * Report", built to the same shape so the two files line up row for row.
 *
 * EZEE's report is one line per folio (one per room), for the nights that
 * fall inside the calendar month. Room charges are the nights in the month at
 * the stay's average rate; the cleaning fee sits in the arrival month; the
 * channel's commission is booked whole in the departure month, so a stay that
 * departs on the 1st appears with zero nights and its full commission. Damage
 * deposits are inside EZEE's total. This export follows every one of those
 * conventions, and adds the columns finance needs to see why a line differs:
 * the MOKA booking ids, the assignment status, and, when EZEE's own file is
 * supplied, EZEE's figure beside ours with the difference and a reason.
 *
 * This is a second export. The older booking export (whole bookings by
 * check-in date at the stored price) is untouched.
 */
class EzeeRevenueExport
{
    public const HOTELS = [
        '19676' => 'EkoCheras',
        '20317' => 'Bell Suites',
        '20318' => 'Forum / Damai 88',
        '20319' => 'Arte Cheras / Queensville / KL Gateway',
        '20320' => 'Alinea Suites',
    ];

    private const SST = 0.08;

    private string $from;
    private string $to;
    /** @var string[] */
    private array $hotels;

    public function __construct(string $month, ?string $hotel = null)
    {
        $this->from   = $month . '-01';
        $this->to     = date('Y-m-01', strtotime($this->from . ' +1 month'));
        // PHP turns the numeric hotel codes into int keys; the codes are compared as strings everywhere.
        $this->hotels = $hotel && isset(self::HOTELS[$hotel]) ? [(string) $hotel] : array_map('strval', array_keys(self::HOTELS));
    }

    /** @return string[] */
    public function headers(bool $compared): array
    {
        $h = ['Sr. No', 'Property', 'Reservation No', 'Folio No', 'Guest Name', 'Source', 'Arrival', 'Dept.', 'Nights', 'Nights in Month',
            'Room', 'Vouc. No', 'Room Charges (RM) (Excl. Tax)', 'Cleaning Fee (RM) (Excl. Tax)', 'Extras - Company (RM) (Excl. Tax)',
            'Damage Deposit (RM)', 'Room Rate (RM) (Incl. Tax)', 'SST (RM)', 'Discount (RM)', 'Total (RM) (Incl. Tax)', 'Commission (RM)',
            'Revenue at Property (RM) (Incl. Tax)', 'MOKA Booking IDs', 'MOKA Status'];

        if ($compared) {
            $h = array_merge($h, ['EZEE Total (RM) (Incl. Tax)', 'EZEE Commission (RM)', 'EZEE Damage Deposit (RM)',
                'EZEE Cleaning (RM) (Incl. Tax)', 'EZEE Extras - Company (RM) (Incl. Tax)', 'EZEE Extras detail', 'Difference (RM)', 'Note']);
        }

        return $h;
    }

    /**
     * @param  string[]  $ezeeFiles  paths to EZEE detail revenue report CSVs, any number
     * @return array<int,array<string,mixed>> lines in EZEE's order: by property, then arrival
     */
    public function lines(array $ezeeFiles = []): array
    {
        $lines = array_merge($this->ezeeBackedLines(), $this->manualLines());

        usort($lines, fn ($a, $b) => [$a['hotel'], $a['arrival'], $a['res']] <=> [$b['hotel'], $b['arrival'], $b['res']]);

        if ($ezeeFiles) {
            $lines = $this->compare($lines, $ezeeFiles);
        }

        foreach ($lines as $i => &$l) {
            $l['sr'] = $i + 1;
        }

        return $lines;
    }

    /** @var array<int,Booking> live bookings around the month, by id */
    private array $byId = [];
    /** @var array<string,Booking[]> the same, by folio */
    private array $byFolio = [];
    private bool $loaded = false;

    /**
     * Everything the month can touch, read once: a year-long tenancy that
     * started last autumn still has a segment in this month, so the window is
     * wide, and rows are grouped by folio in memory instead of one query per
     * reservation.
     */
    private function load(): void
    {
        if ($this->loaded) {
            return;
        }
        $rows = Booking::withoutGlobalScopes()->with('listing', 'user')
            ->where('status', '<>', 1)
            ->where('check_out', '>', date('Y-m-d', strtotime($this->from . ' -400 days')))
            ->where('check_in', '<', date('Y-m-d', strtotime($this->to . ' +400 days')))
            ->orderBy('check_in')->get();
        foreach ($rows as $b) {
            $this->byId[$b->id] = $b;
            if ($b->folio_no !== null && $b->folio_no !== '') {
                $this->byFolio[$b->folio_no][] = $b;
            }
        }
        $this->loaded = true;
    }

    /** @return array<int,array<string,mixed>> */
    private function ezeeBackedLines(): array
    {
        $this->load();
        $rows = EzeeBooking::query()
            ->whereIn(DB::raw('SUBSTR(TransactionId,1,5)'), $this->hotels)
            ->where('status', '<>', 1)
            ->where('End', '>=', $this->from)
            ->where('Start', '<', $this->to)
            ->where('Start', '>=', '2024-01-01')
            ->whereRaw('DATEDIFF(End, Start) BETWEEN 0 AND 400')
            // Rows whose End and RoomName were overwritten by another hotel's
            // same-numbered reservation describe a different property's stay.
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('ezee_bookings as o')
                ->whereColumn('o.SubBookingId', 'ezee_bookings.SubBookingId')
                ->whereColumn('o.id', '<>', 'ezee_bookings.id')
                ->whereRaw('SUBSTR(o.TransactionId,1,5) <> SUBSTR(ezee_bookings.TransactionId,1,5)')
                ->whereColumn('o.End', 'ezee_bookings.End')
                ->whereRaw('(o.RoomName <=> ezee_bookings.RoomName OR ezee_bookings.RoomName IS NULL OR ezee_bookings.RoomName = \'\')')
                ->whereColumn('o.Start', '>', 'ezee_bookings.Start'))
            ->orderBy('Start')
            ->get();

        $lines = [];

        foreach ($rows as $e) {
            $hotel   = substr((string) $e->TransactionId, 0, 5);
            $pointer = $e->book_id ? ($this->byId[$e->book_id] ?? Booking::withoutGlobalScopes()->with('listing', 'user')->find($e->book_id)) : null;
            $isExtra = stripos((string) $e->RoomName, 'Extra Room') !== false;

            $segments = $pointer && (int) $pointer->status !== 1 ? $this->stayRows($pointer, $hotel, $e->Start, $e->End) : collect();
            // A row whose dates were overwritten by another hotel's reservation
            // can carry an End inside the month while the stay it is linked to
            // ended long before. The stay decides, not the row.
            if ($segments->isNotEmpty() && !$segments->contains(fn ($b) => $b->check_out > $this->from && $b->check_in < $this->to)) {
                continue;
            }
            $figures  = $this->figures($segments, $e->Start, $e->End);

            $status = match (true) {
                $segments->isNotEmpty()                     => 'Assigned',
                $pointer && (int) $pointer->status === 1    => 'Link cancelled - review',
                $isExtra                                     => 'Extra room (company)',
                (int) $e->status === EzeeAutoAssign::NO_UNIT => 'No unit',
                default                                      => 'Unassigned',
            };

            $lines[] = array_merge([
                'hotel'    => $hotel,
                'property' => self::HOTELS[$hotel] ?? $hotel,
                'res'      => preg_replace('/-\d+$/', '', (string) $e->SubBookingId),
                'folio'    => $e->folio_no ?: ($pointer->folio_no ?? ('FN' . ltrim(substr((string) $e->TransactionId, 5), '0'))),
                // EZEE's guest name is the one on the folio; hand-keyed MOKA
                // bookings often sit under a shared staff account.
                'guest'    => trim($e->FirstName . ' ' . $e->LastName) ?: ($pointer && $pointer->user ? trim($pointer->user->name . ' ' . $pointer->user->last_name) : ''),
                'source'   => self::channel((string) $e->Source),
                'arrival'  => substr((string) $e->Start, 0, 10),
                'dept'     => substr((string) $e->End, 0, 10),
                'nights'   => max(0, (int) ((strtotime($e->End) - strtotime($e->Start)) / 86400)),
                'room'     => $segments->isNotEmpty() ? $segments->pluck('listing.name')->unique()->implode(' → ') : (string) $e->RoomName,
                'voucher'  => explode('/', (string) $e->VoucherNo)[0],
                'ids'      => $segments->pluck('id')->implode(' '),
                'status'   => $status,
                'ezee_row' => $e,
            ], $figures);
        }

        return $lines;
    }

    /**
     * Bookings entered with "+ New Booking" that never went through EZEE:
     * one line per folio and unit, so finance sees them as MOKA-only.
     *
     * @return array<int,array<string,mixed>>
     */
    private function manualLines(): array
    {
        $this->load();
        $linked = DB::table('ezee_bookings')->where('status', '<>', 1)->whereNotNull('book_id')->pluck('book_id')->flip();

        $groups = [];
        foreach ($this->byId as $b) {
            if ($b->check_out <= $this->from || $b->check_in >= $this->to || isset($linked[$b->id])) {
                continue;
            }
            $key = ($b->folio_no ?: 'B' . $b->id) . '|' . $b->listing_id;
            $groups[$key][] = $b;
        }

        $lines = [];
        foreach ($groups as $key => $group) {
            [$folio, $listingId] = explode('|', $key);
            // a folio+unit group with any linked row is an EZEE stay, not a manual one
            $stay = collect($this->byFolio[$folio] ?? [$group[0]])->filter(fn ($b) => (int) $b->listing_id === (int) $listingId)->sortBy('check_in')->values();
            if ($stay->contains(fn ($b) => isset($linked[$b->id]))) {
                continue;
            }
            $first = $stay->first();
            $hotel = self::hotelOfListing($first->listing->name ?? '');
            // A night carved out to another unit (a split stay) belongs to the
            // linked reservation's line, not to a line of its own.
            $sibling = collect($this->byFolio[$folio] ?? [])->first(fn ($b) => isset($linked[$b->id])
                && self::hotelOfListing($b->listing->name ?? '') === $hotel
                && abs(strtotime((string) $b->check_in) - strtotime((string) $first->check_in)) < 60 * 86400);
            if ($sibling) {
                continue;
            }
            if ($hotel && !in_array($hotel, $this->hotels, true)) {
                continue;
            }

            $lines[] = array_merge([
                'hotel'    => $hotel ?: '',
                'property' => $hotel ? self::HOTELS[$hotel] : 'Other',
                'res'      => '',
                'folio'    => $first->folio_no,
                'guest'    => $first->user ? trim($first->user->name . ' ' . $first->user->last_name) : '',
                'source'   => self::channel((string) $first->source),
                'arrival'  => substr((string) $stay->min('check_in'), 0, 10),
                'dept'     => substr((string) $stay->max('check_out'), 0, 10),
                'nights'   => (int) $stay->sum('nights'),
                'room'     => $first->listing->name ?? '',
                'voucher'  => '',
                'ids'      => $stay->pluck('id')->implode(' '),
                'status'   => 'MOKA only (manual)',
                'ezee_row' => null,
            ], $this->figures($stay, $stay->min('check_in'), $stay->max('check_out')));
        }

        return $lines;
    }

    /**
     * Every live row of one stay: the linked booking and the same-folio rows
     * on units of the same property inside the stay's dates. That covers month
     * segments and a mid-stay room move, and excludes another property's
     * guest who happens to share the folio number.
     */
    private function stayRows(Booking $pointer, string $hotel, string $start, string $end)
    {
        // Same rule as the assigner: rows that butt directly against the stay
        // on this property, never a same-folio row that merely falls nearby.
        return BookingSplitter::stayChain($pointer, $hotel);
    }

    /**
     * The month's share of a stay, EZEE's way: room and SST for the nights in
     * the month, cleaning in the arrival month, commission whole in the
     * departure month.
     *
     * @return array<string,float>
     */
    private function figures($segments, ?string $start, ?string $end): array
    {
        $room = 0.0; $sst = 0.0; $cleaning = 0.0; $discount = 0.0; $nightsIn = 0; $feeWhole = 0.0;

        foreach ($segments as $b) {
            $in  = max((string) $b->check_in, $this->from);
            $out = min((string) $b->check_out, $this->to);
            $an  = max(0, (int) ((strtotime($out) - strtotime($in)) / 86400));
            $n   = max(1, (int) $b->nights);

            $nightsIn += $an;
            $room     += (float) $b->price_night * $an;
            $sst      += (float) $b->sst * $an / $n;
            $feeWhole += (float) $b->ota_fee;

            if ($b->check_in >= $this->from && $b->check_in < $this->to) {
                $cleaning += (float) $b->cleaning_fee + (float) $b->sst_cf;
                $discount += (float) $b->discount_fee;
            }
        }

        $departsInMonth = $end && $end >= $this->from && $end < $this->to;
        $commission     = $departsInMonth ? $feeWhole : 0.0;
        $total          = $room + $cleaning + $sst - $discount;

        return [
            'nights_in'  => $nightsIn,
            'room_charge' => round($room, 2),
            'cleaning'   => round($cleaning, 2),
            'extras'     => 0.00,
            'deposit'    => 0.00,
            'rate'       => $nightsIn > 0 ? round(($room + $sst) / $nightsIn, 2) : 0.00,
            'sst'        => round($sst, 2),
            'discount'   => round($discount, 2),
            'total'      => round($total, 2),
            'commission' => round($commission, 2),
            'revenue'    => round($total - $commission, 2),
        ];
    }

    /**
     * Lay EZEE's own lines beside ours. Matched on property and reservation
     * number, then on the room for a multi-room reservation. EZEE lines with
     * no MOKA counterpart are appended so nothing on their file goes unseen.
     *
     * @param  array<int,array<string,mixed>>  $lines
     * @param  string[]  $files
     * @return array<int,array<string,mixed>>
     */
    /** What MOKA holds for an EZEE folio that has no export line this month. */
    private function mokaStateOf(string $hotel, string $res, string $folio): string
    {
        $q = DB::table('ezee_bookings')->where('TransactionId', 'like', $hotel . '%');
        $q = $res !== '' ? $q->where('SubBookingId', $res) : $q->where('folio_no', $folio);
        $row = $q->orderByDesc('id')->first(['status', 'book_id']);
        if (!$row) {
            return 'not received from EZEE';
        }
        if ((int) $row->status === 1) {
            return 'cancelled';
        }
        $b = $row->book_id ? DB::table('bookings')->where('id', $row->book_id)->first(['id', 'status', 'check_in', 'check_out']) : null;
        if (!$b) {
            return (int) $row->status === 7 ? 'marked no unit' : 'unassigned';
        }

        return ((int) $b->status === 1 ? 'cancelled booking' : 'live booking') . " #{$b->id} {$b->check_in} to {$b->check_out}";
    }

    private function compare(array $lines, array $files): array
    {
        $ezee = []; $extras = []; $loose = [];
        foreach ($files as $f) {
            if (self::isExtraChargeFile($f)) {
                [$byFolio, $noFolio] = self::parseExtraChargeCsv($f);
                foreach ($byFolio as $k => $x) {
                    $extras[$k] = isset($extras[$k]) ? self::mergeExtras($extras[$k], $x) : $x;
                }
                foreach ($noFolio as $x) {
                    $loose[] = $x;
                }
                continue;
            }
            foreach (self::parseEzeeCsv($f) as $row) {
                $ezee[] = $row;
            }
        }
        $extrasUsed = [];

        // Keyed on property and reservation number; a reservation EZEE sends
        // without a number is keyed on its folio instead, which staff carry
        // across when they key the tenancy by hand.
        $keyOf = fn (string $hotel, string $res, string $folio) => $hotel . '|' . ($res !== '' ? $res : 'FN:' . preg_replace('/\D/', '', $folio));
        $byKey = [];
        foreach ($lines as $i => $l) {
            if ($l['res'] !== '' || $l['folio'] !== '') {
                $byKey[$keyOf($l['hotel'], $l['res'], (string) $l['folio'])][] = $i;
            }
        }

        $used = [];
        foreach ($ezee as $r) {
            $cands = $r['hotel'] !== '' ? array_diff($byKey[$keyOf($r['hotel'], $r['res'], $r['folio'])] ?? [], $used) : [];
            if (!$cands && $r['hotel'] === '' && stripos($r['room'], 'Extra Room') !== false) {
                foreach ($byKey as $k => $idx) {
                    if (substr($k, 6) === $r['res']) {
                        foreach (array_diff($idx, $used) as $i) {
                            if (stripos((string) ($lines[$i]['ezee_row']->RoomName ?? ''), 'Extra Room') !== false) {
                                $cands = [$i];
                                break 2;
                            }
                        }
                    }
                }
            }
            if (!$cands && $r['hotel'] === '') {
                // The report names a room the map cannot place (an extra room, a
                // unit not yet mapped). Take the reservation number if exactly
                // one property has it; otherwise leave the line unmatched.
                $hits = [];
                foreach ($byKey as $k => $idx) {
                    if (substr($k, 6) === ($r['res'] !== '' ? $r['res'] : 'FN:' . preg_replace('/\D/', '', $r['folio']))) {
                        $hits[$k] = array_diff($idx, $used);
                    }
                }
                $hits = array_filter($hits);
                if (count($hits) === 1) {
                    $cands = reset($hits);
                }
            }
            // A tenancy EZEE sends without a reservation number is keyed on its
            // folio, but staff sometimes key a different folio by hand. Fall back
            // to the same unit with the same arrival and departure.
            if (!$cands && $r['hotel'] !== '' && $r['res'] === '') {
                foreach ($lines as $i => $l) {
                    if (in_array($i, $used, true) || $l['hotel'] !== $r['hotel'] || $l['arrival'] !== $r['arrival'] || $l['dept'] !== $r['dept']) {
                        continue;
                    }
                    $unit = self::unitKey($l['ezee_row']->RoomName ?? $l['room']);
                    if ($unit !== '' && str_starts_with(self::unitKey($r['room']), $unit)) {
                        $cands = [$i];
                        break;
                    }
                }
            }
            $pick = null;
            if (count($cands) === 1) {
                $pick = reset($cands);
            } elseif ($cands) {
                foreach ($cands as $i) {
                    $unit = self::unitKey($lines[$i]['ezee_row']->RoomName ?? $lines[$i]['room']);
                    if ($unit !== '' && str_starts_with(self::unitKey($r['room']), $unit)) {
                        $pick = $i;
                        break;
                    }
                }
                $pick = $pick ?? reset($cands);
            }

            if ($pick === null) {
                // EZEE prints a line for every folio it touched in the month, including
                // ones that earned nothing: cancelled, no-show, or re-booked under another
                // reservation. Those are not gaps in MOKA, so they are labelled apart.
                $zero  = abs($r['total']) < 0.005 && abs($r['deposit']) < 0.005;
                $ours  = $this->mokaStateOf($r['hotel'], $r['res'], $r['folio']);
                $lines[] = array_merge([
                    'hotel' => $r['hotel'], 'property' => self::HOTELS[$r['hotel']] ?? $r['hotel'], 'res' => $r['res'], 'folio' => $r['folio'],
                    'guest' => $r['guest'], 'source' => $r['source'], 'arrival' => $r['arrival'], 'dept' => $r['dept'], 'nights' => $r['nights'],
                    'room' => $r['room'], 'voucher' => '', 'ids' => '', 'status' => $zero ? 'EZEE zero line' : 'EZEE only', 'ezee_row' => null,
                    'nights_in' => '', 'room_charge' => '', 'cleaning' => '', 'extras' => '', 'deposit' => '', 'rate' => '', 'sst' => '',
                    'discount' => '', 'total' => '', 'commission' => '', 'revenue' => '',
                ], ['ezee_total' => $r['total'], 'ezee_commission' => $r['commission'], 'ezee_deposit' => $r['deposit'], 'ezee_cleaning' => '', 'ezee_extras' => '', 'ezee_extras_detail' => '',
                    'difference' => round(-($r['total'] - $r['deposit']), 2),
                    'note' => ($zero ? 'EZEE reports RM 0 for this folio (cancelled, no-show or re-booked under another reservation)' : 'EZEE has revenue here but MOKA has no line this month: check the booking') . ($ours ? "; MOKA: $ours" : '')]);
                continue;
            }

            $used[] = $pick;
            $l      = &$lines[$pick];
            $xk     = $r['hotel'] . '|' . preg_replace('/\D/', '', $r['folio']);
            $x      = $extras[$xk] ?? null;
            if ($x) {
                $extrasUsed[$xk] = true;
            }
            $companyExtras = $x ? $x['extras'] : 0.0;
            $target = $r['total'] - max($r['deposit'], $x['deposit'] ?? 0.0) - $companyExtras;
            $diff   = round((float) $l['total'] - $target, 2);
            $l['ezee_total']         = $r['total'];
            $l['ezee_commission']    = $r['commission'];
            $l['ezee_deposit']       = max($r['deposit'], $x['deposit'] ?? 0.0);
            $l['ezee_cleaning']      = $x ? round($x['cleaning'], 2) : '';
            $l['ezee_extras']        = $x ? round($x['extras'], 2) : '';
            $l['ezee_extras_detail'] = $x ? $x['detail'] : '';
            $l['difference']         = $diff;
            $l['note']               = self::reason($l, $r, $diff) . ($companyExtras > 0 ? sprintf(' (company extras RM %.2f excluded)', $companyExtras) : '');
            unset($l);
        }

        // Extra charges on folios that matched no revenue line, and incidental
        // invoices with no folio at all: company revenue with no booking behind
        // it, listed so the extra-charge file also reconciles to the sen.
        foreach ($extras as $k => $x) {
            if (isset($extrasUsed[$k]) || ($x['extras'] <= 0 && $x['cleaning'] <= 0 && $x['deposit'] <= 0)) {
                continue;
            }
            [$hotel, $folio] = explode('|', $k);
            $lines[] = self::extraOnlyLine($hotel, 'FN' . $folio, $x['room'], $x);
        }
        foreach ($loose as $x) {
            $lines[] = self::extraOnlyLine($x['hotel'], $x['ref'], '', $x);
        }

        foreach ($lines as &$l) {
            if (!array_key_exists('ezee_total', $l)) {
                $l['ezee_total'] = ''; $l['ezee_commission'] = ''; $l['ezee_deposit'] = '';
                $l['difference'] = $l['total'] !== '' ? round((float) $l['total'], 2) : '';
                $l['note']       = $l['status'] === 'MOKA only (manual)' ? 'Manual booking, not in EZEE' : 'Not on the EZEE file supplied';
            }
        }

        return $lines;
    }

    /** @return array<string,mixed> */
    private static function extraOnlyLine(string $hotel, string $ref, string $room, array $x): array
    {
        return [
            'hotel' => $hotel, 'property' => self::HOTELS[$hotel] ?? ($hotel ?: 'Unknown'), 'res' => '', 'folio' => $ref, 'guest' => '', 'source' => '',
            'arrival' => $x['date'] ?? '', 'dept' => '', 'nights' => '', 'nights_in' => '', 'room' => $room, 'voucher' => '', 'ids' => '',
            'status' => 'EZEE extra charge (company)', 'ezee_row' => null,
            'room_charge' => '', 'cleaning' => '', 'extras' => '', 'deposit' => '', 'rate' => '', 'sst' => '', 'discount' => '', 'total' => '', 'commission' => '', 'revenue' => '',
            'ezee_total' => '', 'ezee_commission' => '', 'ezee_deposit' => $x['deposit'] > 0 ? round($x['deposit'], 2) : '',
            'ezee_cleaning' => $x['cleaning'] > 0 ? round($x['cleaning'], 2) : '', 'ezee_extras' => round($x['extras'], 2), 'ezee_extras_detail' => $x['detail'],
            'difference' => 0, 'note' => $x['cleaning'] > 0 ? 'Charge on a folio with no revenue line this month' : 'Company extra charge, no booking',
        ];
    }

    private static function isExtraChargeFile(string $path): bool
    {
        $fh = fopen($path, 'r');
        $hdr = $fh ? fgetcsv($fh) : null;
        if ($fh) {
            fclose($fh);
        }
        return $hdr && preg_replace('/^\xEF\xBB\xBF/', '', trim((string) $hdr[0])) === 'Date' && trim((string) ($hdr[1] ?? '')) === 'Voucher No';
    }

    /**
     * EZEE's "Daily Extra Charge" report: sections headed "Extra Charge","<type>",
     * each row referencing a folio and room or an incidental invoice. Cleaning
     * fees of every flavour compare against our cleaning column; the damage
     * deposit is a deposit; everything else is company revenue.
     *
     * @return array{0: array<string,array<string,mixed>>, 1: array<int,array<string,mixed>>}
     */
    private static function parseExtraChargeCsv(string $path): array
    {
        $fh = fopen($path, 'r');
        if (!$fh) {
            return [[], []];
        }
        $map = EzeeUnitMap::make();
        $type = ''; $byFolio = []; $loose = []; $fileHotel = '';
        $num = fn ($v) => (float) str_replace(',', '', (string) $v);
        $date = fn ($v) => preg_match('#^(\d\d)/(\d\d)/(\d{4})#', (string) $v, $m) ? "$m[3]-$m[2]-$m[1]" : (string) $v;

        while (($row = fgetcsv($fh)) !== false) {
            $c0 = preg_replace('/^\xEF\xBB\xBF/', '', trim((string) ($row[0] ?? '')));
            if ($c0 === 'Extra Charge') {
                $type = trim((string) ($row[1] ?? ''));
                continue;
            }
            if (!preg_match('#^\d\d/\d\d/\d{4}$#', $c0) || $type === '') {
                continue;
            }
            $ref   = (string) ($row[2] ?? '');
            $total = $num($row[7] ?? 0);
            $kind  = match (true) {
                stripos($type, 'deposit') !== false => 'deposit',
                stripos($type, 'clean') !== false || stripos($type, 'channel') !== false => 'cleaning',
                default => 'extras',
            };
            $room = preg_match('/Room\s*:\s*(.+)$/', $ref, $m) ? trim($m[1]) : '';
            if ($room !== '' && $fileHotel === '') {
                $listing = $map->listingForReportRoom($room);
                $fileHotel = $listing ? (self::hotelOfListing($listing->name) ?? '') : '';
            }
            $label = $type . ' ' . number_format($total, 2) . (trim((string) ($row[10] ?? '')) !== '' ? ' (' . trim((string) $row[10]) . ')' : '');

            if (preg_match('/Folio-FN(\d+)/', $ref, $m)) {
                $k = '|' . $m[1];   // hotel filled in below once known for the file
                $byFolio[$k] = $byFolio[$k] ?? ['cleaning' => 0.0, 'deposit' => 0.0, 'extras' => 0.0, 'detail' => '', 'room' => $room, 'date' => $date($c0)];
                $byFolio[$k][$kind] += $total;
                if ($kind === 'extras') {
                    $byFolio[$k]['detail'] = trim($byFolio[$k]['detail'] . '; ' . $label, '; ');
                }
            } else {
                $loose[] = ['hotel' => '', 'ref' => preg_match('/Invoice\s*:\s*(\S+)/', $ref, $m) ? $m[1] : $ref, 'cleaning' => $kind === 'cleaning' ? $total : 0.0,
                    'deposit' => $kind === 'deposit' ? $total : 0.0, 'extras' => $kind === 'extras' ? $total : 0.0, 'detail' => $label, 'date' => $date($c0)];
            }
        }
        fclose($fh);

        $keyed = [];
        foreach ($byFolio as $k => $x) {
            $keyed[$fileHotel . $k] = $x;
        }
        foreach ($loose as &$x) {
            $x['hotel'] = $fileHotel;
        }

        return [$keyed, $loose];
    }

    private static function mergeExtras(array $a, array $b): array
    {
        return ['cleaning' => $a['cleaning'] + $b['cleaning'], 'deposit' => $a['deposit'] + $b['deposit'], 'extras' => $a['extras'] + $b['extras'],
            'detail' => trim($a['detail'] . '; ' . $b['detail'], '; '), 'room' => $a['room'] ?: $b['room'], 'date' => $a['date'] ?: $b['date']];
    }

    private static function reason(array $l, array $r, float $diff): string
    {
        if (abs($diff) <= max(2, 0.015 * max(abs($r['total'] - $r['deposit']), 1))) {
            return 'OK';
        }
        if ($l['status'] !== 'Assigned') {
            return $l['status'];
        }
        // The unit matters more than the amount: a stay on the wrong unit pays
        // the wrong owner. The report's room is resolved through the room map
        // and compared with the unit the stay ended on ("A → B" ends on B).
        $label = (string) $r['room'];
        if (stripos($label, 'extra room') === 0) {
            // The report names extra rooms without the property; the room map needs it.
            $label = (['19676' => 'EkoCheras', '20317' => 'Bell Suites', '20318' => 'Forum', '20319' => 'Arte Cheras', '20320' => 'Alinea'][$r['hotel']] ?? '') . ' ' . preg_replace('/-.*$/', '', $label);
        }
        $reported = stripos((string) $r['room'], 'extra room') === 0
            ? Listing::withoutGlobalScope('notArchived')->whereRaw('LOWER(name) = ?', [strtolower(trim($label))])->first()
            : self::unitMap()->listingForReportRoom($label);
        $final    = trim((string) preg_replace('/^.*→\s*/u', '', (string) $l['room']));
        $final    = trim((string) preg_replace('/\s*\(.*$/', '', $final));
        $bothExtra = stripos((string) $r['room'], 'extra room') !== false && stripos($final, 'extra room') !== false;
        if ($reported && !$bothExtra && strcasecmp(preg_replace('/\s+/', ' ', $reported->name), preg_replace('/\s+/', ' ', $final)) !== 0) {
            return sprintf('Unit differs: EZEE reports %s, MOKA has it on %s', $reported->name, $final);
        }
        if ((int) $l['nights_in'] !== (int) $r['nights_in'] && $r['nights_in'] !== null) {
            return sprintf('Nights in month differ: MOKA %d, EZEE %d', $l['nights_in'], $r['nights_in']);
        }
        if (abs((float) $l['room_charge'] - $r['room_excl']) <= max(2, 0.015 * max($r['room_excl'], 1))) {
            return $r['other'] > 0 ? sprintf('Extras posted in EZEE (Other RM %.2f), company revenue', $r['other']) : 'Room charge matches; cleaning or extra charges differ';
        }
        return (float) $l['room_charge'] < $r['room_excl'] ? 'Rate changed in EZEE after booking (MOKA lower)' : 'Rate differs (MOKA higher)';
    }

    /** @return array<int,array<string,mixed>> */
    private static function parseEzeeCsv(string $path): array
    {
        $fh = fopen($path, 'r');
        if (!$fh) {
            return [];
        }
        $hdr = fgetcsv($fh);
        if (!$hdr) {
            return [];
        }
        $hdr[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $hdr[0]);
        $ix     = array_flip(array_map('trim', $hdr));
        $col    = fn (array $row, string $name) => isset($ix[$name]) ? ($row[$ix[$name]] ?? '') : '';
        $num    = fn ($v) => (float) str_replace(',', '', (string) $v);
        $date   = fn ($v) => preg_match('#^(\d\d)/(\d\d)/(\d{4})#', (string) $v, $m) ? "$m[3]-$m[2]-$m[1]" : (string) $v;
        $out    = [];
        $unitMap = EzeeUnitMap::make();

        while (($row = fgetcsv($fh)) !== false) {
            if (!isset($row[1]) || !preg_match('/^\d+$/', trim((string) $row[0]))) {
                continue;
            }
            $room  = (string) $col($row, 'Room');
            $hotel = self::hotelOfReportRoom($room, $unitMap) ?? '';
            $out[] = [
                'hotel'      => $hotel,
                'res'        => trim((string) $col($row, 'Reservation No')),
                'folio'      => trim((string) $col($row, 'Folio No')),
                'guest'      => trim((string) $col($row, 'Guest Name'), " ."),
                'source'     => self::channel((string) $col($row, 'Source')),
                'arrival'    => $date($col($row, 'Arrival')),
                'dept'       => $date($col($row, 'Dept.')),
                'nights'     => (int) $num($col($row, 'Nights')),
                'nights_in'  => null,
                'room'       => $room,
                'room_excl'  => $num($col($row, 'All Room Charges  (RM) (Exclusive of Tax)')),
                'other'      => $num($col($row, 'Other (RM) (Exclusive of Tax)')),
                'deposit'    => $num($col($row, 'Damage Deposit (RM) (Exclusive of Tax)')),
                'total'      => $num($col($row, 'Total (RM) (Inclusive of Tax)')),
                'commission' => $num($col($row, 'Commission (RM)')),
            ];
        }
        fclose($fh);

        return $out;
    }

    /** EZEE's report labels the room with the unit and room type run together; the unit map finds the unit. */
    private static function hotelOfReportRoom(string $room, EzeeUnitMap $map): ?string
    {
        // "Extra Room 1" exists at every property; the label alone cannot place it.
        if (stripos($room, 'Extra Room') !== false) {
            return null;
        }
        $listing = $map->listingForReportRoom($room);

        return $listing ? self::hotelOfListing($listing->name) : null;
    }

    public static function hotelOfListing(string $name): ?string
    {
        $n = strtolower($name);
        return match (true) {
            str_starts_with($n, 'eko')                                                                 => '19676',
            str_starts_with($n, 'bell')                                                                => '20317',
            str_starts_with($n, 'forum') || str_starts_with($n, 'damai')                               => '20318',
            str_starts_with($n, 'arte') || str_starts_with($n, 'queens') || str_starts_with($n, 'kl gate') => '20319',
            str_starts_with($n, 'alinea')                                                              => '20320',
            default                                                                                    => null,
        };
    }

    private static ?EzeeUnitMap $unitMap = null;

    private static function unitMap(): EzeeUnitMap
    {
        return self::$unitMap ??= EzeeUnitMap::make();
    }

    private static function unitKey(string $s): string
    {
        return EzeeUnitMap::compact(preg_replace('/\(.*?\)/', '', $s));
    }

    /** The channel as finance names it, without the account suffixes EZEE appends. */
    public static function channel(string $source): string
    {
        $s = trim(preg_replace('/[-_ ]?\d+.*$/', '', $source));
        return match (true) {
            stripos($s, 'booking') === 0 => 'Booking.com',
            stripos($s, 'ctrip') === 0 || stripos($s, 'trip.com') === 0 => 'CTrip',
            stripos($s, 'traveloka') === 0 => 'Traveloka',
            $s === 'PMS' || stripos($s, 'walk') === 0 => 'Walk In',
            default => $s ?: 'Unknown',
        };
    }

    /** @return array<int,mixed> one CSV row in header order */
    public function row(array $l, bool $compared): array
    {
        $r = [$l['sr'], $l['property'], $l['res'], $l['folio'], $l['guest'], $l['source'], $l['arrival'], $l['dept'], $l['nights'], $l['nights_in'],
            $l['room'], $l['voucher'], $l['room_charge'], $l['cleaning'], $l['extras'], $l['deposit'], $l['rate'], $l['sst'], $l['discount'],
            $l['total'], $l['commission'], $l['revenue'], $l['ids'], $l['status']];

        if ($compared) {
            $r = array_merge($r, [$l['ezee_total'], $l['ezee_commission'], $l['ezee_deposit'], $l['ezee_cleaning'] ?? '', $l['ezee_extras'] ?? '', $l['ezee_extras_detail'] ?? '', $l['difference'], $l['note']]);
        }

        return $r;
    }
}
