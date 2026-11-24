<?php

namespace App\Support;

use App\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Units to push (Sam, 24 Nov 2026): replaces the half-month pivot finance sent to
 * the revenue manager. One row per unit: the nights still empty, how the month is
 * booked, and room revenue so far against the unit's own usual, with one word of
 * status. Same basis as the Portfolio page: nights clipped to the month, revenue =
 * nights × nightly rate, before SST and fees. Reads only; nothing here changes a booking.
 *
 * "Usual" = the same days of the month (1st to yesterday) averaged over the last
 * three months the unit was already with us, so a studio is judged against a
 * studio's own record and not against one line for every unit.
 */
class UnitsToPush
{
    public const AHEAD_DAYS  = 14;   // how far ahead the empty-night count looks
    public const EARLY_DAYS  = 5;    // before this many nights have passed, the comparisons say too little
    public const PUSH_BELOW  = -25;  // % below its own usual that makes a unit "Push now"
    public const WATCH_BELOW = -10;  // % below its own usual that makes a unit "Watch"
    public const PUSH_GAP    = -20;  // booked % points below the building's other short-stay units: "Push now"
    public const WATCH_GAP   = -10;  // the same, for "Watch"
    public const PUSH_PEER   = -30;  // % revenue below similar units (same building, same room type): "Push now"
    public const WATCH_PEER  = -15;  // the same, for "Watch"
    public const MIN_PEERS   = 3;    // a group needs this many short-stay units before units are compared with it

    public const HIGH_BOOKED = 85;   // booked % at which a revenue shortfall is a rate question, not empty nights
    public const RATE_BAND   = 10;   // % above or below similar units before the nightly rate is called high or low

    public const STATUSES = ['push' => 'Push now', 'watch' => 'Watch', 'ok' => 'On track', 'full' => 'Full', 'idle' => 'No bookings'];

    public static function build(?Carbon $today = null): array
    {
        $today   = ($today ?: now())->copy()->startOfDay();
        $start   = $today->copy()->startOfMonth();
        $endEx   = $start->copy()->addMonth();
        $aheadEx = $today->copy()->addDays(self::AHEAD_DAYS);
        $hist    = [$start->copy()->subMonths(3), $start->copy()->subMonths(2), $start->copy()->subMonth()];
        $rangeS  = $hist[0]->toDateString();
        $rangeE  = max($endEx->toDateString(), $aheadEx->toDateString());

        $properties = PortfolioAnalytics::properties();
        $listings   = $properties->flatten(1);
        $ids        = $listings->pluck('id')->map(fn ($v) => (int) $v)->all();

        $rows = Booking::whereIn('listing_id', $ids)->where('status', '>=', 5)
            ->where('check_out', '>', $rangeS)->where('check_in', '<', $rangeE)
            ->get(['listing_id', 'check_in', 'check_out', 'price_night']);

        // night => taken, and room revenue per month and day of month, per unit
        $occ = []; $rev = [];
        foreach ($rows as $b) {
            $from = max((string) $b->check_in, $rangeS); $to = min((string) $b->check_out, $rangeE);
            for ($d = Carbon::parse($from); $d->toDateString() < $to; $d->addDay()) {
                $k = $d->toDateString();
                $occ[$b->listing_id][$k] = true;
                $m = substr($k, 0, 7); $day = (int) substr($k, 8, 2);
                $rev[$b->listing_id][$m][$day] = ($rev[$b->listing_id][$m][$day] ?? 0) + (float) $b->price_night;
            }
        }

        $emptyBetween = function (int $id, Carbon $from, Carbon $toEx) use ($occ) {
            $n = 0;
            for ($d = $from->copy(); $d->lt($toEx); $d->addDay()) {
                $n += isset($occ[$id][$d->toDateString()]) ? 0 : 1;
            }
            return $n;
        };
        $passed  = $today->day - 1;                       // nights of this month already gone
        $toDate  = fn (int $id, Carbon $m) => array_sum(array_filter($rev[$id][$m->format('Y-m')] ?? [], fn ($day) => $day <= $passed, ARRAY_FILTER_USE_KEY));
        $days    = $start->daysInMonth;
        $ym      = $start->format('Y-m');

        // Room type as eZee names it on the unit's own reservations ("FR- Deluxe Studio Room" → "Deluxe Studio Room").
        // listings.room_type and the room mapping's type are mostly blank, so the most common type since six months ago is used.
        $roomOf = [];
        $types = DB::table('ezee_bookings as e')->join('bookings as b', 'b.id', '=', 'e.book_id')
            ->whereIn('b.listing_id', $ids)->where('e.Start', '>=', $start->copy()->subMonths(6)->toDateString())
            ->whereNotNull('e.RoomTypeName')->where('e.RoomTypeName', '<>', '')
            ->selectRaw('b.listing_id, e.RoomTypeName as t, count(*) as n')->groupBy('b.listing_id', 'e.RoomTypeName')->orderByDesc('n')->get();
        foreach ($types as $t) {
            $roomOf[$t->listing_id] = $roomOf[$t->listing_id] ?? trim(preg_replace('/^[A-Z]{2,4}\s*-\s*/', '', (string) $t->t));
        }

        $units = [];
        foreach ($listings as $l) {
            $id      = (int) $l->id;
            $nights  = count($rev[$id][$ym] ?? []);       // nights of this month with a booking
            $revenue = array_sum($rev[$id][$ym] ?? []);
            $empty14 = $emptyBetween($id, $today, $aheadEx);
            $emptyM  = $emptyBetween($id, $today, $endEx);

            // usual: the same days in each earlier month the unit was already live
            $soFar = $toDate($id, $start); $usual = null; $vs = null;
            $base  = array_filter($hist, fn (Carbon $m) => !$l->created_at || Carbon::parse($l->created_at)->lte($m));
            if ($base) {
                $usual = array_sum(array_map(fn (Carbon $m) => $toDate($id, $m), $base)) / count($base);
                if ($passed >= self::EARLY_DAYS && $usual > 0) {
                    $vs = round(($soFar - $usual) / $usual * 100);
                }
            }

            $units[] = [
                'id' => $id, 'name' => $l->name, 'property' => PortfolioAnalytics::propertyOf($l->name),
                // idle: nothing this month, nothing ahead and nothing in the three months before. Not yet trading, or closed.
                'status' => $empty14 === 0 && $emptyM === 0 ? 'full' : ($nights === 0 && $empty14 === self::AHEAD_DAYS && !$usual ? 'idle' : 'ok'),
                'room' => $roomOf[$id] ?? '',
                'ahead' => implode('', array_map(fn ($i) => isset($occ[$id][$today->copy()->addDays($i)->toDateString()]) ? '1' : '0', range(0, self::AHEAD_DAYS - 1))),
                'empty7' => $emptyBetween($id, $today, $today->copy()->addDays(7)),
                'empty14' => $empty14, 'emptyMonth' => $emptyM, 'nights' => $nights,
                'occupancy' => $days > 0 ? round($nights / $days * 100) : 0,
                'revenue' => round($revenue, 2), 'adr' => $nights > 0 ? round($revenue / $nights, 2) : 0,
                'soFar' => round($soFar, 2), 'usual' => $usual === null ? null : round($usual, 2), 'vs' => $vs,
                'isNew' => !$base,
            ];
        }

        // Each unit against the building's other short-stay units (not full on a long stay, not idle).
        $peers = []; $revBld = []; $revType = []; $adrBld = []; $adrType = [];
        foreach ($units as $u) {
            if ($u['status'] === 'ok') {
                $peers[$u['property']][] = $u['occupancy'];
                $revBld[$u['property']][] = $u['revenue'];
                if ($u['room'] !== '') {
                    $revType[$u['property'] . '|' . $u['room']][] = $u['revenue'];
                }
                if ($u['nights'] > 0) {   // a rate exists only where a night is sold
                    $adrBld[$u['property']][] = $u['adr'];
                    if ($u['room'] !== '') {
                        $adrType[$u['property'] . '|' . $u['room']][] = $u['adr'];
                    }
                }
            }
        }
        $weekend = array_map(fn ($i) => in_array($today->copy()->addDays($i)->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY], true), range(0, self::AHEAD_DAYS - 1));
        foreach ($units as &$u) {
            $p = $peers[$u['property']] ?? [];
            $u['building'] = count($p) >= self::MIN_PEERS ? (int) round(array_sum($p) / count($p)) : null;
            $u['gap'] = $u['building'] === null || $passed < self::EARLY_DAYS ? null : (int) ($u['occupancy'] - $u['building']);
            // revenue against similar units: same building and room type; the whole building when the type has too few
            $same = $revType[$u['property'] . '|' . $u['room']] ?? [];
            $pool = count($same) >= self::MIN_PEERS ? $same : ($revBld[$u['property']] ?? []);
            $u['peerScope'] = count($same) >= self::MIN_PEERS ? 'type' : 'building';
            $u['peer'] = count($pool) >= self::MIN_PEERS ? round(array_sum($pool) / count($pool), 2) : null;
            $u['peerVs'] = $u['peer'] && $passed >= self::EARLY_DAYS ? (int) round(($u['revenue'] - $u['peer']) / $u['peer'] * 100) : null;
            // nightly rate against the same group: tells a price problem from a demand problem
            $rates = $u['peerScope'] === 'type' ? ($adrType[$u['property'] . '|' . $u['room']] ?? []) : ($adrBld[$u['property']] ?? []);
            $u['peerAdr'] = count($rates) >= self::MIN_PEERS ? round(array_sum($rates) / count($rates), 2) : null;
            $u['adrVs'] = $u['peerAdr'] && $u['adr'] > 0 ? (int) round(($u['adr'] - $u['peerAdr']) / $u['peerAdr'] * 100) : null;
            $u['weekendEmpty'] = count(array_filter(array_keys($weekend), fn ($i) => $weekend[$i] && $u['ahead'][$i] === '0'));
            $u['why'] = ''; $u['action'] = '';
            if ($u['status'] === 'idle') {
                $u['peerVs'] = null;
                $u['action'] = 'Confirm whether this unit is open for sale. If it is, check it is mapped and live on every channel.';
                $u['gap'] = null;
                $u['why'] = 'Nothing booked since ' . $hist[0]->format('F') . '. Is it open for sale?';
            }
            if ($u['status'] !== 'ok') {
                continue;
            }
            $vs = $u['vs']; $gap = $u['gap']; $pv = $u['peerVs'];
            if (($vs !== null && $vs <= self::PUSH_BELOW) || ($gap !== null && $gap <= self::PUSH_GAP) || ($pv !== null && $pv <= self::PUSH_PEER)) {
                $u['status'] = 'push';
            } elseif (($vs !== null && $vs <= self::WATCH_BELOW) || ($gap !== null && $gap <= self::WATCH_GAP) || ($pv !== null && $pv <= self::WATCH_PEER)) {
                $u['status'] = 'watch';
            }
            if ($u['status'] !== 'ok') {
                $why = [];
                if ($u['nights'] > 0) {
                    if ($vs !== null && $vs <= self::WATCH_BELOW) {
                        $why[] = abs($vs) . '% below its usual' . ($u['occupancy'] >= self::HIGH_BOOKED ? ': well booked, so check the rate' : '');
                    }
                    if ($gap !== null && $gap <= self::WATCH_GAP) {
                        $why[] = 'booked ' . abs($gap) . ' points below the building';
                    }
                    if ($pv !== null && $pv <= self::WATCH_PEER) {
                        $why[] = 'revenue ' . abs($pv) . '% below ' . ($u['peerScope'] === 'type' ? 'similar units' : 'the building average');
                    }
                }
                $u['why'] = ucfirst(implode('; ', $why));
                $u['action'] = self::action($u, $gap !== null && $gap <= self::WATCH_GAP, $vs !== null && $vs <= self::WATCH_BELOW);
            }
        }
        unset($u);

        $order = array_flip(array_keys(self::STATUSES));
        // worst first: the bigger of the two shortfalls decides the order inside a status
        $worst = fn ($u) => min($u['vs'] ?? 0, ($u['gap'] ?? 0) * 2, $u['peerVs'] ?? 0);
        usort($units, fn ($a, $b) => [$order[$a['status']], $worst($a), -$a['empty14'], $a['name']] <=> [$order[$b['status']], $worst($b), -$b['empty14'], $b['name']]);

        $all     = collect($units);
        $trading = $all->where('status', '<>', 'idle');   // totals leave out units that are not trading
        $byProperty = $trading->groupBy('property')->map(fn ($g, $p) => [
            'property' => $p, 'units' => $g->count(), 'push' => $g->where('status', 'push')->count(), 'watch' => $g->where('status', 'watch')->count(),
            'empty7' => $g->sum('empty7'), 'occupancy' => $g->count() ? round($g->sum('nights') / ($g->count() * $days) * 100) : 0,
        ])->sortByDesc('push')->values();

        return [
            'today' => $today, 'month' => $start->format('F Y'), 'days' => $days, 'passed' => $passed, 'early' => $passed < self::EARLY_DAYS,
            'aheadTo' => $aheadEx->copy()->subDay(), 'usualMonths' => array_map(fn (Carbon $m) => $m->format('M'), $hist),
            'units' => $units, 'byProperty' => $byProperty,
            'counts' => array_map(fn ($k) => $all->where('status', $k)->count(), array_combine(array_keys(self::STATUSES), array_keys(self::STATUSES))),
            'trading' => $trading->count(), 'empty7' => $trading->sum('empty7'), 'empty14' => $trading->sum('empty14'),
            'occupancy' => $trading->count() ? round($trading->sum('nights') / ($trading->count() * $days) * 100, 1) : 0,
            'revenue' => round($trading->sum('revenue'), 2),
        ];
    }

    /**
     * One suggested next step for a flagged unit, from simple rules: is the unit empty,
     * priced above or below similar units, or well booked at a low rate. A suggestion
     * for the manager to judge, not an instruction.
     */
    private static function action(array $u, bool $lowBooked, bool $belowUsual): string
    {
        $who  = $u['peerScope'] === 'type' ? 'similar units' : 'the building average';
        $rate = $u['adrVs'];
        if ($u['nights'] === 0) {
            $a = 'Nothing booked this month. Check it is open on every channel and not blocked in eZee.';
        } elseif ($u['occupancy'] >= self::HIGH_BOOKED) {
            $a = $rate !== null && $rate <= -self::RATE_BAND
                ? 'Well booked at a rate ' . abs($rate) . '% below ' . $who . '. Raise the rate for the nights still empty and for next month.'
                : 'Well booked, so there is little left to sell. Review the rate for next month.';
        } elseif ($rate !== null && $rate >= self::RATE_BAND && ($lowBooked || $u['empty14'] > 0)) {
            $a = 'Rate is ' . $rate . '% above ' . $who . '. Lower it, or run a promotion on the empty nights.';
        } elseif ($rate !== null && $rate <= -self::RATE_BAND && $lowBooked) {
            $a = 'Rate is already ' . abs($rate) . '% below ' . $who . ', so price is not the cause. Check the listing: photos, reviews, and that every channel is open.';
        } elseif ($rate !== null && $rate <= -self::RATE_BAND) {
            $a = 'Booked like its neighbours but at a rate ' . abs($rate) . '% lower. Raise the rate.';
        } elseif ($lowBooked) {
            $a = 'Rate is in line with ' . $who . '. Push the empty nights: a promotion or a long-stay offer, and check every channel is open.';
        } elseif ($belowUsual) {
            $a = ($rate === null ? 'Behind its own record.' : 'Booked and priced like its neighbours, but behind its own record.') . ' Fill the empty nights ahead.';
        } else {
            $a = 'Earning less than ' . $who . ' at a similar rate. Fill the empty nights ahead.';
        }

        return $a;
    }
}
