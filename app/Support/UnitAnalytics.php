<?php

namespace App\Support;

use App\Booking;
use App\Http\Controllers\Owner\HomeController as OwnerDashboard;
use App\Listing;
use Carbon\Carbon;

/**
 * Analysis sections of the admin Unit report (Sam, 12 Oct 2026): revenue split,
 * nights and RevPAR, same month last year, revenue by channel, bookings list.
 * Admin and managers only; owners never see these.
 *
 * Month rows = bookings checking in within the month (rows are split at month
 * ends, so this is the month's revenue the way the statements count it). Nights
 * sold / available follow the dashboard's clipping so occupancy agrees with it.
 */
class UnitAnalytics
{
    public static function build(?Listing $listing, array $metrics): ?array
    {
        if (!$listing) {
            return null;
        }
        $selDate  = $metrics['selDate'];
        $pool     = $metrics['pool'];
        $scopeIds = $pool ? $pool['listing_ids'] : [$listing->id];
        $units    = $pool ? $pool['units'] : 1;
        $start    = $selDate->copy()->startOfMonth();
        $endEx    = $start->copy()->addMonth();

        $rows = Booking::whereIn('listing_id', $scopeIds)
            ->where('status', '>=', 5)
            ->where('check_in', '>=', $start->toDateString())
            ->where('check_in', '<', $endEx->toDateString())
            ->with(['user:id,name,last_name', 'listing:id,name'])
            ->orderBy('check_in')->orderBy('id')
            ->get();

        $room = 0.0; $cleaning = 0.0; $sst = 0.0; $fee = 0.0; $gross = 0.0; $nightsRows = 0;
        foreach ($rows as $b) {
            $room     += (float) $b->nights * (float) $b->price_night;
            $cleaning += (float) $b->cleaning_fee;
            $sst      += (float) $b->sst + (float) $b->sst_cf;
            $fee      += (float) $b->ota_fee;
            $gross    += (float) $b->price;
            $nightsRows += (int) $b->nights;
        }
        $share = (float) ($listing->profit ?? 0);          // management share, % of room revenue (listing setting)
        $mokaShare = round($room * $share / 100, 2);
        $net = round($room - $fee - $mokaShare, 2);

        // Nights sold the way the dashboard counts them (stays clipped to the month).
        $overlap = Booking::whereIn('listing_id', $scopeIds)->where('status', '>=', 5)
            ->where('check_out', '>', $start->toDateString())->where('check_in', '<=', $selDate->copy()->endOfMonth()->toDateString())
            ->get(['check_in', 'check_out']);
        $sold = 0;
        foreach ($overlap as $b) {
            $cin = max($b->check_in, $start->toDateString());
            $cout = min($b->check_out, $endEx->toDateString());
            $sold += max(0, (int) Carbon::parse($cin)->diffInDays(Carbon::parse($cout)));
        }
        $available = $selDate->daysInMonth * $units;
        $revpar = $available > 0 ? round($room / $available, 2) : 0;

        // Same month last year, same code path as the tiles.
        $ly = OwnerDashboard::metrics($listing, $listing->id, $selDate->copy()->subYear()->format('Y-m'));
        $cmp = [];
        foreach (['monthRevenue' => 'Revenue', 'occupancy' => 'Occupancy', 'avgDailyRate' => 'Average daily rate', 'bookingCount' => 'Bookings', 'avgLengthOfStay' => 'Average stay'] as $k => $label) {
            $now = (float) $metrics[$k]; $then = (float) $ly[$k];
            $cmp[] = ['label' => $label, 'now' => $now, 'then' => $then, 'pct' => $then > 0 ? round(($now - $then) / $then * 100, 1) : null, 'kind' => in_array($k, ['monthRevenue', 'avgDailyRate']) ? 'rm' : ($k === 'occupancy' ? 'pct' : 'n')];
        }

        // Revenue by channel.
        $byChannel = $rows->groupBy(fn ($b) => Channel::canonical($b->source))->map(function ($g, $src) use ($room) {
            $r = $g->sum(fn ($b) => (float) $b->nights * (float) $b->price_night);
            return ['channel' => $src, 'bookings' => $g->count(), 'nights' => (int) $g->sum('nights'), 'room' => round($r, 2), 'fee' => round((float) $g->sum('ota_fee'), 2), 'share' => $room > 0 ? round($r / $room * 100, 1) : 0, 'adr' => $g->sum('nights') > 0 ? round($r / $g->sum('nights'), 2) : 0];
        })->sortByDesc('room')->values();

        return [
            'month'     => $selDate->format('F Y'),
            'lastYear'  => $selDate->copy()->subYear()->format('M Y'),
            'units'     => $units,
            'split'     => ['room' => round($room, 2), 'cleaning' => round($cleaning, 2), 'sst' => round($sst, 2), 'fee' => round($fee, 2), 'gross' => round($gross, 2), 'share_pct' => $share, 'moka' => $mokaShare, 'net' => $net],
            'nights'    => ['sold' => $sold, 'available' => $available, 'revpar' => $revpar, 'rows' => $nightsRows],
            'compare'   => $cmp,
            'byChannel' => $byChannel,
            'rows'      => $rows,
            'colours'   => Channel::colours(),
        ];
    }
}
