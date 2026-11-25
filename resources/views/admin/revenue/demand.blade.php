@extends('admin.layout')
@section('title', 'Revenue desk')
@section('page-title', 'Revenue desk')

@push('styles')
@include('admin.revenue._style')
<style>
.dm-when{white-space:nowrap}.dm-when b{font-size:13.5px}.dm-tags{display:flex;gap:4px;flex-wrap:wrap;margin-top:5px}
.dm-vs{display:flex;align-items:baseline;gap:6px;white-space:nowrap}.dm-vs .now{font-size:15px;font-weight:700}.dm-vs .was{font-size:12px;color:var(--mute)}
.dm-do{min-width:210px}.dm-do p{margin:6px 0 0;font-size:12.5px;line-height:1.4;color:#44403c}
/* calendar: one row per building, one square per night */
.cal{display:grid;grid-template-columns:118px max-content;font-size:11px;padding:0 0 12px}
.cal-names{position:sticky;left:0;background:#fff;z-index:2;border-right:1px solid var(--line)}
.cal-names div,.cal-row{height:26px;display:flex;align-items:center}.cal-names div{padding:0 10px 0 16px;font-size:12.5px;font-weight:600;white-space:nowrap}.cal-names div.all{font-weight:700}.cal-names div.h{height:46px;align-items:flex-end;padding-bottom:4px;font-size:11px;color:var(--mute);font-weight:600;text-transform:uppercase;letter-spacing:.03em}
.cal-grid{padding-right:16px}.cal-row{gap:2px;padding-left:6px}.cal-row.h{height:46px;align-items:flex-end;padding-bottom:4px}.cal-row.all{background:#fafafa}
.cal-c{width:17px;height:20px;border-radius:4px;flex:0 0 auto;position:relative;background:#f1f5f9}.cal-c.m1{margin-left:8px}
.cal-c.hot{box-shadow:0 0 0 2px #ea580c;z-index:1}.cal-c.warm{box-shadow:0 0 0 1.5px #f59e0b}.cal-c.soft::after{content:"";position:absolute;left:5px;right:5px;bottom:2px;height:3px;border-radius:2px;background:#0284c7}.cal-c.na{background:repeating-linear-gradient(45deg,#f8fafc,#f8fafc 3px,#eef2f5 3px,#eef2f5 6px)}
.cal-d{width:17px;flex:0 0 auto;text-align:center;line-height:1.15;color:var(--mute);position:relative}.cal-d.m1{margin-left:8px}.cal-d b{display:block;font-weight:600;color:#44403c}.cal-d.we b{color:#0f766e}.cal-d .mo{position:absolute;bottom:30px;left:0;font-size:11px;font-weight:700;color:var(--text,#1d1d1f);white-space:nowrap;text-transform:uppercase;letter-spacing:.03em}
.cal-d.hol b{background:#dc2626;color:#fff;border-radius:4px}.cal-d.eve b{background:#fecaca;color:#7f1d1d;border-radius:4px}.cal-d.sea::after{content:"";position:absolute;left:1px;right:1px;bottom:-3px;height:2px;background:#a78bfa;border-radius:1px}
.cal-key{display:flex;gap:14px;flex-wrap:wrap;padding:0 16px 14px;font-size:11.5px;color:var(--mute);align-items:center}.cal-key span{display:inline-flex;align-items:center;gap:5px}.cal-key .cal-c{width:13px;height:13px;border-radius:3px}.cal-key .sw{display:inline-flex;gap:1px}.cal-key .d{width:14px;height:14px;border-radius:3px;display:inline-block}
.dm-hol td{vertical-align:middle}
@media (max-width:900px){.cal{grid-template-columns:92px max-content}.cal-names div{padding-left:12px;font-size:12px}.dm-do{min-width:0}}
</style>
@endpush

@section('content')
@php
    use App\Support\RevenueDesk as R;
    $today = $d['today']; $k = $d['kpi'];
    $shade = function ($pct) {   // light grey to deep teal
        if ($pct <= 0) return '#f1f5f9';
        $t = min(1, $pct / 100); $a = [204, 251, 241]; $z = [15, 94, 89];
        return sprintf('rgb(%d,%d,%d)', $a[0] + ($z[0] - $a[0]) * $t, $a[1] + ($z[1] - $a[1]) * $t, $a[2] + ($z[2] - $a[2]) * $t);
    };
    $up = fn ($v) => $v > 0 ? '<span class="rd-up ' . ($v < 15 ? 'mild' : '') . '">+' . $v . '%</span>' : ($v < 0 ? '<span class="rd-up down">' . $v . '% or a promotion</span>' : '<span class="rd-up none">Hold the rate</span>');
    $range = fn ($a, $b) => $a->equalTo($b) ? $a->format('D j M') : ($a->format('D j') . ($a->month !== $b->month ? ' ' . $a->format('M') : '') . ' to ' . $b->format('D j M'));
    $togo = fn ($n) => $n === 0 ? 'tonight' : ($n === 1 ? 'tomorrow' : 'in ' . $n . ' days');
    $tip = fn ($name, $dt, $c) => $dt['day']->format('D j M') . ' · ' . $name . ': ' . ($c['level'] === 'na' ? 'too few short-stay units to rate' : $c['sold'] . ' of ' . $c['sale'] . ' sold (' . $c['pct'] . '%), normal ' . $c['normal'] . '%' . ($c['rate'] ? ' · sold at RM ' . $c['rate'] : '') . ($c['suggest'] ? ' · suggest ' . ($c['suggest'] > 0 ? '+' : '') . $c['suggest'] . '%' : ''));
@endphp

<div class="rd">
@include('admin.revenue._tabs', ['tab' => 'demand'])

<div class="rd-head">
    <h1>Demand ahead</h1>
    <p>{{ $today->format('l, j F Y') }} · the next {{ R::DAYS }} nights · short-stay units only, long stays are left out</p>
</div>

<div class="rd-kpis">
    <div class="rd-kpi hot"><div class="l">Spikes</div><div class="v">{{ $k['hot'] }}</div><div class="s">dates filling far earlier than normal</div></div>
    <div class="rd-kpi warm"><div class="l">Filling early</div><div class="v">{{ $k['warm'] }}</div><div class="s">dates ahead of normal</div></div>
    <div class="rd-kpi"><div class="l">Sold, next 30 nights</div><div class="v">{{ $k['pct30'] }}%</div><div class="s">{{ number_format($k['pickup30']) }} nights booked in the last 7 days</div></div>
    <div class="rd-kpi"><div class="l">Next holiday</div>
        @if($k['nextHoliday'])<div class="v sm">{{ $k['nextHoliday']['name'] }}</div><div class="s">{{ $k['nextHoliday']['day']->format('D j M') }}, {{ $togo($k['nextHoliday']['lead']) }}</div>
        @else<div class="v sm">None in {{ R::DAYS }} days</div>@endif
    </div>
</div>

@if($lead['sameDay'] !== null)
<div class="rd-insight"><b>{{ $lead['sameDay'] }}% of short stays are booked on the day or the day before</b>, and only {{ $lead['early'] }}% a week or more ahead. So a date that is already filling weeks out is unusual: change its rate now, because most of its bookings have not arrived yet.</div>
@endif

<div class="rd-card">
    <h2>Dates to act on</h2>
    <p class="sub">A date is listed when its building has sold far more short-stay units than is normal this many days ahead. Normal is learnt from the last {{ R::HISTORY / 7 }} weeks.</p>
    @if(count($d['act']))
    <div class="rd-wrap">
        <table class="rd-table cards" style="min-width:0">
            <thead><tr><th>When</th><th>Building</th><th>Sold now</th><th>Units left</th><th>Booked in last 7 days</th><th>Sold at</th><th>Suggested</th></tr></thead>
            <tbody>
            @foreach($d['act'] as $a)
            <tr>
                <td class="dm-when wide"><b>{{ $range($a['from'], $a['to']) }}</b><div class="rd-sub">{{ $togo($a['lead']) }}{{ $a['nights'] > 1 ? ' · ' . $a['nights'] . ' nights' : '' }}</div>
                    <div class="dm-tags"><span class="rd-chip {{ $a['level'] }}">{{ $a['level'] === 'hot' ? 'Spike' : 'Filling early' }}</span>@foreach($a['holidays'] as $h)<span class="rd-chip red">{{ $h }}</span>@endforeach</div></td>
                <td data-l="Building"><span class="rd-b">{{ $a['property'] }}</span></td>
                <td data-l="Sold now"><div class="dm-vs"><span class="now">{{ $a['pct'] }}%</span><span class="was">normal {{ $a['normal'] }}%</span></div>
                    <div class="rd-meter"><i style="width:{{ min(100, $a['pct']) }}%"></i><u style="left:calc({{ min(100, $a['normal']) }}% - 1px)"></u></div></td>
                <td data-l="Units left"><span class="rd-num">{{ $a['left'] }}</span><div class="rd-sub">{{ $a['nights'] > 1 ? 'unit-nights' : 'units' }} still to sell</div></td>
                <td data-l="Booked in last 7 days"><span class="rd-num">{{ $a['pickup'] ?: '–' }}</span>@if($a['pickup'])<div class="rd-sub">night{{ $a['pickup'] > 1 ? 's' : '' }}</div>@endif</td>
                <td data-l="Sold at"><span class="rd-num">{{ $a['rate'] ? 'RM ' . number_format($a['rate']) : '–' }}</span><div class="rd-sub">average a night so far</div></td>
                <td class="dm-do wide" data-l="Suggested">{!! $up($a['suggest']) !!}<p>Raise the rate on the {{ $a['left'] }} {{ $a['nights'] > 1 ? 'unit-nights' : 'units' }} still to sell.@if($a['pct'] >= R::NEAR_FULL) Nearly full: hold the last units for the best price.@endif</p></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="rd-empty">No date is filling ahead of normal right now.</div>
    @endif
</div>

<div class="rd-card">
    <h2>Calendar, next {{ R::DAYS }} nights</h2>
    <p class="sub">Each square is one night. Darker means more short-stay units sold. Hover over a square for the figures; scroll sideways for later dates.</p>
    <div class="rd-wrap">
        <div class="cal">
            <div class="cal-names">
                <div class="h">Building</div>
                <div class="all">All buildings</div>
                @foreach($d['props'] as $p)<div>{{ $p }}</div>@endforeach
            </div>
            <div class="cal-grid">
                <div class="cal-row h">
                    @foreach($d['dates'] as $i => $dt)
                    @php $kinds = array_column($dt['hol'], 'kind'); $names = implode(', ', array_unique(array_column($dt['hol'], 'name'))); $m1 = $i > 0 && $dt['day']->day === 1; @endphp
                    <div class="cal-d {{ $dt['we'] ? 'we' : '' }} {{ in_array('public', $kinds) ? 'hol' : (in_array('eve', $kinds) ? 'eve' : '') }} {{ in_array('season', $kinds) ? 'sea' : '' }} {{ $m1 ? 'm1' : '' }}" title="{{ $dt['day']->format('l j F') }}{{ $names ? ': ' . $names : '' }}">@if($i === 0 || $dt['day']->day === 1)<span class="mo">{{ $dt['day']->format('M') }}</span>@endif{{ substr($dt['day']->format('D'), 0, 1) }}<b>{{ $dt['day']->day }}</b></div>
                    @endforeach
                </div>
                <div class="cal-row all">
                    @foreach($d['dates'] as $i => $dt)@php $c = $d['total'][$dt['date']]; @endphp<span class="cal-c {{ $c['level'] }} {{ $i > 0 && $dt['day']->day === 1 ? 'm1' : '' }}" style="background:{{ $shade($c['pct']) }}" title="{{ $tip('all buildings', $dt, $c) }}"></span>@endforeach
                </div>
                @foreach($d['props'] as $p)
                <div class="cal-row">
                    @foreach($d['dates'] as $i => $dt)@php $c = $d['cells'][$p][$dt['date']]; @endphp<span class="cal-c {{ $c['level'] }} {{ $i > 0 && $dt['day']->day === 1 ? 'm1' : '' }}" @if($c['level'] !== 'na') style="background:{{ $shade($c['pct']) }}" @endif title="{{ $tip($p, $dt, $c) }}"></span>@endforeach
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="cal-key">
        <span><span class="sw">@foreach([0, 25, 50, 75, 100] as $v)<span class="d" style="background:{{ $shade($v) }}"></span>@endforeach</span> 0% to 100% sold</span>
        <span><span class="cal-c hot" style="background:{{ $shade(60) }}"></span> spike</span>
        <span><span class="cal-c warm" style="background:{{ $shade(30) }}"></span> filling early</span>
        <span><span class="cal-c soft" style="background:{{ $shade(10) }}"></span> slow, inside {{ R::SOFT_DAYS }} days</span>
        <span><span class="d" style="background:#dc2626"></span> public holiday</span>
        <span><span class="d" style="background:#fecaca"></span> eve</span>
        <span><span class="d" style="background:#a78bfa;height:3px"></span> school holidays</span>
    </div>
</div>

<div class="rd-card">
    <h2>Holidays coming up</h2>
    <p class="sub">Public holidays in Kuala Lumpur and the night before each one, for the next {{ R::DAYS }} days. A holiday night gets a starting rise even before it fills, because most bookings arrive late.</p>
    @if(count($d['watch']))
    <div class="rd-wrap">
        <table class="rd-table cards dm-hol" style="min-width:0">
            <thead><tr><th>Night of</th><th>Holiday</th><th>Sold now</th><th>Fullest building</th><th>Suggested</th></tr></thead>
            <tbody>
            @foreach($d['watch'] as $w)
            <tr>
                <td class="dm-when" data-l="Night of"><b>{{ $w['day']->format('D j M') }}</b><div class="rd-sub">{{ $togo($w['lead']) }}</div></td>
                <td data-l="Holiday"><span class="rd-b">{{ $w['name'] }}</span>
                    <div class="dm-tags"><span class="rd-chip {{ $w['kind'] === 'public' ? 'red' : 'grey' }}">{{ $w['kind'] === 'public' ? 'Public holiday' : 'Eve' }}</span>@if($w['longWeekend'])<span class="rd-chip teal">Long weekend</span>@endif @if($w['confirm'])<span class="rd-chip grey">Date to confirm</span>@endif</div></td>
                <td data-l="Sold now"><div class="dm-vs"><span class="now">{{ $w['pct'] }}%</span><span class="was">normal {{ $w['normal'] }}%</span></div>
                    <div class="rd-meter"><i style="width:{{ min(100, $w['pct']) }}%"></i><u style="left:calc({{ min(100, $w['normal']) }}% - 1px)"></u></div></td>
                <td data-l="Fullest building"><span class="rd-num">{{ $w['top'] ?: '–' }}</span></td>
                <td class="wide" data-l="Suggested">{!! $up($w['suggest']) !!}<div class="rd-sub">{{ in_array($w['level'], ['hot', 'warm']) ? 'Already filling ahead of normal.' : ($w['longWeekend'] ? 'Long weekend: set this before bookings arrive.' : 'Holiday night: set this before bookings arrive.') }}</div></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="rd-empty">No public holiday in the next {{ R::DAYS }} days.</div>
    @endif
    @if(count($d['seasons']))
    <div class="rd-note" style="border-top:1px solid var(--line)"><strong>School holidays in this period:</strong>
        @foreach($d['seasons'] as $i => $s){{ $i ? '; ' : ' ' }}{{ $s['name'] }}, {{ $s['from']->format('j M') }} to {{ $s['to']->format('j M Y') }}@endforeach.
        Families travel on weekdays too in these weeks, so weekday rates can follow weekend rates.
    </div>
    @endif
</div>

@if(count($d['soft']))
<div class="rd-card">
    <h2>Slow nights in the next {{ R::SOFT_DAYS }} days</h2>
    <p class="sub">Nights where the whole portfolio has sold less than is normal this close in. A promotion works better here than waiting.</p>
    <div class="rd-wrap">
        <table class="rd-table" style="min-width:0">
            <thead><tr><th>Night of</th><th>Sold now</th><th>Units left</th><th>Suggested</th></tr></thead>
            <tbody>
            @foreach($d['soft'] as $s)
            <tr><td class="dm-when"><b>{{ $s['day']->format('D j M') }}</b></td>
                <td><div class="dm-vs"><span class="now">{{ $s['pct'] }}%</span><span class="was">normal {{ $s['normal'] }}%</span></div></td>
                <td><span class="rd-num">{{ $s['sale'] - $s['sold'] }}</span></td>
                <td>{!! $up($s['suggest']) !!}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="rd-card">
    <details class="rd-note">
        <summary>How the suggestions are worked out</summary>
        <ul>
            <li><strong>Sold now</strong> is short-stay units sold out of short-stay units for sale that night. A unit on a stay of {{ R::LONG_STAY }} nights or more is not for sale, so it is left out.</li>
            <li><strong>Normal</strong> is what the same building had sold the same number of days ahead, on average over the last {{ R::HISTORY / 7 }} weeks. Friday and Saturday nights are measured apart from other nights. A building with fewer than five units uses the whole portfolio's pattern.</li>
            <li><strong>Filling early:</strong> {{ R::WARM }} points or more above normal. Suggest +10%.</li>
            <li><strong>Spike:</strong> {{ R::HOT }} points or more above normal, suggest +15%; 40 points or more, +20%. A night {{ R::NEAR_FULL }}% sold gets at least +20%, and 90% sold gets +25%.</li>
            <li><strong>Holiday nights:</strong> at least +{{ R::HOLIDAY_UP }}% for a public holiday or its eve, +{{ R::LONG_WEEKEND_UP }}% when the holiday makes a long weekend, before any spike.</li>
            <li><strong>Slow:</strong> inside {{ R::SOFT_DAYS }} days and {{ abs(R::SOFT) }} points or more below normal. Suggest a promotion or −10%.</li>
            <li><strong>These are starting points from simple rules.</strong> Homemoka knows the rates guests paid, not the rate on sale in eZee today, so apply the % to your current rate. The page cannot see events, competitors or weather. A building with fewer than {{ R::MIN_UNITS }} short-stay units for sale on a night is not rated.</li>
            <li>Holiday dates were checked on {{ \Carbon\Carbon::parse(config('holidays.checked'))->format('j F Y') }}. Dates marked "to confirm" follow the moon sighting or are not yet gazetted.</li>
        </ul>
    </details>
</div>
</div>
@endsection
