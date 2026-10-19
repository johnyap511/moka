<?php

namespace App\Support;

use App\Booking;
use App\Listing;
use App\User;
use Carbon\Carbon;

/**
 * Portfolio view (Sam, 12 Oct 2026): the whole portfolio or one property for a
 * month. Nights are clipped to the month, revenue = clipped nights × nightly
 * rate, the same basis as the owner dashboard's occupancy and revenue tiles.
 * Cleaning, SST and channel fees come from the rows checking in the month.
 * Admin and managers only.
 */
class PortfolioAnalytics
{
    /** Property name a unit belongs to, from its name ("Ekocheras H-10-02" → "Ekocheras"). */
    public static function propertyOf(string $name): string
    {
        $base = preg_replace('/(\s+(Extra\s+Room|\S*\d\S*))+$/u', '', $name) ?: $name;
        $key  = preg_replace('/[^a-z]/', '', strtolower($base));
        // Unit names are typed by hand ("EkoCheras" and "Ekocheras", "Bell Suite" and "Bell Suites"): one property each.
        return [
            'ekocheras' => 'EkoCheras', 'bellsuite' => 'Bell Suites', 'bellsuites' => 'Bell Suites', 'damai' => 'Damai 88',
            'artecheras' => 'Arte Cheras', 'forum' => 'Forum', 'alinea' => 'Alinea', 'queensville' => 'Queensville', 'klgateway' => 'KL Gateway', 'mesahill' => 'Mesahill',
        ][$key] ?? $base;
    }

    /** @return array<string, \Illuminate\Support\Collection<int, Listing>> property => units */
    public static function properties()
    {
        return Listing::where('status', 1)->orderBy('name')->get()
            ->reject(fn ($l) => stripos($l->name, 'extra room') !== false || stripos($l->name, 'company') !== false)
            ->groupBy(fn ($l) => self::propertyOf($l->name))->sortKeys();
    }

    public static function build($listings, Carbon $sel, string $label): array
    {
        $ids   = $listings->pluck('id')->map(fn ($v) => (int) $v)->all();
        $units = count($ids);
        $start = $sel->copy()->startOfMonth();
        $endEx = $start->copy()->addMonth();
        $lyStart = $start->copy()->subYear();
        $trendStart = $start->copy()->subMonths(5);

        $owners = User::whereIn('id', $listings->pluck('user_id')->filter()->unique())->pluck('name', 'id');

        // One read for the six-month trend and this month; one for last year.
        $fetch = fn ($from, $toEx) => Booking::whereIn('listing_id', $ids)->where('status', '>=', 5)
            ->where('check_out', '>', $from->toDateString())->where('check_in', '<', $toEx->toDateString())
            ->get(['id', 'listing_id', 'check_in', 'check_out', 'nights', 'price_night', 'cleaning_fee', 'sst', 'sst_cf', 'ota_fee', 'price', 'source']);
        $rows   = $fetch($trendStart, $endEx);
        $lyRows = $fetch($lyStart, $lyStart->copy()->addMonth());

        $month = fn ($rows, Carbon $s) => self::monthStats($rows, $s, $units);

        $now = $month($rows, $start);
        $ly  = $month($lyRows, $lyStart);

        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $start->copy()->subMonths($i);
            $t = $month($rows, $m);
            $trend[] = ['label' => $m->format("M'y"), 'revenue' => $t['revenue'], 'occupancy' => $t['occupancy'], 'adr' => $t['adr'], 'revpar' => $t['revpar']];
        }

        // Per unit, this month.
        $byUnit = [];
        foreach ($listings as $l) {
            $u = $now['units'][$l->id] ?? ['nights' => 0, 'revenue' => 0.0, 'bookings' => 0];
            $days = $sel->daysInMonth;
            $byUnit[] = [
                'id' => $l->id, 'name' => $l->name, 'owner' => $owners[$l->user_id] ?? '', 'property' => self::propertyOf($l->name),
                'nights' => $u['nights'], 'occupancy' => $days > 0 ? round($u['nights'] / $days * 100, 1) : 0,
                'revenue' => round($u['revenue'], 2), 'adr' => $u['nights'] > 0 ? round($u['revenue'] / $u['nights'], 2) : 0,
                'revpar' => $days > 0 ? round($u['revenue'] / $days, 2) : 0, 'bookings' => $u['bookings'],
            ];
        }
        usort($byUnit, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        // Per property (for the whole-portfolio view).
        $byProperty = collect($byUnit)->groupBy('property')->map(function ($g, $p) use ($sel) {
            $n = $g->sum('nights'); $r = $g->sum('revenue'); $avail = $g->count() * $sel->daysInMonth;
            return ['property' => $p, 'units' => $g->count(), 'nights' => $n, 'occupancy' => $avail > 0 ? round($n / $avail * 100, 1) : 0, 'revenue' => round($r, 2), 'adr' => $n > 0 ? round($r / $n, 2) : 0, 'revpar' => $avail > 0 ? round($r / $avail, 2) : 0, 'bookings' => $g->sum('bookings')];
        })->sortByDesc('revenue')->values();

        $cmp = [];
        foreach (['revenue' => ['Revenue', 'rm'], 'occupancy' => ['Occupancy', 'pct'], 'adr' => ['ADR', 'rm'], 'revpar' => ['RevPAR', 'rm'], 'bookings' => ['Bookings', 'n'], 'nights' => ['Nights sold', 'n']] as $k => [$cLabel, $kind]) {
            $a = (float) $now[$k]; $b = (float) $ly[$k];
            $cmp[] = ['label' => $cLabel, 'now' => $a, 'then' => $b, 'pct' => $b > 0 ? round(($a - $b) / $b * 100, 1) : null, 'kind' => $kind];
        }

        return [
            'label' => $label, 'month' => $sel->format('F Y'), 'lastYear' => $lyStart->format('M Y'), 'units' => $units,
            'now' => $now, 'ly' => $ly, 'compare' => $cmp, 'trend' => $trend,
            'byUnit' => $byUnit, 'byProperty' => $byProperty, 'colours' => Channel::colours(),
        ];
    }

    private static function monthStats($rows, Carbon $start, int $units): array
    {
        $s = $start->toDateString(); $eEx = $start->copy()->addMonth()->toDateString(); $days = $start->daysInMonth;
        $nights = 0; $revenue = 0.0; $bookings = 0; $byUnit = []; $byChannel = [];
        $cleaning = 0.0; $sst = 0.0; $fee = 0.0; $gross = 0.0; $roomIn = 0.0;
        foreach ($rows as $b) {
            if ($b->check_out <= $s || $b->check_in >= $eEx) {
                continue;
            }
            $cin = max($b->check_in, $s); $cout = min($b->check_out, $eEx);
            $n = max(0, (int) Carbon::parse($cin)->diffInDays(Carbon::parse($cout)));
            $r = $n * (float) $b->price_night;
            $nights += $n; $revenue += $r; $bookings++;
            $u = &$byUnit[$b->listing_id]; $u['nights'] = ($u['nights'] ?? 0) + $n; $u['revenue'] = ($u['revenue'] ?? 0) + $r; $u['bookings'] = ($u['bookings'] ?? 0) + 1; unset($u);
            $ch = Channel::canonical($b->source) ?: 'Unknown';
            $c = &$byChannel[$ch]; $c['bookings'] = ($c['bookings'] ?? 0) + 1; $c['nights'] = ($c['nights'] ?? 0) + $n; $c['revenue'] = ($c['revenue'] ?? 0) + $r; $c['fee'] = ($c['fee'] ?? 0) + ($b->check_in >= $s ? (float) $b->ota_fee : 0); unset($c);
            if ($b->check_in >= $s) {   // the row belongs to this month: fees and taxes are not split across months
                $cleaning += (float) $b->cleaning_fee; $sst += (float) $b->sst + (float) $b->sst_cf; $fee += (float) $b->ota_fee; $gross += (float) $b->price; $roomIn += (float) $b->nights * (float) $b->price_night;
            }
        }
        $avail = $days * $units;
        $channels = collect($byChannel)->map(fn ($c, $k) => ['channel' => $k, 'bookings' => $c['bookings'], 'nights' => $c['nights'], 'revenue' => round($c['revenue'], 2), 'fee' => round($c['fee'], 2), 'share' => $revenue > 0 ? round($c['revenue'] / $revenue * 100, 1) : 0, 'adr' => $c['nights'] > 0 ? round($c['revenue'] / $c['nights'], 2) : 0])->sortByDesc('revenue')->values();
        return [
            'nights' => $nights, 'available' => $avail, 'occupancy' => $avail > 0 ? round($nights / $avail * 100, 1) : 0,
            'revenue' => round($revenue, 2), 'bookings' => $bookings, 'adr' => $nights > 0 ? round($revenue / $nights, 2) : 0, 'revpar' => $avail > 0 ? round($revenue / $avail, 2) : 0,
            'units' => $byUnit, 'channels' => $channels,
            'split' => ['room' => round($roomIn, 2), 'cleaning' => round($cleaning, 2), 'sst' => round($sst, 2), 'fee' => round($fee, 2), 'gross' => round($gross, 2)],
        ];
    }
}
