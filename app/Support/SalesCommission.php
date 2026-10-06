<?php

namespace App\Support;

use App\OtherModel\EzeeBooking;
use App\Support\Channel;
use App\Support\EzeeUnitMap;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Sales commission from eZee's Transaction Detail Report (Back Office Report,
 * exported per property). The Sales Person column exists only in that report;
 * eZee's APIs never send it (checked against every documented endpoint, 29 Sep 2026).
 *
 * Basis: every posted room charge (plus early check-in / late check-out) row that carries a Sales Person, net of SST,
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

        $keep = [];      // open months: replace what is stored for the period
        $locked = [];    // reported months: stored once, never replaced (ground rule 27)
        $seen = [];
        foreach ($rows as $r) {
            if ($r['sales_person'] === '' || !$r['tran_date'] || $r['charge'] === '') {
                continue;
            }
            $key = md5(json_encode($r));       // an exact duplicate row is never counted twice
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $r['payable'] = self::isPayable($r);
            if ($r['tran_date'] < $cut) {
                $locked[] = $r;
            } else {
                $keep[] = $r;
            }
        }
        // A reported month keeps the figures it was paid on: only nights never stored
        // before are added (a first upload of an old month), nothing is changed or removed.
        $lockedSkipped = 0;
        if ($locked) {
            $have = DB::table('sales_transactions')->where('hotel_code', $hotel)->where('tran_date', '<', $cut)
                ->whereBetween('tran_date', [$from, $cut])->selectRaw("CONCAT(folio_no, '|', tran_date) k")->pluck('k')->flip();
            $locked = array_values(array_filter($locked, function ($r) use ($have, &$lockedSkipped) {
                if (isset($have[$r['folio_no'] . '|' . $r['tran_date']])) {
                    $lockedSkipped++;

                    return false;
                }

                return true;
            }));
            $keep = array_merge($keep, $locked);
        }

        return DB::transaction(function () use ($hotel, $filename, $from, $to, $cut, $rows, $keep, $lockedSkipped, $userId) {
            $clawed = 0;
            $uploadId = DB::table('sales_report_uploads')->insertGetId([
                'hotel_code' => $hotel, 'filename' => mb_substr($filename, 0, 255), 'period_from' => $from, 'period_to' => $to,
                'rows_in_file' => count($rows), 'rows_stored' => count($keep), 'rows_skipped_locked' => $lockedSkipped,
                'uploaded_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $clawed = self::clawbacks($hotel, $rows, $cut, $userId);
            // The newest report for a property and period is the truth for that period.
            $replaced = DB::table('sales_transactions')->where('hotel_code', $hotel)
                ->whereBetween('tran_date', [max($from, $cut), $to])->delete();
            $now = now();
            foreach (array_chunk($keep, 500) as $chunk) {
                DB::table('sales_transactions')->insert(array_map(fn ($r) => $r + ['upload_id' => $uploadId, 'hotel_code' => $hotel, 'created_at' => $now, 'updated_at' => $now], $chunk));
            }
            DB::table('sales_report_uploads')->where('id', $uploadId)->update(['rows_replaced' => $replaced, 'clawbacks' => $clawed, 'missing' => self::flagMissing($hotel, $rows, $cut)]);
            self::syncPersons();

            return DB::table('sales_report_uploads')->find($uploadId);
        });
    }

    /**
     * Rule 26: a stay keyed after night audit has a folio but no RES and never reaches
     * the sync; a stay the sync missed for any other reason looks the same from here.
     * Every folio in the report with posted room nights in an open month that Homemoka
     * has no record of gets a placeholder eZee row (status 7, never auto-assigned) and a
     * Needs Review item with the facts, so a person keys it or asks Claude to.
     */
    public static function flagMissing(string $hotel, array $rows, string $cut): int
    {
        $byFolio = [];
        foreach ($rows as $r) {
            // Extra Room folios are cancelled or shortened trips: nothing for staff to place.
            if ($r['charge'] !== 'Room Charges' || $r['folio_no'] === '' || !$r['tran_date'] || $r['tran_date'] < $cut || self::isExtraRoom($r['room_no'] ?? null)
                || in_array((string) $r['booking_status'], ['Cancel', 'Void', 'No Show'], true) || (string) $r['folio_status'] === 'Void' || (float) $r['net_amount'] <= 0) {
                continue;
            }
            $f = &$byFolio[$r['folio_no']];
            $f = $f ?: ['res' => $r['res_no'], 'guest' => $r['guest_name'], 'room' => $r['room_no'], 'arrival' => $r['arrival'], 'departure' => $r['departure'], 'first' => $r['tran_date'], 'last' => $r['tran_date'], 'nights' => 0, 'net' => 0.0, 'source' => $r['business_source']];
            $f['nights']++;
            $f['net'] += (float) $r['net_amount'];
            $f['first'] = min($f['first'], $r['tran_date']);
            $f['last']  = max($f['last'], $r['tran_date']);
            unset($f);
        }
        $n = 0;
        foreach ($byFolio as $folio => $f) {
            if (EzeeBooking::where('folio_no', $folio)->where('TransactionId', 'like', $hotel . '%')->exists()) {
                continue;
            }
            if ($f['res'] && EzeeBooking::where('SubBookingId', $f['res'])->where('TransactionId', 'like', $hotel . '%')->exists()) {
                continue;
            }
            if (DB::table('bookings')->join('listings', 'listings.id', '=', 'bookings.listing_id')->where('bookings.status', '<>', 1)
                ->where(fn ($q) => $q->where('bookings.folio_no', $folio)->orWhere('bookings.server_folio_no', $folio))
                ->where('listings.name', 'like', (self::HOTELS[$hotel] === 'Forum / Damai 88' ? 'Forum' : explode(' /', self::HOTELS[$hotel])[0]) . '%')->exists()) {
                continue;
            }
            $sub = $f['res'] ?: 'NORES-' . $folio;
            $tx  = $hotel . 'NORES' . preg_replace('/\D/', '', $folio);
            $eb  = EzeeBooking::where('TransactionId', $tx)->first();
            if (!$eb) {
                [$first, $last] = array_pad(explode(' ', trim((string) $f['guest']), 2), 2, '');
                $eb = EzeeBooking::create(['SubBookingId' => $sub, 'TransactionId' => $tx, 'folio_no' => $folio, 'FirstName' => $first, 'LastName' => $last, 'RoomName' => $f['room'],
                    'Start' => $f['arrival'] ?: $f['first'], 'End' => $f['departure'] ?: date('Y-m-d', strtotime($f['last'] . ' +1 day')), 'Source' => $f['source'],
                    'TotalAmountBeforeTax' => round($f['net'], 2), 'TotalAmountAfterTax' => round($f['net'] * 1.08, 2), 'TotalExtraCharge' => 0, 'CurrencyCode' => 'MYR', 'status' => 7, 'ezee_current_status' => 'From report', 'IsConfirmed' => 1]);
            }
            $listing = EzeeUnitMap::make()->resolve($eb);
            if (!$listing || DB::table('ezee_assignment_logs')->where('ezee_booking_id', $eb->id)->where('method', 'conflict')->whereNull('resolved_at')->exists()) {
                continue;
            }
            DB::table('ezee_assignment_logs')->insert(['ezee_booking_id' => $eb->id, 'listing_id' => $listing->id, 'old_listing_id' => null, 'assigned_by' => null, 'method' => 'conflict',
                'note' => sprintf('In eZee\'s Transaction Detail Report but not in Homemoka: folio %s%s, %s, %s, %d night(s) %s to %s, RM %.2f room charges, %s. %s Key it by hand on Bookings (rule 26) or ask Claude, then Mark done.',
                    $folio, $f['res'] ? ' / ' . $f['res'] : ' (no RES)', $f['guest'] ?: 'guest unknown', $f['room'] ?: 'room unknown', $f['nights'], $f['first'], $f['last'], $f['net'], $f['source'] ?: 'source unknown',
                    $f['res'] ? 'The sync never received it.' : 'Keyed after night audit, so the sync cannot see it.'),
                'created_at' => now(), 'updated_at' => now()]);
            $n++;
        }

        return $n;
    }

    /** A posted room night that earns commission. */
    /** Charges that earn commission (Sam, 6 Oct 2026): room charges plus early check-in and late check-out. Cleaning fees, deposits, damage, OTA channel fee and other charges do not. */
    public const COMMISSIONABLE = ['Room Charges', 'Early Check In', 'Late Check Out'];

    /** Stays parked on an "Extra Room" are cancelled or shortened trips (Sam, 6 Oct 2026): no commission. */
    public static function isExtraRoom(?string $room): bool
    {
        return (bool) preg_match('/extra\s*room/i', (string) $room);
    }

    public static function isPayable(array $r): bool
    {
        return in_array($r['charge'], self::COMMISSIONABLE, true)
            && !self::isExtraRoom($r['room_no'] ?? null)
            && self::isDirectSource($r['business_source'] ?? null)
            && in_array((string) $r['folio_status'], ['Active', 'Close'], true)
            && !in_array((string) $r['booking_status'], ['Cancel', 'Void', 'No Show'], true)
            && (float) $r['net_amount'] > 0;
    }

    /** SOP §2: OTA and agent bookings earn no commission even if a sales person is tagged. */
    public static function isDirectSource(?string $source): bool
    {
        $c = Channel::canonical($source);

        return !in_array($c, [Channel::BOOKING, Channel::AGODA, Channel::EXPEDIA, Channel::AIRBNB, Channel::TRIP, Channel::TRAVELOKA, Channel::TIKET], true);
    }

    /**
     * SOP §12: a night already paid (its month is final) that the new report now shows
     * cancelled, voided or no-show is clawed back from the next open month, once.
     */
    private static function clawbacks(string $hotel, array $rows, string $cut, ?int $userId): int
    {
        $dead = [];
        foreach ($rows as $r) {
            if ($r['tran_date'] < $cut && $r['charge'] === 'Room Charges' && $r['sales_person'] !== ''
                && (in_array((string) $r['booking_status'], ['Cancel', 'Void', 'No Show'], true) || (string) $r['folio_status'] === 'Void')) {
                $dead[$r['folio_no'] . '|' . $r['tran_date']] = $r;
            }
        }
        if (!$dead) {
            return 0;
        }
        $rate  = self::rate();
        $month = max($cut, date('Y-m-01'));
        $ym    = substr($month, 0, 7);
        $n     = 0;
        $paid  = DB::table('sales_transactions')->where('hotel_code', $hotel)->where('payable', 1)->whereNull('clawed_back_at')
            ->where('tran_date', '<', $cut)->whereIn(DB::raw("CONCAT(folio_no, '|', tran_date)"), array_keys($dead))->get();
        foreach ($paid as $t) {
            $pid = DB::table('sales_persons')->where('name', $t->sales_person)->value('id');
            if (!$pid) {
                continue;
            }
            DB::table('sales_adjustments')->insert(['sales_person_id' => $pid, 'ym' => $ym, 'amount' => -round((float) $t->net_amount * $rate, 2), 'kind' => 'clawback',
                'reason' => sprintf('Clawback (SOP §12): %s %s night of %s now %s in eZee; commission paid for %s recovered', $t->res_no ?: 'no RES', $t->folio_no, $t->tran_date,
                    $dead[$t->folio_no . '|' . $t->tran_date]['booking_status'] ?: 'void', date('M Y', strtotime($t->tran_date))),
                'created_by' => $userId, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('sales_transactions')->where('id', $t->id)->update(['clawed_back_at' => now()]);
            $n++;
        }

        return $n;
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

        // Cross-month stays: the nights posted in other months, so a person can see
        // what was paid last month and what follows next month for the same stay.
        $rate = self::rate();
        $keys = array_keys($stays);
        if ($keys) {
            $other = DB::table('sales_transactions')->selectRaw("hotel_code, folio_no, DATE_FORMAT(tran_date, '%Y-%m') ym, COUNT(*) nights, SUM(net_amount) net")
                ->where('payable', 1)->where(fn ($w) => $w->where('tran_date', '<', $from)->orWhere('tran_date', '>', $to))
                ->where(function ($w) use ($stays) {
                    foreach ($stays as $s) {
                        $w->orWhere(fn ($x) => $x->where('hotel_code', $s->hotel_code)->where('folio_no', $s->folio_no));
                    }
                })->groupBy('hotel_code', 'folio_no', 'ym')->orderBy('ym')->get();
            foreach ($other as $o) {
                $k = $o->hotel_code . '|' . $o->folio_no;
                if (isset($stays[$k])) {
                    $stays[$k]->other_months[] = (object) ['ym' => $o->ym, 'nights' => (int) $o->nights, 'net' => (float) $o->net, 'commission' => round((float) $o->net * $rate, 2), 'before' => $o->ym < substr($from, 0, 7)];
                }
            }
        }
        $people = [];
        foreach ($stays as $s) {
            $s->other_months = $s->other_months ?? [];
            $s->cross = $s->other_months || ($s->arrival && $s->arrival < $from) || ($s->departure && $s->departure > date('Y-m-d', strtotime($to . ' +1 day')));
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
