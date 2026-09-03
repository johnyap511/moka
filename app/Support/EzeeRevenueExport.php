<?php

namespace App\Support;

use App\Booking;
use App\OtherModel\EzeeBooking;
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
        $this->hotels = $hotel && isset(self::HOTELS[$hotel]) ? [$hotel] : array_keys(self::HOTELS);
    }

    /** @return string[] */
    public function headers(bool $compared): array
    {
        $h = ['Sr. No', 'Property', 'Reservation No', 'Folio No', 'Guest Name', 'Source', 'Arrival', 'Dept.', 'Nights', 'Nights in Month',
            'Room', 'Vouc. No', 'Room Charges (RM) (Excl. Tax)', 'Cleaning Fee (RM) (Excl. Tax)', 'Extras - Company (RM) (Excl. Tax)',
            'Damage Deposit (RM)', 'Room Rate (RM) (Incl. Tax)', 'SST (RM)', 'Discount (RM)', 'Total (RM) (Incl. Tax)', 'Commission (RM)',
            'Revenue at Property (RM) (Incl. Tax)', 'MOKA Booking IDs', 'MOKA Status'];

        if ($compared) {
            $h = array_merge($h, ['EZEE Total (RM) (Incl. Tax)', 'EZEE Commission (RM)', 'EZEE Damage Deposit (RM)', 'Difference (RM)', 'Note']);
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

    /** @return array<int,array<string,mixed>> */
    private function ezeeBackedLines(): array
    {
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
                ->whereRaw('o.RoomName <=> ezee_bookings.RoomName')
                ->whereColumn('o.Start', '>', 'ezee_bookings.Start'))
            ->orderBy('Start')
            ->get();

        $lines = [];

        foreach ($rows as $e) {
            $hotel   = substr((string) $e->TransactionId, 0, 5);
            $pointer = $e->book_id ? Booking::withoutGlobalScopes()->with('listing', 'user')->find($e->book_id) : null;
            $isExtra = stripos((string) $e->RoomName, 'Extra Room') !== false;

            $segments = $pointer && (int) $pointer->status !== 1 ? $this->stayRows($pointer, $hotel, $e->Start, $e->End) : collect();
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
                'guest'    => $pointer && $pointer->user ? trim($pointer->user->name . ' ' . $pointer->user->last_name) : trim($e->FirstName . ' ' . $e->LastName),
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
        $rows = Booking::withoutGlobalScopes()->with('listing', 'user')
            ->where('status', '<>', 1)
            ->where('check_out', '>', $this->from)
            ->where('check_in', '<', $this->to)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('ezee_bookings')->whereColumn('ezee_bookings.book_id', 'bookings.id')->where('ezee_bookings.status', '<>', 1))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('bookings as s')->join('ezee_bookings as e', 'e.book_id', '=', 's.id')
                ->whereColumn('s.folio_no', 'bookings.folio_no')->where('s.folio_no', '<>', '')->whereColumn('s.listing_id', 'bookings.listing_id')
                ->where('s.status', '<>', 1)->where('e.status', '<>', 1))
            ->orderBy('check_in')
            ->get();

        $lines = [];

        foreach ($rows->groupBy(fn ($b) => ($b->folio_no ?: 'B' . $b->id) . '|' . $b->listing_id) as $group) {
            $first = $group->sortBy('check_in')->first();
            $hotel = self::hotelOfListing($first->listing->name ?? '');

            if ($hotel && !in_array($hotel, $this->hotels, true)) {
                continue;
            }

            $stay = Booking::withoutGlobalScopes()->with('listing')->where('status', '<>', 1)
                ->where('listing_id', $first->listing_id)
                ->when($first->folio_no, fn ($q) => $q->where('folio_no', $first->folio_no), fn ($q) => $q->where('id', $first->id))
                ->orderBy('check_in')->get();

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
        $rows = Booking::withoutGlobalScopes()->with('listing')
            ->where('status', '<>', 1)
            ->where(fn ($q) => $q->where('id', $pointer->id)
                ->orWhere(fn ($w) => $w->where('folio_no', $pointer->folio_no)->where('folio_no', '<>', '')
                    ->where('check_out', '>', date('Y-m-d', strtotime($start . ' -1 day')))
                    ->where('check_in', '<', date('Y-m-d', strtotime($end . ' +1 day')))))
            ->orderBy('check_in')->get();

        return $rows->filter(fn ($b) => $b->id === $pointer->id
            || $b->listing_id === $pointer->listing_id
            || self::hotelOfListing($b->listing->name ?? '') === $hotel)->values();
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
    private function compare(array $lines, array $files): array
    {
        $ezee = [];
        foreach ($files as $f) {
            foreach (self::parseEzeeCsv($f) as $row) {
                $ezee[] = $row;
            }
        }

        $byKey = [];
        foreach ($lines as $i => $l) {
            if ($l['res'] !== '') {
                $byKey[$l['hotel'] . '|' . $l['res']][] = $i;
            }
        }

        $used = [];
        foreach ($ezee as $r) {
            $cands = array_diff($byKey[$r['hotel'] . '|' . $r['res']] ?? [], $used);
            if (!$cands) {
                $cands = [];
                foreach ($byKey as $k => $idx) {
                    if (substr($k, 6) === $r['res']) {
                        $cands = array_merge($cands, array_diff($idx, $used));
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
                $lines[] = array_merge([
                    'hotel' => $r['hotel'], 'property' => self::HOTELS[$r['hotel']] ?? $r['hotel'], 'res' => $r['res'], 'folio' => $r['folio'],
                    'guest' => $r['guest'], 'source' => $r['source'], 'arrival' => $r['arrival'], 'dept' => $r['dept'], 'nights' => $r['nights'],
                    'room' => $r['room'], 'voucher' => '', 'ids' => '', 'status' => 'EZEE only', 'ezee_row' => null,
                    'nights_in' => '', 'room_charge' => '', 'cleaning' => '', 'extras' => '', 'deposit' => '', 'rate' => '', 'sst' => '',
                    'discount' => '', 'total' => '', 'commission' => '', 'revenue' => '',
                ], ['ezee_total' => $r['total'], 'ezee_commission' => $r['commission'], 'ezee_deposit' => $r['deposit'],
                    'difference' => round(-($r['total'] - $r['deposit']), 2), 'note' => 'Not in MOKA export for this month']);
                continue;
            }

            $used[] = $pick;
            $l      = &$lines[$pick];
            $target = $r['total'] - $r['deposit'];
            $diff   = round((float) $l['total'] - $target, 2);
            $l['ezee_total']      = $r['total'];
            $l['ezee_commission'] = $r['commission'];
            $l['ezee_deposit']    = $r['deposit'];
            $l['difference']      = $diff;
            $l['note']            = self::reason($l, $r, $diff);
            unset($l);
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

    private static function reason(array $l, array $r, float $diff): string
    {
        if (abs($diff) <= max(2, 0.015 * max(abs($r['total'] - $r['deposit']), 1))) {
            return 'OK';
        }
        if ($l['status'] !== 'Assigned') {
            return $l['status'];
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

    /** EZEE's report names the room like "C2-30-01-AC - Mixed 2BR"; the unit is the first token. */
    private static function hotelOfReportRoom(string $room, EzeeUnitMap $map): ?string
    {
        $unit = trim(explode(' - ', $room)[0]);
        $unit = preg_replace('/-(AC|QV|FR|KLG)$/i', '', $unit);
        $listing = $map->listingForUnitName($unit);

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

    private static function unitKey(string $s): string
    {
        return strtolower(preg_replace('/\s+|\(.*?\)/', '', explode(' - ', $s)[0]));
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
            $r = array_merge($r, [$l['ezee_total'], $l['ezee_commission'], $l['ezee_deposit'], $l['difference'], $l['note']]);
        }

        return $r;
    }
}
