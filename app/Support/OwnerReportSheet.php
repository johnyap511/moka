<?php

namespace App\Support;

use App\Booking;
use App\Listing;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Owner reports phase 2 (11 Oct 2026): Homemoka builds the "Moka" bookings sheet the
 * report engine reads, instead of staff uploading a Reconciled_FINAL.xlsx. Same 19
 * columns and the same values as the Bookings export (BookingController@exportExcelRange),
 * one row per booking with check-in in the month, header on row 1, sheet named "Moka".
 * The export itself is left untouched (month-end rule: never change the old export).
 *
 * Part B (Sam, 6 Nov 2026): the same workbook also carries the group tabs the engine reads
 * ("BS Group", "Alinea Group", "Mesahill Group": member units with their OTA and cleaning
 * totals, Bell Suites split STUDIO / 3BR) and a 20th column, "Posted this month", the part
 * of the row's total whose nights fall inside the month, which is what the eZee Detail
 * Revenue Report shows and what staff tick against. The engine reads columns by name, so
 * the extra column changes nothing for it.
 */
class OwnerReportSheet
{
    public const COLUMNS = ['Booking Id', 'RES', 'Folio No.', 'First Name', 'Last Name', 'Listing Name', 'Arrival', 'Departure', 'Nights',
        'Reservation Source', 'Price per Night', 'Discount', 'Cleaning Fee', 'SST(CF)', 'OTA', 'SST', 'Ezee Folio No', 'Total', 'Remarks', 'Posted this month'];

    /** Rows for every live booking checking in within the month (status >= 3, as the export). */
    public static function rows(string $ym): array
    {
        $from = $ym . '-01';
        $to   = date('Y-m-t', strtotime($from));
        $out  = [];
        // lazyById pages by id; an extra orderBy() here made the pages overlap and skip (150 duplicates,
        // 81 missing on the first September run). Sort afterwards instead.
        $bookings = Booking::where('status', '>=', 3)->whereBetween('check_in', [$from, $to])
            ->with(['user', 'listing', 'ezeeBooking'])->lazyById(500);
        foreach ($bookings as $b) {
            $ezee = $b->ezeeBooking ?: self::siblingEzee($b);
            $otaText = Channel::canonical($ezee->Source ?? $b->source);
            $ezeeFolio = $ezee->folio_no ?? '';
            if ($ezeeFolio === '' && !empty($b->server_folio_no)) {
                $ezeeFolio = $b->server_folio_no;
            }
            $folio = $ezeeFolio !== '' ? $ezeeFolio : $b->folio_no;
            $first = trim((string) ($ezee->FirstName ?? '')) !== '' ? trim((string) $ezee->FirstName) : ($b->user->name ?? '');
            $last  = trim((string) ($ezee->FirstName ?? '')) !== '' ? trim((string) ($ezee->LastName ?? '')) : ($b->user->last_name ?? '');
            $out[] = [
                $b->id, $ezee->SubBookingId ?? '', $folio, $first, $last, $b->listing->name ?? '',
                \Carbon\Carbon::parse($b->check_in)->format('Y-m-d'), \Carbon\Carbon::parse($b->check_out)->format('Y-m-d'), (int) $b->nights,
                $otaText, (float) $b->price_night, (float) $b->discount_fee, (float) $b->cleaning_fee, (float) $b->sst_cf, (float) $b->ota_fee, (float) $b->sst,
                $ezeeFolio, round((float) $b->price, 2), self::remarksFor($b), self::postedInMonth($b, $to),
            ];
        }

        usort($out, fn ($a, $b) => [$a[6], $a[0]] <=> [$b[6], $b[0]]);

        return $out;
    }

    /**
     * The part of a booking's total posted within the month: eZee posts each night at
     * audit and the cleaning fee at check-in, so for a row whose stay runs past month
     * end only the nights up to the last night of the month count. Equals Total for
     * the usual row that ends by the first of next month.
     */
    public static function postedInMonth(Booking $b, string $monthEnd): float
    {
        $nights = (int) $b->nights;
        $lastNight = Carbon::parse($monthEnd);                       // the night of the 31st is posted on the 31st
        $out = Carbon::parse($b->check_out);
        if ($nights <= 0 || $out->lte($lastNight->copy()->addDay())) {
            return round((float) $b->price, 2);
        }
        $in = Carbon::parse($b->check_in);
        $nightsIn = max(0, min($nights, $in->diffInDays($lastNight->copy()->addDay())));
        $share = $nightsIn / $nights;
        $room = (float) $b->price_night * $nightsIn;
        $sst  = (float) $b->sst * $share;
        return round($room + $sst + (float) $b->cleaning_fee + (float) $b->sst_cf - (float) $b->discount_fee, 2);
    }

    /**
     * Group tabs for the engine, from Homemoka's group tables. Configured in
     * config moka.report_groups (tab name, Homemoka group, markers). A member is in the
     * month's tab when it is not archived at month end and has started renting, i.e. has
     * had at least one live booking by month end (Mesahill units signed but not yet let
     * stay out, as the engine expects). Returns [tab => rows], rows as the engine reads
     * them: marker on the first unit of each type, subtotal and grand-total rows.
     *
     * @param array<int, array> $mokaRows the Moka sheet rows, for the OTA and cleaning sums
     */
    public static function groupTabs(string $ym, array $mokaRows): array
    {
        $to  = date('Y-m-t', strtotime($ym . '-01'));
        $ota = [];
        $cf  = [];
        foreach ($mokaRows as $r) {
            $k = strtolower(str_replace(' ', '', (string) $r[5]));
            $ota[$k] = ($ota[$k] ?? 0) + (float) $r[14];
            $cf[$k]  = ($cf[$k] ?? 0) + (float) $r[12];
        }
        $started = Booking::where('status', '>=', 3)->where('check_in', '<=', $to)
            ->selectRaw('listing_id, min(check_in) first_in')->groupBy('listing_id')->pluck('first_in', 'listing_id');

        $tabs = [];
        foreach ((array) config('moka.report_groups', []) as $g) {
            $group = DB::table('groups')->where('name', 'like', $g['group'] . '%')->orderBy('id')->first();
            if (!$group) {
                continue;
            }
            $members = DB::table('listing_groups as lg')
                ->join('listings as l', 'l.id', '=', 'lg.listing_id')
                ->leftJoin('group_types as gt', 'gt.id', '=', 'lg.group_type_id')
                ->where('lg.group_id', $group->id)
                ->where(fn ($q) => $q->whereNull('l.archived_at')->orWhere('l.archived_at', '>', $to . ' 23:59:59'))
                ->orderBy('l.name')
                ->get(['l.id', 'l.name', 'gt.type']);
            $rows = [['Listing Name2', 'Listing Name', 'Sum of OTA', 'Sum of Cleaning Fee']];
            $grand = [0.0, 0.0];
            $byMarker = [];
            foreach ($members as $m) {
                if (!$started->has($m->id)) {
                    continue;   // signed, not started: no share of the pool this month
                }
                $marker = $g['marker'] ?? ($g['markers'][$m->type] ?? null);
                if ($marker === null) {
                    continue;   // a Bell Suites unit with no Studio/3BR type cannot be pooled by points
                }
                $byMarker[$marker][] = $m->name;
            }
            $order = isset($g['markers']) ? array_values($g['markers']) : [$g['marker']];
            foreach ($order as $marker) {
                $sub = [0.0, 0.0];
                foreach ($byMarker[$marker] ?? [] as $i => $name) {
                    $k = strtolower(str_replace(' ', '', $name));
                    $o = round($ota[$k] ?? 0, 2);
                    $c = round($cf[$k] ?? 0, 2);
                    $rows[] = [$i === 0 ? $marker : null, $name, $o, $c];
                    $sub[0] += $o;
                    $sub[1] += $c;
                }
                if (isset($g['markers']) && isset($byMarker[$marker])) {
                    $rows[] = [$marker . ' Total', null, round($sub[0], 2), round($sub[1], 2)];
                }
                $grand[0] += $sub[0];
                $grand[1] += $sub[1];
            }
            $rows[] = ['Grand Total', null, round($grand[0], 2), round($grand[1], 2)];
            $tabs[$g['tab']] = $rows;
        }

        return $tabs;
    }

    /** Write the workbook (Moka sheet + group tabs) to $path. Returns the booking row count. */
    public static function write(string $ym, string $path): int
    {
        $rows = self::rows($ym);
        $ss = new Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Moka');
        $ws->fromArray(array_merge([self::COLUMNS], $rows), null, 'A1', true);
        foreach (self::groupTabs($ym, $rows) as $tab => $tabRows) {
            $gs = $ss->createSheet();
            $gs->setTitle($tab);
            $gs->fromArray($tabRows, null, 'A1', true);
        }
        $ss->setActiveSheetIndex(0);
        // Dates as real dates so pandas parses them the way it parses the hand-made file.
        $n = count($rows);
        if ($n) {
            foreach (['G', 'H'] as $col) {
                for ($r = 2; $r <= $n + 1; $r++) {
                    $v = $ws->getCell($col . $r)->getValue();
                    if ($v) {
                        $ws->setCellValue($col . $r, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTime($v)));
                        $ws->getStyle($col . $r)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                    }
                }
            }
        }
        (new Xlsx($ss))->save($path);

        return $n;
    }

    private static function siblingEzee($booking): ?object
    {
        $folio = trim((string) $booking->folio_no);
        if ($folio === '' || !preg_match('/^FN\d+$/', $folio)) {
            return null;
        }

        return DB::table('ezee_bookings as e')
            ->join('bookings as s', 's.id', '=', 'e.book_id')
            ->where('e.status', '<>', 1)
            ->where('s.folio_no', $folio)
            ->where('s.id', '<>', $booking->id)
            ->where(function ($q) use ($booking) {
                $q->where('s.listing_id', $booking->listing_id)
                  ->orWhere('s.check_in', $booking->check_out)
                  ->orWhere('s.check_out', $booking->check_in);
            })
            ->orderBy('s.check_in')
            ->select('e.*')
            ->first();
    }

    private static function remarksFor($booking): string
    {
        $remark = trim((string) $booking->remark);
        $split  = (int) ($booking->is_split ?? 0) === 1 || preg_match('/split/i', $remark) === 1;
        if (!$split || preg_match('/^split booking/i', $remark) === 1) {
            return $remark;
        }

        return 'Split Booking' . ($remark !== '' ? ' | ' . $remark : '');
    }
}
