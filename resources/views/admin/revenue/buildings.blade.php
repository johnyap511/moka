@extends('admin.layout')
@section('title', 'Revenue desk')
@section('page-title', 'Revenue desk')

@push('styles')
@include('admin.revenue._style')
<style>
.bd-v{min-width:220px}.bd-v p{margin:6px 0 0;font-size:12.5px;line-height:1.4;color:#44403c}
.bd-m{display:flex;align-items:center;gap:8px;white-space:nowrap}.bd-m .rd-meter{margin:0;width:80px}.bd-m span{font-weight:600;font-variant-numeric:tabular-nums;min-width:36px}
</style>
@endpush

@section('content')
@php
    use App\Support\RevenueDesk as R;
    $today = $d['today']; $rows = collect($d['rows']); $n = R::HISTORY;
    $label = ['strong' => ['Add units here', 'green'], 'good' => ['Well booked', 'teal'], 'long' => ['Long stays', 'grey'], 'steady' => ['Steady', 'warm'], 'weak' => ['Fill first', 'red'], 'few' => ['Too few to judge', 'grey']];
    $say = function ($r) use ($n) {
        return [
            'strong' => 'Sold out or nearly on ' . $r['full'] . ' of the last ' . $n . ' nights. More units of this type would likely sell.',
            'good'   => 'Well booked, but rarely sold out. One or two more units could work; watch it first.',
            'long'   => $r['longShare'] . '% of nights were long stays. Steady income; judge new units on long-stay demand, not on nightly bookings.',
            'steady' => 'Sells, but with nights to spare. Fill the current units before adding more.',
            'weak'   => 'Under half of short-stay nights sold. Do not add units of this type here yet.',
            'few'    => 'Only ' . $r['units'] . ' unit' . ($r['units'] > 1 ? 's' : '') . ' of this type, so the figures swing too much to rely on.',
        ][$r['verdict']];
    };
    $strong = $rows->where('verdict', 'strong'); $weak = $rows->where('verdict', 'weak');
@endphp

<div class="rd">
@include('admin.revenue._tabs', ['tab' => 'buildings'])

<div class="rd-head">
    <h1>Where demand is</h1>
    <p>{{ $today->format('l, j F Y') }} · by building and room type · short-stay nights over the last {{ $n / 7 }} weeks, since {{ $d['since']->format('j F') }}</p>
</div>

<div class="rd-kpis">
    <div class="rd-kpi"><div class="l">Add units here</div><div class="v">{{ $strong->count() }}<small>room types</small></div><div class="s">{{ $strong->sum('units') }} units today, often sold out</div></div>
    <div class="rd-kpi"><div class="l">Fill first</div><div class="v">{{ $weak->count() }}<small>room types</small></div><div class="s">{{ $weak->sum('units') }} units, under half sold</div></div>
    <div class="rd-kpi"><div class="l">Strongest building</div><div class="v sm">{{ $d['byProperty']->first()['property'] ?? '–' }}</div><div class="s">{{ $d['byProperty']->first()['pct'] ?? '–' }}% of short-stay nights sold</div></div>
    <div class="rd-kpi"><div class="l">Weakest building</div><div class="v sm">{{ $d['byProperty']->whereNotNull('pct')->last()['property'] ?? '–' }}</div><div class="s">{{ $d['byProperty']->whereNotNull('pct')->last()['pct'] ?? '–' }}% of short-stay nights sold</div></div>
</div>

<div class="rd-card">
    <h2>By building and room type</h2>
    <p class="sub">Strongest first. "Sold out" counts nights when nine in ten of that type's short-stay units were taken: the clearest sign that more units would sell.</p>
    <div class="rd-wrap">
        <table class="rd-table cards" style="min-width:0">
            <thead><tr><th>Building and room type</th><th>Short-stay nights sold</th><th>Sold-out nights</th><th>On long stays</th><th>Rate a night</th><th>Revenue per unit</th><th>What it means</th></tr></thead>
            <tbody>
            @foreach($d['rows'] as $r)
            <tr>
                <td class="wide"><span class="rd-b">{{ $r['property'] }}</span><div class="rd-sub">{{ $r['room'] }} · {{ $r['units'] }} unit{{ $r['units'] > 1 ? 's' : '' }}</div></td>
                <td data-l="Short-stay nights sold">@if($r['pct'] !== null)<div class="bd-m"><span>{{ $r['pct'] }}%</span><div class="rd-meter"><i style="width:{{ min(100, $r['pct']) }}%"></i></div></div>@else<span class="rd-mute">None for sale</span>@endif</td>
                <td data-l="Sold-out nights"><span class="rd-num">{{ $r['full'] }}</span> <span class="rd-mute">of {{ $r['days'] }}</span></td>
                <td data-l="On long stays"><span class="rd-num">{{ $r['longShare'] }}%</span><div class="rd-sub">of all nights</div></td>
                <td data-l="Rate a night"><span class="rd-num">{{ $r['rate'] ? 'RM ' . number_format($r['rate']) : '–' }}</span></td>
                <td data-l="Revenue per unit"><span class="rd-num">{{ $r['revUnit'] !== null ? 'RM ' . number_format($r['revUnit']) : '–' }}</span><div class="rd-sub">{{ $d['month'] }} so far</div></td>
                <td class="bd-v wide" data-l="What it means"><span class="rd-chip {{ $label[$r['verdict']][1] }}">{{ $label[$r['verdict']][0] }}</span><p>{{ $say($r) }}</p></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="rd-card">
    <h2>By building</h2>
    <div class="rd-wrap">
        <table class="rd-table" style="min-width:0">
            <thead><tr><th>Building</th><th>Units trading</th><th>Room types</th><th>Short-stay nights sold</th><th>Types to add</th><th>Types to fill first</th></tr></thead>
            <tbody>
            @foreach($d['byProperty'] as $p)
            <tr><td><span class="rd-b">{{ $p['property'] }}</span></td><td><span class="rd-num">{{ $p['units'] }}</span></td><td>{{ $p['types'] }}</td>
                <td>@if($p['pct'] !== null)<div class="bd-m"><span>{{ $p['pct'] }}%</span><div class="rd-meter"><i style="width:{{ min(100, $p['pct']) }}%"></i></div></div>@else<span class="rd-mute">Long stays or too few units</span>@endif</td>
                <td>{{ $p['strong'] ?: '–' }}</td><td>{{ $p['weak'] ?: '–' }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="rd-card">
    <details class="rd-note">
        <summary>How to read this page</summary>
        <ul>
            <li><strong>Short-stay nights sold:</strong> nights sold out of nights for sale over the last {{ $n }} nights. A unit on a stay of {{ R::LONG_STAY }} nights or more is not for sale, so it is left out.</li>
            <li><strong>Add units here:</strong> 70% or more sold, and sold out on at least 14 of the {{ $n }} nights, with three or more units of that type.</li>
            <li><strong>Fill first:</strong> under 50% sold.</li>
            <li><strong>Rate and revenue per unit</strong> are averages for the room type this month so far: nights × nightly rate, before SST, cleaning and channel fees. They are not what the owner receives.</li>
            <li><strong>What this page cannot see:</strong> guests who looked and could not book. A sold-out night shows demand was at least that high, not how much higher. It also does not know the rent or terms an owner would ask.</li>
            <li>Room types are eZee's own names. Units with no bookings in four months are left out.</li>
        </ul>
    </details>
</div>
</div>
@endsection
