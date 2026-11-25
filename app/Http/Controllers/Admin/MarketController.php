<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketCompetitor;
use App\Models\MarketRate;
use App\Support\RevenueDesk;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Revenue desk, Market tab (Sam, 25 Nov 2026): the manager keys in what nearby
 * competitors charge for three nights, about once a week, and the page sets that
 * beside our own rate and how the building is selling. Writes only to the two
 * market tables; it never touches a booking.
 */
class MarketController extends Controller
{
    public function __construct()
    {
        $this->middleware(fn ($request, $next) => admin_can('reports.push') ? $next($request) : abort(403));
    }

    /** Buildings and their room types, from the same figures as the other tabs. */
    private function buildings(): array
    {
        $out = [];
        foreach (RevenueDesk::cached('push')['data']['units'] as $u) {
            if ($u['status'] === 'idle') {
                continue;
            }
            $out[$u['property']][$u['room'] ?: 'Any room'] = true;
        }
        ksort($out);

        return array_map(fn ($t) => array_keys($t), $out);
    }

    public function index(Request $request)
    {
        $buildings = $this->buildings();
        $property  = (string) $request->input('property', '');
        if (!isset($buildings[$property])) {
            $property = (string) (MarketCompetitor::where('active', 1)->whereIn('property', array_keys($buildings))->orderByDesc('id')->value('property') ?: array_key_first($buildings));
        }
        $today  = now()->startOfDay();
        $demand = RevenueDesk::cached('demand')['data'];

        // Three nights to check: this Saturday, next Saturday, and the next busy night after them (a public holiday, its eve, a stadium event or a convocation).
        $sat  = $today->copy(); while ($sat->dayOfWeek !== Carbon::SATURDAY) { $sat->addDay(); }
        $peak = collect($demand['dates'])->first(fn ($d) => $d['day']->gt($sat->copy()->addDays(7)) && (collect($d['hol'])->whereIn('kind', ['public', 'eve'])->count() || !empty($d['evBig'])));
        $default = [$sat->toDateString(), $sat->copy()->addDays(7)->toDateString(), $peak ? $peak['date'] : $sat->copy()->addDays(14)->toDateString()];
        $dates = [];
        foreach ([0, 1, 2] as $i) {
            $v = (string) $request->input('d' . ($i + 1), '');
            $ok = preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && $v >= $today->toDateString() && $v <= $today->copy()->addYear()->toDateString();
            $dates[] = $ok ? $v : $default[$i];
        }
        $dates = array_values(array_unique($dates)); sort($dates);

        $rows = MarketCompetitor::where('property', $property)->where('active', 1)->orderByDesc('is_own')->orderBy('name')->get();
        // the latest price keyed for each competitor and night
        $latest = [];
        foreach (MarketRate::whereIn('competitor_id', $rows->pluck('id'))->whereIn('stay_date', $dates)->orderBy('id')->get() as $r) {
            $latest[$r->competitor_id][substr((string) $r->stay_date, 0, 10)] = $r;
        }

        // per room type and night: our rate against the competitors' average, with a line on what to do
        $dateInfo = collect($demand['dates'])->keyBy('date');
        $compare = [];
        foreach ($rows->groupBy('room_type') as $type => $set) {
            foreach ($dates as $date) {
                $own  = $set->where('is_own', true)->map(fn ($c) => $latest[$c->id][$date] ?? null)->filter()->first();
                $them = $set->where('is_own', false)->map(fn ($c) => $latest[$c->id][$date] ?? null)->filter();
                $cell = $demand['cells'][$property][$date] ?? null;
                $avg  = $them->count() ? round($them->avg('rate'), 2) : null;
                $ours = $own ? (float) $own->rate : null;
                $diff = $ours !== null && $avg ? (int) round(($ours - $avg) / $avg * 100) : null;
                $compare[$type][$date] = [
                    'ours' => $ours, 'avg' => $avg, 'n' => $them->count(), 'low' => $them->min('rate'), 'high' => $them->max('rate'), 'diff' => $diff,
                    'cell' => $cell && $cell['level'] !== 'na' ? $cell : null, 'say' => self::say($ours, $avg, $diff, $cell),
                ];
            }
        }

        $last = MarketRate::whereIn('competitor_id', MarketCompetitor::where('active', 1)->pluck('id'))
            ->join('market_competitors as c', 'c.id', '=', 'market_rates.competitor_id')->selectRaw('c.property, max(market_rates.checked_on) as d')->groupBy('c.property')->pluck('d', 'property');

        return view('admin.revenue.market', compact('buildings', 'property', 'dates', 'rows', 'latest', 'compare', 'last', 'today', 'dateInfo'));
    }

    /** One plain line: is our price above or below nearby, and is the building filling? */
    private static function say(?float $ours, ?float $avg, ?int $diff, ?array $cell): array
    {
        if ($avg === null) {
            return ['', 'Key in at least one competitor for this night.'];
        }
        if ($ours === null) {
            return ['', 'Key in our own rate on sale to compare.'];
        }
        $rated   = $cell && $cell['level'] !== 'na';
        $filling = $rated && $cell['diff'] >= RevenueDesk::WARM;
        $slow    = $rated && $cell['diff'] <= 0;
        $to      = 'RM ' . number_format($avg, 0);
        if ($diff >= 10) {
            return $filling ? ['good', abs($diff) . '% above nearby and still filling early. The price is holding: keep it.']
                : ($slow ? ['warn', abs($diff) . '% above nearby and not filling. Lower towards ' . $to . ', unless the unit is clearly better.']
                    : ['flat', abs($diff) . '% above nearby and selling as normal. Watch it; lower only if it stalls.']);
        }
        if ($diff <= -10) {
            return $filling ? ['warn', abs($diff) . '% below nearby and filling early. Raise towards ' . $to . '.']
                : ($slow ? ['bad', abs($diff) . '% below nearby and still slow. Price is not the problem: check photos, reviews and ranking.']
                    : ['flat', abs($diff) . '% below nearby and selling as normal. There is room to raise a little.']);
        }

        return $filling ? ['good', 'In line with nearby, and filling early. Demand is strong: there is room to go above.'] : ['flat', 'In line with nearby (within 10%).'];
    }

    public function storeCompetitor(Request $request)
    {
        $buildings = $this->buildings();
        $data = $request->validate([
            'property'  => 'required|string|in:' . implode(',', array_keys($buildings)),
            'room_type' => 'required|string|max:80',
            'name'      => 'required|string|max:120',
            'link'      => 'nullable|url|max:500',
        ]);
        if (!in_array($data['room_type'], $buildings[$data['property']], true)) {
            return back()->with('error', 'Pick one of the room types listed for that building.');
        }
        // our own "rate on sale" row for that room type, so there is something to compare with
        MarketCompetitor::firstOrCreate(
            ['property' => $data['property'], 'room_type' => $data['room_type'], 'is_own' => true],
            ['name' => 'Our rate on sale', 'active' => true, 'created_by' => Auth::id()]
        )->update(['active' => true]);
        MarketCompetitor::create($data + ['is_own' => false, 'active' => true, 'created_by' => Auth::id()]);

        return redirect('/admin/revenue-desk/market?property=' . urlencode($data['property']))->with('success', $data['name'] . ' added. Key in its rates below.');
    }

    public function removeCompetitor($id)
    {
        $c = MarketCompetitor::where('is_own', false)->findOrFail($id);
        $c->update(['active' => false]);   // its past rates are kept

        return redirect('/admin/revenue-desk/market?property=' . urlencode($c->property))->with('success', $c->name . ' removed from the list. Its past rates are kept.');
    }

    public function storeRates(Request $request)
    {
        $request->validate([
            'property' => 'required|string|max:60',
            'rate'     => 'array',
            'rate.*'   => 'array',
            'rate.*.*' => 'nullable|numeric|min:1|max:100000',
        ]);
        $property = (string) $request->input('property');
        $ids      = MarketCompetitor::where('property', $property)->where('active', 1)->pluck('id')->all();
        $today    = now()->toDateString(); $max = now()->addYear()->toDateString(); $saved = 0;
        foreach ((array) $request->input('rate', []) as $cid => $byDate) {
            if (!in_array((int) $cid, $ids, true)) {
                continue;   // not a competitor of this building
            }
            foreach ((array) $byDate as $date => $rate) {
                if ($rate === null || $rate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date) || $date < $today || $date > $max) {
                    continue;
                }
                $prev = MarketRate::where('competitor_id', $cid)->where('stay_date', $date)->orderByDesc('id')->first();
                if ($prev && (float) $prev->rate === (float) $rate && substr((string) $prev->checked_on, 0, 10) === $today) {
                    continue;   // the same figure, already saved today
                }
                MarketRate::create(['competitor_id' => (int) $cid, 'stay_date' => $date, 'rate' => round((float) $rate, 2), 'checked_on' => $today, 'entered_by' => Auth::id()]);
                $saved++;
            }
        }
        $q = http_build_query(['property' => $property] + array_filter($request->only(['d1', 'd2', 'd3'])));

        return redirect('/admin/revenue-desk/market?' . $q)->with('success', $saved ? $saved . ' rate' . ($saved > 1 ? 's' : '') . ' saved.' : 'Nothing new to save.');
    }
}
