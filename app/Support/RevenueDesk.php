<?php

namespace App\Support;

use App\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Revenue desk (Sam, 25 Nov 2026): the forward view for the revenue manager.
 *
 *  - demand(): each night of the next 90 days per building, how full the short-stay
 *    units already are against what is normal that many days ahead, which nights are
 *    filling early (a spike), a suggested rate change, and the holidays coming up.
 *  - ahead(): which buildings and units are booked far ahead, and how far ahead guests book.
 *  - buildings(): demand by building and room type, for Business Development.
 *
 * "Normal" is learnt from the last eight weeks: for every past night, how many of the
 * short-stay units had been booked by 1, 2, 3 … days before. Booking times are reliable
 * from late September 2026, when eZee reservations began arriving hourly.
 *
 * A stay of 21 nights or more is a long stay: that unit is not for sale those nights,
 * so it is left out of both sides of the short-stay percentage.
 *
 * Reads only; nothing here changes a booking. Rate suggestions are simple rules for a
 * person to judge: Homemoka does not know the rate on sale today in eZee.
 */
class RevenueDesk
{
    public const DAYS       = 90;   // nights ahead
    public const HISTORY    = 56;   // nights back used to learn what is normal
    public const LONG_STAY  = 21;   // nights at which a stay is a long stay
    public const MIN_UNITS  = 3;    // a building needs this many short-stay units for sale before a night is rated
    public const HOT        = 25;   // points above normal: a spike
    public const WARM       = 12;   // points above normal: filling early
    public const SOFT       = -12;  // points below normal, inside SOFT_DAYS: slow
    public const SOFT_DAYS  = 14;
    public const NEAR_FULL  = 80;   // % of short-stay units sold
    public const HOLIDAY_UP = 10;   // starting rate rise for a public holiday night or its eve, before it fills
    public const EVENT_UP   = ['stadium' => 20, 'convocation' => 15, 'arena' => 0, 'expo' => 0, 'festival' => 0, 'graduation' => 0];   // starting rise near an event; smaller events are only watched until the bookings show a lift
    public const LONG_WEEKEND_UP = 15;

    /** Shared base: trading units, and per building and night what is sold, on long stay, picked up and at what rate. */
    public const CACHE_SECONDS = 300;   // bookings arrive from eZee hourly, so five minutes loses nothing

    private static array $memo = [];

    /**
     * The figures for one tab ('push', 'demand', 'ahead', 'buildings'), worked out at most
     * once every five minutes and shared by everyone who opens the page. Returns
     * ['at' => when they were worked out, 'data' => the figures]. The key carries the date
     * and this code's own timestamp, so a new day or a deploy starts fresh.
     */
    public static function cached(string $view, ?string $arg = null): array
    {
        $stamp = filemtime(__FILE__) . '-' . filemtime(__DIR__ . '/UnitsToPush.php') . '-' . @filemtime(config_path('holidays.php')) . '-' . @filemtime(config_path('events.php'));
        $key = 'revenue-desk:' . $view . ':' . md5((string) $arg) . ':' . now()->toDateString() . ':' . $stamp;
        $make = fn () => ['at' => now(), 'data' => $view === 'push' ? UnitsToPush::build() : ($view === 'outlook' ? self::outlook($arg) : self::$view())];
        try {
            return Cache::remember($key, self::CACHE_SECONDS, $make);
        } catch (\Throwable $e) {
            report($e);   // a cache that cannot be written must not take the page down

            return $make();
        }
    }

    private static function base(?Carbon $today = null): array
    {
        $today = ($today ?: now())->copy()->startOfDay();
        if (isset(self::$memo[$today->toDateString()])) {
            return self::$memo[$today->toDateString()];   // one page may ask for two views
        }
        $from  = $today->copy()->subDays(self::HISTORY);
        $toEx  = $today->copy()->addDays(self::DAYS);

        $push  = UnitsToPush::build($today);
        $units = collect($push['units'])->where('status', '<>', 'idle')->keyBy('id');   // trading units only
        $ids   = $units->keys()->all();

        $rows = Booking::whereIn('listing_id', $ids)->where('status', '>=', 5)
            ->where('check_out', '>', $from->toDateString())->where('check_in', '<', $toEx->copy()->addDays(400)->toDateString())
            ->get(['id', 'listing_id', 'folio_no', 'check_in', 'check_out', 'price_night', 'created_at']);

        // A stay split into month parts is one stay: same unit and folio.
        $fam = [];
        foreach ($rows as $b) {
            $k = $b->listing_id . '|' . ($b->folio_no ?: 'b' . $b->id);
            $n = max(0, (int) Carbon::parse($b->check_in)->diffInDays(Carbon::parse($b->check_out)));
            $f = &$fam[$k];
            $f['nights'] = ($f['nights'] ?? 0) + $n;
            $f['booked'] = min($f['booked'] ?? '9999', substr((string) $b->created_at, 0, 10));
            $f['in']     = min($f['in'] ?? '9999', (string) $b->check_in);
            $f['out']    = max($f['out'] ?? '0000', (string) $b->check_out);
            unset($f);
        }

        $fromS = $from->toDateString(); $toS = $toEx->toDateString(); $todayS = $today->toDateString();
        $weekAgo = $today->copy()->subDays(7)->toDateString();
        $night = [];   // [property][date] => sold, long, rate (sum), pickup
        $unit  = [];   // [listing] => nights by date: 's' short, 'l' long; and the long stay's end
        $leads = [];   // [property][weekend 0/1][days ahead] => short nights booked that far ahead (history)
        $stays = [];   // short stays that checked in during the history window: lead in days, per unit
        foreach ($rows as $b) {
            $k = $b->listing_id . '|' . ($b->folio_no ?: 'b' . $b->id);
            $f = $fam[$k]; $long = $f['nights'] >= self::LONG_STAY;
            $p = $units[$b->listing_id]['property'];
            $s = max((string) $b->check_in, $fromS); $e = min((string) $b->check_out, $toS);
            for ($d = Carbon::parse($s); $d->toDateString() < $e; $d->addDay()) {
                $k2 = $d->toDateString();
                $unit[$b->listing_id][$k2] = $long ? 'l' : 's';
                $c = &$night[$p][$k2];
                if ($long) {
                    $c['long'] = ($c['long'] ?? 0) + 1;
                    $c['lrate'] = ($c['lrate'] ?? 0) + (float) $b->price_night;
                } else {
                    $c['sold'] = ($c['sold'] ?? 0) + 1;
                    $c['rate'] = ($c['rate'] ?? 0) + (float) $b->price_night;
                    if ($f['booked'] >= $weekAgo) {
                        $c['pickup'] = ($c['pickup'] ?? 0) + 1;
                    }
                    if ($k2 < $todayS) {
                        $lead = max(0, (int) Carbon::parse($f['booked'])->diffInDays($d, false));
                        $we = in_array($d->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY], true) ? 1 : 0;
                        $leads[$p][$we][min($lead, self::DAYS)] = ($leads[$p][$we][min($lead, self::DAYS)] ?? 0) + 1;
                    }
                }
                unset($c);
            }
            if ($long && $f['out'] > $todayS && $f['in'] <= $toS) {
                $unit[$b->listing_id]['until'] = max($unit[$b->listing_id]['until'] ?? '', $f['out']);
            }
            if (!$long && $b->check_in === $f['in'] && $f['in'] >= $fromS && $f['in'] < $todayS) {
                $stays[$b->listing_id][] = max(0, (int) Carbon::parse($f['booked'])->diffInDays(Carbon::parse($f['in']), false));
            }
            $unit[$b->listing_id]['last'] = max($unit[$b->listing_id]['last'] ?? '', min($f['out'], '9999'));
        }

        return self::$memo[$today->toDateString()] = compact('today', 'from', 'toEx', 'units', 'night', 'unit', 'leads', 'stays', 'push');
    }

    /** Units for sale on short stay in a building on a night: trading units less those on a long stay. */
    private static function forSale(array $night, array $count, string $p, string $date): int
    {
        return max(0, ($count[$p] ?? 0) - ($night[$p][$date]['long'] ?? 0));
    }

    /**
     * What is normal: for a building, the share of short-stay units already sold N days
     * before the night, weekends (Friday, Saturday) apart from other nights. A building
     * with too few units borrows the whole portfolio's curve.
     */
    private static function curves(array $b, array $count): array
    {
        $today = $b['today']; $cap = [];   // [property][weekend] => unit-nights that were for sale in the history window
        for ($d = $b['from']->copy(); $d->lt($today); $d->addDay()) {
            $we = in_array($d->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY], true) ? 1 : 0;
            foreach ($count as $p => $n) {
                $cap[$p][$we] = ($cap[$p][$we] ?? 0) + self::forSale($b['night'], $count, $p, $d->toDateString());
            }
        }
        $curve = []; $all = []; $allCap = [0 => 0, 1 => 0];
        foreach ($count as $p => $n) {
            foreach ([0, 1] as $we) {
                $allCap[$we] += $cap[$p][$we] ?? 0;
                $run = 0;
                for ($l = self::DAYS; $l >= 0; $l--) {
                    $run += $b['leads'][$p][$we][$l] ?? 0;
                    $all[$we][$l] = ($all[$we][$l] ?? 0) + ($b['leads'][$p][$we][$l] ?? 0);
                    $curve[$p][$we][$l] = ($cap[$p][$we] ?? 0) > 0 ? $run / $cap[$p][$we] * 100 : 0;
                }
            }
        }
        // Today is not over: bookings still to come today belong to this lead. So the fair
        // figure for "now" sits halfway between this many days ahead and one day more.
        $mid = function (array $c) {
            $o = [];
            for ($l = 0; $l <= self::DAYS; $l++) {
                $o[$l] = ($c[$l] + ($c[min($l + 1, self::DAYS)] ?? $c[$l])) / 2;
            }
            return $o;
        };
        foreach ([0, 1] as $we) {
            $run = 0;
            for ($l = self::DAYS; $l >= 0; $l--) {
                $run += $all[$we][$l] ?? 0;
                $curve['*'][$we][$l] = $allCap[$we] > 0 ? $run / $allCap[$we] * 100 : 0;
            }
        }
        foreach ($count as $p => $n) {
            if ($n < 5) {
                $curve[$p] = $curve['*'];
            }
        }
        foreach ($curve as $p => $byWe) {
            foreach ($byWe as $we => $c) {
                $curve[$p][$we] = $mid($c);
            }
        }

        return $curve;
    }

    /** Public holidays, their eves and school-holiday seasons, keyed by date. */
    public static function holidays(Carbon $from, Carbon $toEx): array
    {
        $out = []; $f = $from->toDateString(); $t = $toEx->toDateString();
        $put = function (string $date, array $h) use (&$out, $f, $t) {
            if ($date >= $f && $date < $t) {
                $out[$date][] = $h;
            }
        };
        $public = collect(config('holidays.public', []))->keyBy('date');
        foreach ($public as $date => $h) {
            $put($date, ['kind' => 'public', 'name' => $h['name'], 'confirm' => !empty($h['confirm'])]);
            $eve = Carbon::parse($date)->subDay()->toDateString();
            if (!$public->has($eve)) {   // day 2 of a festival is not an "eve"
                $put($eve, ['kind' => 'eve', 'name' => self::eveName($h['name']), 'confirm' => !empty($h['confirm'])]);
            }
        }
        foreach (config('holidays.eves', []) as $h) {
            $put($h['date'], ['kind' => 'eve', 'name' => $h['name'], 'confirm' => !empty($h['confirm'])]);
        }
        foreach (config('holidays.seasons', []) as $s) {
            for ($d = Carbon::parse(max($s['from'], $f)); $d->toDateString() <= $s['to'] && $d->toDateString() < $t; $d->addDay()) {
                $out[$d->toDateString()][] = ['kind' => 'season', 'name' => $s['name'], 'confirm' => false];
            }
        }

        return $out;
    }

    /**
     * Big events from config/events.php that fall inside the window, each with the buildings
     * near its venue, and a lookup of event names by night and building.
     *
     * @return array{0: array<int,array>, 1: array<string,array<string,array<int,string>>>, 2: array<string,array<int,string>>}
     */
    public static function events(Carbon $from, Carbon $toEx): array
    {
        $f = $from->toDateString(); $t = $toEx->toDateString(); $areas = config('events.areas', []);
        $list = []; $byNight = []; $byDate = []; $big = [];
        foreach (config('events.events', []) as $e) {
            $to = $e['to'] ?? $e['from'];
            if ($to < $f || $e['from'] >= $t) {
                continue;
            }
            $near = $areas[$e['area'] ?? ''] ?? [];
            $nights = [];
            for ($d = Carbon::parse(max($e['from'], $f)); $d->toDateString() <= $to && $d->toDateString() < $t; $d->addDay()) {
                $k = $d->toDateString(); $nights[] = $k;
                $byDate[$k][] = $e['name'];
                if (empty($e['tentative']) && (self::EVENT_UP[$e['size'] ?? ''] ?? 0) > 0) {
                    $big[$k] = true;   // a confirmed stadium event or convocation
                }
                foreach ($near as $p) {
                    $byNight[$k][$p][] = $e['name'];
                }
            }
            $list[] = $e + ['to' => $to, 'near' => $near, 'nights' => $nights];
        }
        usort($list, fn ($a, $b) => $a['from'] <=> $b['from']);

        return [$list, $byNight, $byDate, $big];
    }

    private static function eveName(string $holiday): string
    {
        return ['Christmas Day' => 'Christmas Eve', "New Year's Day" => "New Year's Eve"][$holiday] ?? 'Eve of ' . $holiday;
    }

    /** The suggested rate change for one night, in %, from how far above normal and how full it is. 0 = leave it. */
    public static function suggest(float $pct, float $diff, int $lead): int
    {
        $up = 0;
        if ($diff >= self::WARM) { $up = 10; }
        if ($diff >= self::HOT) { $up = 15; }
        if ($diff >= 40) { $up = 20; }
        if ($pct >= self::NEAR_FULL) { $up = max($up, 20); }
        if ($pct >= 90) { $up = 25; }
        if ($up === 0 && $lead >= 1 && $lead <= self::SOFT_DAYS && $diff <= self::SOFT) {
            return -10;
        }

        return $up;
    }

    public static function demand(?Carbon $today = null): array
    {
        $b = self::base($today); $today = $b['today'];
        $count = $b['units']->countBy('property')->all();
        $curve = self::curves($b, $count);
        $hol   = self::holidays($today, $b['toEx']);
        [$evList, $evNight, $evDate, $evBig] = self::events($today, $b['toEx']);
        $props = collect($count)->sortDesc()->keys()->all();

        $dates = []; $cells = []; $total = [];
        for ($i = 0; $i < self::DAYS; $i++) {
            $d = $today->copy()->addDays($i); $k = $d->toDateString();
            $we = in_array($d->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY], true) ? 1 : 0;
            $dates[] = ['date' => $k, 'day' => $d, 'lead' => $i, 'we' => $we, 'hol' => $hol[$k] ?? [], 'ev' => array_values(array_unique($evDate[$k] ?? [])), 'evBig' => !empty($evBig[$k])];
            $tSold = 0; $tSale = 0; $tRate = 0; $tPick = 0; $tExp = 0;
            foreach ($props as $p) {
                $sale = self::forSale($b['night'], $count, $p, $k);
                $n    = $b['night'][$p][$k] ?? [];
                $sold = min($sale, $n['sold'] ?? 0);
                $pct  = $sale > 0 ? $sold / $sale * 100 : 0;
                $exp  = $curve[$p][$we][$i] ?? 0;
                $rated = $sale >= self::MIN_UNITS;
                $diff = $rated ? $pct - $exp : 0;
                $level = !$rated ? 'na' : (($diff >= self::HOT || ($pct >= self::NEAR_FULL && $i >= 3)) ? 'hot' : ($diff >= self::WARM ? 'warm' : ($i >= 1 && $i <= self::SOFT_DAYS && $diff <= self::SOFT ? 'soft' : 'ok')));
                $cells[$p][$k] = [
                    'sale' => $sale, 'sold' => $sold, 'long' => $n['long'] ?? 0, 'pct' => (int) round($pct), 'normal' => (int) round($exp), 'diff' => (int) round($diff),
                    'pickup' => $n['pickup'] ?? 0, 'rate' => ($n['sold'] ?? 0) > 0 ? round($n['rate'] / $n['sold']) : null,
                    'level' => $level, 'suggest' => $rated ? self::suggest($pct, $diff, $i) : 0,
                ];
                $tSold += $sold; $tSale += $sale; $tRate += $n['rate'] ?? 0; $tPick += $n['pickup'] ?? 0; $tExp += $exp * $sale;
            }
            $pct = $tSale > 0 ? $tSold / $tSale * 100 : 0; $exp = $tSale > 0 ? $tExp / $tSale : 0; $diff = $pct - $exp;
            $total[$k] = [
                'sale' => $tSale, 'sold' => $tSold, 'pct' => (int) round($pct), 'normal' => (int) round($exp), 'diff' => (int) round($diff), 'pickup' => $tPick,
                'rate' => $tSold > 0 ? round($tRate / $tSold) : null,
                'level' => ($diff >= self::HOT || ($pct >= self::NEAR_FULL && $i >= 3)) ? 'hot' : ($diff >= self::WARM ? 'warm' : ($i >= 1 && $i <= self::SOFT_DAYS && $diff <= self::SOFT ? 'soft' : 'ok')),
                'suggest' => self::suggest($pct, $diff, $i),
            ];
        }

        // Nights to act on: runs of spike or early-filling nights per building, merged into one line each.
        $act = [];
        foreach ($props as $p) {
            $run = null;
            foreach ($dates as $dt) {
                $c = $cells[$p][$dt['date']];
                $on = in_array($c['level'], ['hot', 'warm'], true);
                $ev = $evNight[$dt['date']][$p] ?? [];
                if ($on && $run && $run['level'] === $c['level']) {
                    $run['to'] = $dt['day']; $run['cells'][] = $c; $run['hol'] = array_merge($run['hol'], $dt['hol']); $run['ev'] = array_merge($run['ev'], $ev);
                } else {
                    if ($run) { $act[] = $run; }
                    $run = $on ? ['property' => $p, 'level' => $c['level'], 'from' => $dt['day'], 'to' => $dt['day'], 'lead' => $dt['lead'], 'cells' => [$c], 'hol' => $dt['hol'], 'ev' => $ev] : null;
                }
            }
            if ($run) { $act[] = $run; }
        }
        $act = array_map(function ($r) {
            $c = collect($r['cells']);
            $names = collect($r['hol'])->whereIn('kind', ['public', 'eve'])->pluck('name')->unique()->values()->all();
            return [
                'property' => $r['property'], 'level' => $r['level'], 'from' => $r['from'], 'to' => $r['to'], 'lead' => $r['lead'], 'nights' => $c->count(),
                'pct' => (int) round($c->avg('pct')), 'normal' => (int) round($c->avg('normal')), 'left' => (int) $c->sum(fn ($x) => $x['sale'] - $x['sold']),
                'pickup' => (int) $c->sum('pickup'), 'rate' => $c->whereNotNull('rate')->avg('rate') ? (int) round($c->whereNotNull('rate')->avg('rate')) : null,
                'suggest' => (int) $c->max('suggest'), 'holidays' => $names, 'events' => array_values(array_unique($r['ev'])),
            ];
        }, $act);
        usort($act, fn ($x, $y) => [$x['from']->toDateString(), $x['level'] === 'hot' ? 0 : 1, $x['property']] <=> [$y['from']->toDateString(), $y['level'] === 'hot' ? 0 : 1, $y['property']]);

        // Slow nights close in, whole portfolio: where a promotion helps.
        $soft = collect($dates)->filter(fn ($dt) => $total[$dt['date']]['level'] === 'soft')->map(fn ($dt) => ['day' => $dt['day']] + $total[$dt['date']])->values()->all();

        // Holidays coming up: each public holiday with its eve, and how the books look for those nights.
        $watch = [];
        foreach ($dates as $dt) {
            foreach ($dt['hol'] as $h) {
                if ($h['kind'] === 'season') { continue; }
                $t = $total[$dt['date']];
                $top = collect($props)->map(fn ($p) => ['property' => $p] + $cells[$p][$dt['date']])->where('level', '<>', 'na')->sortByDesc('pct')->first();
                $dow = $dt['day']->dayOfWeek;
                $longWeekend = $h['kind'] === 'public' && in_array($dow, [Carbon::FRIDAY, Carbon::MONDAY], true);
                // a holiday night gets a starting rise even before it fills; a spike can only raise it further
                $t['suggest'] = max($t['suggest'], $longWeekend ? self::LONG_WEEKEND_UP : self::HOLIDAY_UP);
                $watch[] = [
                    'day' => $dt['day'], 'lead' => $dt['lead'], 'name' => $h['name'], 'kind' => $h['kind'], 'confirm' => $h['confirm'],
                    'longWeekend' => $longWeekend,
                    'pct' => $t['pct'], 'normal' => $t['normal'], 'diff' => $t['diff'], 'pickup' => $t['pickup'], 'suggest' => $t['suggest'], 'level' => $t['level'],
                    'top' => $top ? $top['property'] . ' ' . $top['pct'] . '%' : null,
                ];
            }
        }
        // Events coming up: for each, how the buildings near the venue are selling on the event nights.
        $events = [];
        foreach ($evList as $e) {
            $near = array_values(array_intersect($e['near'], $props));
            $rows = [];
            foreach ($near as $p) {
                $c = collect($e['nights'])->map(fn ($k) => $cells[$p][$k] ?? null)->filter()->where('level', '<>', 'na');
                if ($c->count()) {
                    $rows[] = ['property' => $p, 'pct' => (int) round($c->avg('pct')), 'normal' => (int) round($c->avg('normal')), 'left' => (int) $c->sum(fn ($x) => $x['sale'] - $x['sold']), 'suggest' => (int) $c->max('suggest')];
                }
            }
            $floor = empty($e['tentative']) ? (self::EVENT_UP[$e['size'] ?? ''] ?? 0) : 0;   // an unconfirmed date is watched, not priced
            $events[] = [
                'name' => $e['name'], 'venue' => $e['venue'] ?? '', 'area' => $e['area'] ?? '', 'size' => $e['size'] ?? '', 'tentative' => !empty($e['tentative']), 'url' => $e['url'] ?? null,
                'from' => Carbon::parse($e['from']), 'to' => Carbon::parse($e['to']), 'lead' => max(0, (int) $today->diffInDays(Carbon::parse($e['from']), false)),
                'near' => $rows, 'suggest' => $rows ? max($floor, (int) collect($rows)->max('suggest')) : 0, 'floor' => $floor,
                'filling' => (bool) collect($rows)->first(fn ($r) => $r['pct'] - $r['normal'] >= self::WARM),
            ];
        }

        $seasons = collect(config('holidays.seasons', []))->filter(fn ($s) => $s['to'] >= $today->toDateString() && $s['from'] < $b['toEx']->toDateString())
            ->map(fn ($s) => ['name' => $s['name'], 'from' => Carbon::parse($s['from']), 'to' => Carbon::parse($s['to'])])->values()->all();

        $next = fn (int $n) => collect($dates)->take($n)->reduce(fn ($c, $dt) => [$c[0] + $total[$dt['date']]['sold'], $c[1] + $total[$dt['date']]['sale'], $c[2] + $total[$dt['date']]['pickup']], [0, 0, 0]);
        [$s30, $c30, $p30] = $next(30);

        return [
            'today' => $today, 'props' => $props, 'count' => $count, 'dates' => $dates, 'cells' => $cells, 'total' => $total,
            'act' => $act, 'soft' => $soft, 'watch' => $watch, 'seasons' => $seasons, 'events' => $events,
            'kpi' => [
                'hot' => collect($act)->where('level', 'hot')->count(), 'warm' => collect($act)->where('level', 'warm')->count(),
                'pct30' => $c30 > 0 ? (int) round($s30 / $c30 * 100) : 0, 'pickup30' => $p30,
                'nextHoliday' => collect($watch)->where('kind', 'public')->first(),
            ],
        ];
    }

    public static function ahead(?Carbon $today = null): array
    {
        $b = self::base($today); $today = $b['today']; $todayS = $today->toDateString();
        $win = [30, 60, 90];
        $rows = [];
        foreach ($b['units'] as $id => $u) {
            $n = [30 => 0, 60 => 0, 90 => 0]; $long = 0;
            for ($i = 0; $i < self::DAYS; $i++) {
                $v = $b['unit'][$id][$today->copy()->addDays($i)->toDateString()] ?? null;
                if ($v) {
                    foreach ($win as $w) { if ($i < $w) { $n[$w]++; } }
                    if ($v === 'l') { $long++; }
                }
            }
            $leads = $b['stays'][$id] ?? []; sort($leads);
            $until = $b['unit'][$id]['until'] ?? null;
            $rows[] = [
                'id' => $id, 'name' => $u['name'], 'property' => $u['property'], 'room' => $u['room'],
                'n30' => $n[30], 'n60' => $n[60], 'n90' => $n[90], 'p30' => (int) round($n[30] / 30 * 100), 'p60' => (int) round($n[60] / 60 * 100), 'p90' => (int) round($n[90] / 90 * 100),
                'long' => $long, 'until' => $until && $until > $todayS ? Carbon::parse($until) : null,
                'last' => !empty($b['unit'][$id]['last']) && $b['unit'][$id]['last'] > $todayS ? Carbon::parse($b['unit'][$id]['last']) : null,
                'lead' => $leads ? $leads[intdiv(count($leads), 2)] : null, 'stays' => count($leads),
                'early' => $leads ? (int) round(count(array_filter($leads, fn ($l) => $l >= 7)) / count($leads) * 100) : null,
            ];
        }
        usort($rows, fn ($x, $y) => [$y['n90'], $y['n30'], $x['name']] <=> [$x['n90'], $x['n30'], $y['name']]);

        $all = collect($rows);
        $byProperty = $all->groupBy('property')->map(function ($g, $p) use ($b) {
            $leads = $g->flatMap(fn ($u) => $b['stays'][$u['id']] ?? [])->sort()->values();
            $c = $g->count();
            return [
                'property' => $p, 'units' => $c, 'p30' => (int) round($g->sum('n30') / ($c * 30) * 100), 'p60' => (int) round($g->sum('n60') / ($c * 60) * 100), 'p90' => (int) round($g->sum('n90') / ($c * 90) * 100),
                'longUnits' => $g->filter(fn ($u) => $u['until'])->count(),
                'lead' => $leads->count() ? $leads[intdiv($leads->count(), 2)] : null,
                'early' => $leads->count() ? (int) round($leads->filter(fn ($l) => $l >= 7)->count() / $leads->count() * 100) : null,
                'sameDay' => $leads->count() ? (int) round($leads->filter(fn ($l) => $l <= 1)->count() / $leads->count() * 100) : null,
            ];
        })->sortByDesc('p90')->values();
        $leads = collect($b['stays'])->flatten()->sort()->values();

        return [
            'today' => $today, 'units' => $rows, 'byProperty' => $byProperty,
            'kpi' => [
                'p30' => (int) round($all->sum('n30') / max(1, $all->count() * 30) * 100), 'p90' => (int) round($all->sum('n90') / max(1, $all->count() * 90) * 100),
                'longUnits' => $all->filter(fn ($u) => $u['until'])->count(), 'units' => $all->count(),
                'lead' => $leads->count() ? $leads[intdiv($leads->count(), 2)] : null,
                'sameDay' => $leads->count() ? (int) round($leads->filter(fn ($l) => $l <= 1)->count() / $leads->count() * 100) : null,
                'early' => $leads->count() ? (int) round($leads->filter(fn ($l) => $l >= 7)->count() / $leads->count() * 100) : null,
            ],
            'since' => $b['from'],
        ];
    }

    /** Demand by building and room type over the last eight weeks, for Business Development. No owner or unit-level money. */
    public static function buildings(?Carbon $today = null): array
    {
        $b = self::base($today); $today = $b['today'];
        $groups = [];
        foreach ($b['units'] as $id => $u) {
            $groups[$u['property']][$u['room'] ?: 'Other'][] = $id;
        }
        $rows = [];
        foreach ($groups as $p => $types) {
            foreach ($types as $room => $ids) {
                $sold = 0; $sale = 0; $rate = 0; $longN = 0; $full = 0; $days = 0; $rev = 0;
                for ($d = $b['from']->copy(); $d->lt($today); $d->addDay()) {
                    $k = $d->toDateString(); $s = 0; $l = 0;
                    foreach ($ids as $id) {
                        $v = $b['unit'][$id][$k] ?? null;
                        if ($v === 's') { $s++; } elseif ($v === 'l') { $l++; }
                    }
                    $forSale = count($ids) - $l;
                    $sold += $s; $sale += $forSale; $longN += $l; $days++;
                    if ($forSale > 0 && $s / $forSale >= 0.9) { $full++; }
                }
                $rows[] = [
                    'property' => $p, 'room' => $room, 'units' => count($ids), 'ids' => $ids,
                    'pct' => $sale > 0 ? (int) round($sold / $sale * 100) : null, 'full' => $full, 'days' => $days,
                    'longShare' => (int) round($longN / max(1, count($ids) * $days) * 100),
                ];
            }
        }
        // rate and revenue per unit, from the same rows as the Units to push page (this month so far)
        $push = collect($b['push']['units'])->keyBy('id');
        foreach ($rows as &$r) {
            $u = collect($r['ids'])->map(fn ($id) => $push[$id] ?? null)->filter();
            $nights = $u->sum('nights');
            $r['rate'] = $nights > 0 ? (int) round($u->sum('revenue') / $nights) : null;
            $r['revUnit'] = $u->count() ? (int) round($u->sum('revenue') / $u->count()) : null;
            $r['monthPct'] = $u->count() ? (int) round($u->avg('occupancy')) : null;
            // long: mostly let on long stays. few: too few units to judge. Otherwise by how full the short-stay nights were.
            $r['verdict'] = ($r['pct'] === null || $r['longShare'] >= 60) ? 'long' : ($r['units'] < 3 ? 'few'
                : ($r['pct'] >= 70 && $r['full'] >= 14 ? 'strong' : ($r['pct'] >= 70 ? 'good' : ($r['pct'] >= 50 ? 'steady' : 'weak'))));
            unset($r['ids']);
        }
        unset($r);
        $rank = ['strong' => 0, 'good' => 1, 'long' => 2, 'steady' => 3, 'weak' => 4, 'few' => 5];
        usort($rows, fn ($x, $y) => [$rank[$x['verdict']], -($x['pct'] ?? 0)] <=> [$rank[$y['verdict']], -($y['pct'] ?? 0)]);

        // A building's figure uses only the room types that can be judged: not mostly long stays, not too few units.
        $byProperty = collect($rows)->groupBy('property')->map(function ($g, $p) {
            $j = $g->whereNotIn('verdict', ['long', 'few']);
            return [
                'property' => $p, 'units' => $g->sum('units'), 'types' => $g->count(),
                'pct' => $j->sum('units') > 0 ? (int) round($j->sum(fn ($x) => $x['pct'] * $x['units']) / $j->sum('units')) : null,
                'strong' => $g->where('verdict', 'strong')->count(), 'weak' => $g->where('verdict', 'weak')->count(),
            ];
        })->sortByDesc(fn ($x) => $x['pct'] ?? -1)->values();

        // Cleaning fees this month, per building (Sam, 25 Nov 2026). Room revenue on these pages leaves
        // cleaning out, so it is shown apart. Counted as the Portfolio page counts it: bookings that check
        // in this month, fee before SST; a stay split by month carries its fee on the first part. Extra
        // rooms have no owner and no building row, so they are totalled separately.
        $mStart = $today->copy()->startOfMonth();
        $fees   = Booking::join('listings', 'listings.id', '=', 'bookings.listing_id')->where('bookings.status', '>=', 5)
            ->where('bookings.check_in', '>=', $mStart->toDateString())->where('bookings.check_in', '<', $mStart->copy()->addMonth()->toDateString())
            ->where('bookings.cleaning_fee', '>', 0)->get(['listings.name', 'bookings.cleaning_fee']);
        $clean = []; $cleanExtra = 0.0; $cleanOther = 0.0;
        foreach ($fees as $f) {
            if (stripos((string) $f->name, 'extra room') !== false || stripos((string) $f->name, 'company') !== false) {
                $cleanExtra += (float) $f->cleaning_fee;
                continue;
            }
            $p = PortfolioAnalytics::propertyOf((string) $f->name);
            $clean[$p]['amount'] = ($clean[$p]['amount'] ?? 0) + (float) $f->cleaning_fee;
            $clean[$p]['count']  = ($clean[$p]['count'] ?? 0) + 1;
        }
        $byProperty = $byProperty->map(function ($x) use (&$clean) {
            $c = $clean[$x['property']] ?? ['amount' => 0, 'count' => 0];
            unset($clean[$x['property']]);
            return $x + ['cleaning' => round($c['amount'], 2), 'cleanCount' => $c['count']];
        });
        foreach ($clean as $c) {   // a building with no trading unit in the table above
            $cleanOther += $c['amount'];
        }

        return ['today' => $today, 'since' => $b['from'], 'rows' => $rows, 'byProperty' => $byProperty, 'month' => $today->format('F'),
            'cleaning' => ['buildings' => round($byProperty->sum('cleaning'), 2), 'extra' => round($cleanExtra, 2), 'other' => round($cleanOther, 2)]];
    }

    /**
     * For the Portfolio page (Sam, 25 Nov 2026): where this month is likely to finish, and
     * whether short stays or long stays earn more per night in each building. $property
     * narrows it to one building.
     *
     * Likely finish = what is on the books + the short-stay nights that normally still arrive.
     * For each night left in the month, a building is expected to end as full as it usually
     * does (last eight weeks, weekends apart); the gap to what is sold now is priced at the
     * building's recent short-stay rate. An estimate, not a promise.
     */
    public static function outlook(?string $property = null): array
    {
        $b = self::base(); $today = $b['today'];
        $count = $b['units']->countBy('property')->all();
        $props = $property ? array_values(array_filter([$property], fn ($p) => isset($count[$p]))) : array_keys($count);
        $we    = fn (Carbon $d) => in_array($d->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY], true) ? 1 : 0;

        // the last eight weeks, per building: short stays sold and for sale, their revenue, and long stays
        $h = []; $all = ['sold' => [0, 0], 'sale' => [0, 0], 'rev' => 0, 'n' => 0];
        for ($d = $b['from']->copy(); $d->lt($today); $d->addDay()) {
            $k = $d->toDateString(); $w = $we($d);
            foreach ($count as $p => $n) {
                $c = $b['night'][$p][$k] ?? []; $sale = self::forSale($b['night'], $count, $p, $k); $sold = min($sale, $c['sold'] ?? 0);
                $x = &$h[$p];
                $x['sold'][$w] = ($x['sold'][$w] ?? 0) + $sold; $x['sale'][$w] = ($x['sale'][$w] ?? 0) + $sale;
                $x['rev'] = ($x['rev'] ?? 0) + ($c['rate'] ?? 0); $x['n'] = ($x['n'] ?? 0) + ($c['sold'] ?? 0);
                $x['long'] = ($x['long'] ?? 0) + ($c['long'] ?? 0); $x['lrev'] = ($x['lrev'] ?? 0) + ($c['lrate'] ?? 0);
                unset($x);
                $all['sold'][$w] += $sold; $all['sale'][$w] += $sale; $all['rev'] += $c['rate'] ?? 0; $all['n'] += $c['sold'] ?? 0;
            }
        }
        $final = function (string $p, int $w) use ($h, $all, $count) {   // how full the building usually ends a night, %
            $src = ($count[$p] ?? 0) >= 5 ? ($h[$p] ?? $all) : $all;
            return ($src['sale'][$w] ?? 0) > 0 ? $src['sold'][$w] / $src['sale'][$w] * 100 : 0;
        };
        $allRate = $all['n'] > 0 ? $all['rev'] / $all['n'] : 0;

        // nights left this month: what should still arrive
        $endEx = $today->copy()->startOfMonth()->addMonth(); $pickupNights = 0; $pickup = 0.0; $left = 0;
        for ($d = $today->copy(); $d->lt($endEx); $d->addDay()) {
            $k = $d->toDateString(); $w = $we($d); $left++;
            foreach ($props as $p) {
                $sale = self::forSale($b['night'], $count, $p, $k); $sold = min($sale, $b['night'][$p][$k]['sold'] ?? 0);
                $extra = max(0, $final($p, $w) / 100 * $sale - $sold);
                $pickupNights += $extra;
                $pickup += $extra * (($h[$p]['n'] ?? 0) > 0 ? $h[$p]['rev'] / $h[$p]['n'] : $allRate);
            }
        }

        $scope = collect($b['push']['units'])->when($property, fn ($c) => $c->where('property', $property));
        $withUsual = $scope->filter(fn ($u) => ($u['usual'] ?? 0) > 0);
        $onBooks = (float) $scope->sum('revenue'); $soFar = (float) $withUsual->sum('soFar'); $usual = (float) $withUsual->sum('usual');

        // short stay or long stay: what one night of each earns, per building
        $mix = [];
        foreach ($props as $p) {
            $x = $h[$p] ?? null;
            if (!$x) { continue; }
            $sale = array_sum($x['sale']); $sold = array_sum($x['sold']);
            $revpar = $sale > 0 ? $x['rev'] / $sale : null;                     // short stay: earned per night for sale, empty nights included
            $longRate = $x['long'] >= 28 ? $x['lrev'] / $x['long'] : null;      // long stay: earned every night
            // fewer than three units in a building swing too much to compare
            $verdict = $revpar === null || $longRate === null || $count[$p] < self::MIN_UNITS ? 'na' : ($longRate >= $revpar * 1.1 ? 'long' : ($revpar >= $longRate * 1.1 ? 'short' : 'even'));
            $mix[] = [
                'property' => $p, 'units' => $count[$p], 'shortPct' => $sale > 0 ? (int) round($sold / $sale * 100) : null,
                'shortRate' => $x['n'] > 0 ? (int) round($x['rev'] / $x['n']) : null, 'shortNight' => $revpar === null ? null : (int) round($revpar),
                'longRate' => $longRate === null ? null : (int) round($longRate), 'longShare' => (int) round($x['long'] / max(1, $count[$p] * self::HISTORY) * 100),
                // how much more the better one earns, measured against the lower one
                'verdict' => $verdict, 'gap' => $revpar && $longRate ? (int) round((max($revpar, $longRate) / min($revpar, $longRate) - 1) * 100) : null,
            ];
        }
        usort($mix, fn ($x, $y) => $y['units'] <=> $x['units']);

        return [
            'today' => $today, 'month' => $today->format('F'), 'daysLeft' => $left, 'since' => $b['from'],
            'onBooks' => round($onBooks, 2), 'pickup' => round($pickup, 2), 'pickupNights' => (int) round($pickupNights), 'likely' => round($onBooks + $pickup, 2),
            'soFar' => round($soFar, 2), 'usual' => round($usual, 2), 'pace' => $usual > 0 && $b['push']['passed'] >= UnitsToPush::EARLY_DAYS ? (int) round(($soFar - $usual) / $usual * 100) : null,
            'counts' => $scope->countBy('status')->all(), 'mix' => $mix,
        ];
    }
}
