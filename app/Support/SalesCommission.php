<?php

namespace App\Support;

use App\OtherModel\EzeeBooking;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Sales commission from eZee's Transaction Detail Report (Back Office Report,
 * exported per property). The Sales Person column exists only in that report;
 * eZee's APIs never send it (checked against every documented endpoint, 29 Sep 2026).
 *
 * Basis: every posted "Room Charges" row that carries a Sales Person, net of SST,
 * on a folio that is Active or Closed and a booking that is not cancelled, void or
 * no-show. Commission = rate x net amount, counted in the month the night was posted.
 *
 * An upload replaces the stored rows of the same property inside the report's date
 * range, so overlapping weekly uploads never double count and the newest report
 * always wins: a shortened or cancelled stay simply has fewer or no rows next time.
 * Months already reported to owners are never touched (ground rule 27).
 */
class SalesCommission
{
    public const HOTELS = [
        '19676' => 'EkoCheras', '20317' => 'Bell Suites', '20318' => 'Forum / Damai 88',
        '20319' => 'Arte / Queensville / KL Gateway', '20320' => 'Alinea',
    ];

    private const NEEDED = ['Reservation #', 'Folio #', 'Arrival', 'Dept.', 'Room #', 'Guest Name', 'Business Source',
        'Sales Person Name', 'Booking Status', 'Transaction Date', 'Charge', 'Net Amount', 'Tax Amount', 'Gross Amount', 'Folio Status'];

    /** The month a month's commission is paid: with the following month's salary. */
    public static function payoutLabel(string $ym): string
    {
        return 'paid with the ' . date('F Y', strtotime($ym . '-01 +1 month')) . ' salary';
    }

    /** A month whose report has gone to owners is final (ground rule 27); later months are provisional. */
    public static function isFinal(string $ym): bool
    {
        return Lock::isLocked($ym . '-01');
    }

    /**
     * Every Sales Person name seen in uploads, with the staff login tied to it.
     * A new name is tied automatically when exactly one admin login is
     * name@homemoka.com; anything else is set by hand on the page.
     */
    public static function syncPersons(): void
    {
        $known = DB::table('sales_persons')->pluck('name')->all();
        $new   = DB::table('sales_transactions')->distinct()->whereNotIn('sales_person', $known ?: [''])->pluck('sales_person');
        foreach ($new as $name) {
            $email = strtolower(preg_replace('/[^a-z0-9]/i', '', $name)) . '@homemoka.com';
            $ids   = DB::table('users')->join('role_user', 'role_user.user_id', '=', 'users.id')->where('role_user.role_id', 1)
                ->whereRaw('LOWER(users.email) = ?', [$email])->pluck('users.id');
            DB::table('sales_persons')->insertOrIgnore(['name' => $name, 'user_id' => $ids->count() === 1 ? $ids->first() : null, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    /** The Sales Person name tied to a login, or null. */
    public static function personFor($user): ?string
    {
        return $user ? DB::table('sales_persons')->where('user_id', $user->id)->value('name') : null;
    }

    /** Month-by-month totals for the last N months (one person, or everyone). */
    public static function history(?string $person, int $months = 12): array
    {
        $from = date('Y-m-01', strtotime("-" . ($months - 1) . " months"));
        $q = DB::table('sales_transactions')->selectRaw("DATE_FORMAT(tran_date, '%Y-%m') ym, COUNT(DISTINCT CONCAT(hotel_code, '|', folio_no)) stays, COUNT(*) nights, SUM(net_amount) net")
            ->where('payable', 1)->where('tran_date', '>=', $from)->groupBy('ym')->orderByDesc('ym');
        if ($person) {
            $q->where('sales_person', $person);
        }
        $rate = self::rate();

        return $q->get()->map(function ($r) use ($rate) {
            $r->commission = round((float) $r->net * $rate, 2);
            $r->final      = self::isFinal($r->ym);
            $r->payout     = self::payoutLabel($r->ym);

            return $r;
        })->all();
    }

    public static function rate(): float
    {
        return (float) config('moka.sales_commission_rate', 0.02);
    }

    /** Read the report into plain rows keyed by header name. */
    public static function read(string $path): array
    {
        // The full report is 50+ columns; reading only the header row, then only the
        // columns needed, keeps a month's file inside PHP's memory limit.
        if ((int) ini_get('memory_limit') !== -1) {
            ini_set('memory_limit', '512M');
        }
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
            public array $cols = [];
            public function readCell($columnAddress, $row, $worksheetName = "") { return (int) $row === 1 || ($this->cols && in_array($columnAddress, $this->cols, true)); }
        });
        $filter = $reader->getReadFilter();
        $head   = array_map(fn ($h) => trim((string) $h), $reader->load($path)->getSheet(0)->toArray(null, true, false, false)[0] ?? []);
        foreach ($head as $i => $h) {
            if (in_array($h, self::NEEDED, true)) {
                $filter->cols[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            }
        }
        $rows = $reader->load($path)->getSheet(0)->toArray(null, true, false, false);
        array_shift($rows);
        $missing = array_diff(self::NEEDED, $head);
        if ($missing) {
            throw new \InvalidArgumentException('This does not look like eZee\'s Transaction Detail Report; missing columns: ' . implode(', ', $missing));
        }
        $ix  = array_flip($head);
        $out = [];
        foreach ($rows as $r) {
            $get = fn ($k) => isset($r[$ix[$k]]) ? trim((string) $r[$ix[$k]]) : '';
            if ($get('Folio #') === '' && $get('Reservation #') === '') {
                continue;
            }
            $out[] = [
                'res_no'          => $get('Reservation #') ?: null,
                'folio_no'        => $get('Folio #'),
                'guest_name'      => mb_substr($get('Guest Name'), 0, 160) ?: null,
                'business_source' => mb_substr($get('Business Source'), 0, 80) ?: null,
                'sales_person'    => mb_substr(trim($get('Sales Person Name')), 0, 80),
                'room_no'         => mb_substr($get('Room #'), 0, 80) ?: null,
                'arrival'         => self::date($get('Arrival')),
                'departure'       => self::date($get('Dept.')),
                'charge'          => mb_substr($get('Charge'), 0, 80),
                'tran_date'       => self::date($get('Transaction Date')),
                'net_amount'      => round((float) str_replace(',', '', $get('Net Amount')), 2),
                'tax_amount'      => round((float) str_replace(',', '', $get('Tax Amount')), 2),
                'gross_amount'    => round((float) str_replace(',', '', $get('Gross Amount')), 2),
                'booking_status'  => $get('Booking Status') ?: null,
                'folio_status'    => $get('Folio Status') ?: null,
            ];
        }

        return $out;
    }

    private static function date(string $v): ?string
    {
        if ($v === '') {
            return null;
        }
        if (is_numeric($v) && (float) $v > 20000) {   // an Excel serial date
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $v)->format('Y-m-d');
        }
        $t = strtotime($v);

        return $t ? date('Y-m-d', $t) : null;
    }

    /**
     * Which property the file belongs to: the report has no hotel code, so the
     * reservation + folio pairs are matched against the eZee records we hold.
     * Returns [code, matched, agreeing]; null code when the vote is not clear.
     */
    public static function detectHotel(array $rows): array
    {
        $pairs = [];
        foreach ($rows as $r) {
            if ($r['res_no'] && $r['folio_no']) {
                $pairs[$r['res_no'] . '|' . $r['folio_no']] = [$r['res_no'], $r['folio_no']];
            }
        }
        $pairs = array_slice(array_values($pairs), 0, 400);
        $votes = [];
        foreach (array_chunk($pairs, 100) as $chunk) {
            $q = EzeeBooking::query()->select('SubBookingId', 'folio_no', 'TransactionId');
            $q->where(function ($w) use ($chunk) {
                foreach ($chunk as [$res, $folio]) {
                    $w->orWhere(fn ($x) => $x->where('SubBookingId', $res)->where('folio_no', $folio));
                }
            });
            foreach ($q->get() as $eb) {
                $votes[substr((string) $eb->TransactionId, 0, 5)] = ($votes[substr((string) $eb->TransactionId, 0, 5)] ?? 0) + 1;
            }
        }
        arsort($votes);
        $matched = array_sum($votes);
        $top     = $votes ? array_key_first($votes) : null;
        $agree   = $top ? $votes[$top] : 0;
        $clear   = $top && isset(self::HOTELS[$top]) && $matched >= 5 && $agree / $matched >= 0.8;

        return [$clear ? $top : null, $matched, $agree];
    }

    /** Store one report. Returns the upload row. */
    public static function import(string $path, string $filename, ?string $hotel, ?int $userId): \stdClass
    {
        $rows = self::read($path);
        if (!$rows) {
            throw new \InvalidArgumentException('The file has no transaction rows.');
        }
        if (!$hotel) {
            [$hotel, $matched, $agree] = self::detectHotel($rows);
            if (!$hotel) {
                throw new \InvalidArgumentException("Could not tell which property this file is for ($agree of $matched matched reservations agree). Choose the property and upload again.");
            }
        }
        if (!isset(self::HOTELS[$hotel])) {
            throw new \InvalidArgumentException("Unknown property code $hotel.");
        }

        $dates = array_filter(array_column($rows, 'tran_date'));
        $from  = min($dates);
        $to    = max($dates);
        $cut   = Lock::cutoff()->toDateString();

        $keep = [];
        $lockedSkipped = 0;
        $seen = [];
        foreach ($rows as $r) {
            if ($r['sales_person'] === '' || !$r['tran_date'] || $r['charge'] === '') {
                continue;
            }
            if ($r['tran_date'] < $cut) {
                $lockedSkipped++;
                continue;
            }
            $key = md5(json_encode($r));       // an exact duplicate row is never counted twice
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $r['payable'] = self::isPayable($r);
            $keep[] = $r;
        }

        return DB::transaction(function () use ($hotel, $filename, $from, $to, $cut, $rows, $keep, $lockedSkipped, $userId) {
            $uploadId = DB::table('sales_report_uploads')->insertGetId([
                'hotel_code' => $hotel, 'filename' => mb_substr($filename, 0, 255), 'period_from' => $from, 'period_to' => $to,
                'rows_in_file' => count($rows), 'rows_stored' => count($keep), 'rows_skipped_locked' => $lockedSkipped,
                'uploaded_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
            ]);
            // The newest report for a property and period is the truth for that period.
            $replaced = DB::table('sales_transactions')->where('hotel_code', $hotel)
                ->whereBetween('tran_date', [max($from, $cut), $to])->delete();
            $now = now();
            foreach (array_chunk($keep, 500) as $chunk) {
                DB::table('sales_transactions')->insert(array_map(fn ($r) => $r + ['upload_id' => $uploadId, 'hotel_code' => $hotel, 'created_at' => $now, 'updated_at' => $now], $chunk));
            }
            DB::table('sales_report_uploads')->where('id', $uploadId)->update(['rows_replaced' => $replaced]);
            self::syncPersons();

            return DB::table('sales_report_uploads')->find($uploadId);
        });
    }

    /** A posted room night that earns commission. */
    public static function isPayable(array $r): bool
    {
        return $r['charge'] === 'Room Charges'
            && in_array((string) $r['folio_status'], ['Active', 'Close'], true)
            && !in_array((string) $r['booking_status'], ['Cancel', 'Void', 'No Show'], true)
            && (float) $r['net_amount'] > 0;
    }

    /**
     * Month summary: per sales person, the stays and nights, the room revenue and
     * the commission, plus live warnings from Homemoka's own bookings (a stay
     * cancelled or shortened in eZee since the last upload shows up here first).
     */
    public static function month(string $ym, ?string $person = null): array
    {
        $from = $ym . '-01';
        $to   = date('Y-m-t', strtotime($from));
        $q = DB::table('sales_transactions')->where('payable', 1)->whereBetween('tran_date', [$from, $to]);
        if ($person) {
            $q->where('sales_person', $person);
        }
        $rows = $q->orderBy('sales_person')->orderBy('hotel_code')->orderBy('folio_no')->orderBy('tran_date')->get();

        // One line per stay (property + folio).
        $stays = [];
        foreach ($rows as $r) {
            $k = $r->hotel_code . '|' . $r->folio_no;
            $s = &$stays[$k];
            if (!$s) {
                $s = (object) ['hotel_code' => $r->hotel_code, 'property' => self::HOTELS[$r->hotel_code] ?? $r->hotel_code, 'folio_no' => $r->folio_no, 'res_no' => $r->res_no,
                    'guest' => $r->guest_name, 'source' => $r->business_source, 'room' => $r->room_no, 'arrival' => $r->arrival, 'departure' => $r->departure,
                    'sales_person' => $r->sales_person, 'nights' => 0, 'first_night' => $r->tran_date, 'last_night' => $r->tran_date, 'net' => 0.0, 'status' => $r->booking_status, 'warn' => null];
            }
            $s->nights++;
            $s->net += (float) $r->net_amount;
            $s->last_night = max($s->last_night, $r->tran_date);
            $s->status = $r->booking_status;
            unset($s);
        }

        // Live check against Homemoka: the hourly eZee sync knows about cancellations
        // and shortened stays before the next report is uploaded.
        foreach ($stays as $s) {
            $eb = EzeeBooking::where('folio_no', $s->folio_no)->where('TransactionId', 'like', $s->hotel_code . '%')->orderByDesc('id')->first();
            if (!$eb) {
                continue;
            }
            $b = $eb->book_id ? DB::table('bookings')->where('id', $eb->book_id)->first() : null;
            if ((int) $eb->status === 1 || ($b && (int) $b->status === 1)) {
                $s->warn = 'Cancelled in Homemoka since this report. Re-upload the report to confirm before paying.';
            } elseif ($eb->End && $eb->End <= $s->last_night && (string) $eb->Start !== (string) $eb->End) {   // a day-use stay posts on its own date
                $s->warn = 'eZee now ends this stay on ' . $eb->End . ', before the last night posted. Re-upload the report before paying.';
            }
        }

        $rate = self::rate();
        $people = [];
        foreach ($stays as $s) {
            $s->commission = round($s->net * $rate, 2);
            $p = &$people[$s->sales_person];
            if (!$p) {
                $p = (object) ['name' => $s->sales_person, 'stays' => 0, 'nights' => 0, 'net' => 0.0, 'commission' => 0.0, 'warnings' => 0, 'held' => 0.0];
            }
            $p->stays++;
            $p->nights += $s->nights;
            $p->net += $s->net;
            if ($s->warn) {
                $p->warnings++;
                $p->held += $s->commission;
            } else {
                $p->commission += $s->commission;
            }
            unset($p);
        }
        ksort($people, SORT_NATURAL | SORT_FLAG_CASE);
        usort($stays, fn ($a, $b) => [$a->sales_person, $a->property, $a->arrival] <=> [$b->sales_person, $b->property, $b->arrival]);

        return ['people' => array_values($people), 'stays' => $stays, 'rate' => $rate, 'from' => $from, 'to' => $to];
    }
}
