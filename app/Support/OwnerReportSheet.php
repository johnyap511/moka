<?php

namespace App\Support;

use App\Booking;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Owner reports phase 2 (11 Oct 2026): Homemoka builds the "Moka" bookings sheet the
 * report engine reads, instead of staff uploading a Reconciled_FINAL.xlsx. Same 19
 * columns and the same values as the Bookings export (BookingController@exportExcelRange),
 * one row per booking with check-in in the month, header on row 1, sheet named "Moka".
 * The export itself is left untouched (month-end rule: never change the old export).
 */
class OwnerReportSheet
{
    public const COLUMNS = ['Booking Id', 'RES', 'Folio No.', 'First Name', 'Last Name', 'Listing Name', 'Arrival', 'Departure', 'Nights',
        'Reservation Source', 'Price per Night', 'Discount', 'Cleaning Fee', 'SST(CF)', 'OTA', 'SST', 'Ezee Folio No', 'Total', 'Remarks'];

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
                $ezeeFolio, round((float) $b->price, 2), self::remarksFor($b),
            ];
        }

        usort($out, fn ($a, $b) => [$a[6], $a[0]] <=> [$b[6], $b[0]]);

        return $out;
    }

    /** Write the sheet to $path. Returns the row count. */
    public static function write(string $ym, string $path): int
    {
        $rows = self::rows($ym);
        $ss = new Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Moka');
        $ws->fromArray(array_merge([self::COLUMNS], $rows), null, 'A1', true);
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
