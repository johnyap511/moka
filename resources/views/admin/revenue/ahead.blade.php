@extends('admin.layout')
@section('title', 'Revenue desk')
@section('page-title', 'Revenue desk')

@push('styles')
@include('admin.revenue._style')
<style>
.ah-bar{display:flex;justify-content:space-between;align-items:center;gap:10px 16px;flex-wrap:wrap;padding:12px 16px}
.ah-tabs{display:flex;gap:4px;flex-wrap:wrap}.ah-tabs button{padding:7px 13px;border-radius:999px;font:inherit;font-size:13px;color:var(--mute);border:1px solid var(--line);background:#fff;cursor:pointer}.ah-tabs button:hover{border-color:#94a3b8}.ah-tabs button.on{background:var(--teal);border-color:var(--teal);color:#fff;font-weight:600}.ah-tabs button b{font-weight:600;opacity:.75;margin-left:3px}
.ah-tools{display:flex;gap:8px;flex-wrap:wrap}.ah-tools select,.ah-tools input{padding:8px 11px;border:1px solid var(--line);border-radius:10px;font:inherit;font-size:13px;background:#fff;max-width:100%}
.ah-m{display:flex;align-items:center;gap:8px;white-space:nowrap}.ah-m .rd-meter{margin:0;width:70px}.ah-m span{font-weight:600;font-variant-numeric:tabular-nums;min-width:36px}
@media (max-width:900px){.ah-tools,.ah-tools select,.ah-tools input{width:100%}}
</style>
@endpush

@section('content')
@php
    use App\Support\RevenueDesk as R;
    $today = $d['today']; $k = $d['kpi'];
    $days = fn ($n) => $n === null ? '–' : ($n === 0 ? 'Same day' : ($n === 1 ? '1 day ahead' : $n . ' days ahead'));
    $meter = fn ($p, $long = false) => '<div class="ah-m"><span>' . $p . '%</span><div class="rd-meter"><i class="' . ($long ? 'long' : '') . '" style="width:' . min(100, $p) . '%"></i></div></div>';
    $kind = fn ($u) => $u['until'] ? 'long' : ($u['n90'] === 0 ? 'none' : ($u['last'] && $u['last']->gt($today->copy()->addDays(30)) ? 'far' : 'near'));
    $counts = collect($d['units'])->countBy($kind);
@endphp

<div class="rd">
@include('admin.revenue._tabs', ['tab' => 'ahead'])

<div class="rd-head">
    <h1>Booked ahead</h1>
    <p>{{ $today->format('l, j F Y') }} · {{ $k['units'] }} units trading · every booking on the books, long stays included @isset($at) · figures from {{ $at->format('g:i a') }}@endisset</p>
</div>

<div class="rd-kpis">
    <div class="rd-kpi"><div class="l">Booked, next 30 nights</div><div class="v">{{ $k['p30'] }}%</div><div class="s">of all unit-nights</div></div>
    <div class="rd-kpi"><div class="l">Booked, next 90 nights</div><div class="v">{{ $k['p90'] }}%</div><div class="s">of all unit-nights</div></div>
    <div class="rd-kpi"><div class="l">On a long stay</div><div class="v">{{ $k['longUnits'] }}<small>of {{ $k['units'] }} units</small></div><div class="s">stays of {{ R::LONG_STAY }} nights or more</div></div>
    <div class="rd-kpi"><div class="l">Guests book</div><div class="v sm">{{ $days($k['lead']) }}</div><div class="s">typical short stay, last {{ R::HISTORY / 7 }} weeks</div></div>
</div>

@if($k['sameDay'] !== null)
<div class="rd-insight"><b>{{ $k['sameDay'] }}% of short stays are booked on the day or the day before; {{ $k['early'] }}% a week or more ahead.</b> A unit with short-stay bookings beyond 30 days is the exception, and worth a look: its rate may be too low for those dates.</div>
@endif

<div class="rd-card">
    <h2>By building</h2>
    <p class="sub">How much of each building is already booked, and how far ahead its short-stay guests book.</p>
    <div class="rd-wrap">
        <table class="rd-table cards" style="min-width:0">
            <thead><tr><th>Building</th><th>Next 30 nights</th><th>Next 60</th><th>Next 90</th><th>On a long stay</th><th>Guests book</th><th>Booked a week or more ahead</th></tr></thead>
            <tbody>
            @foreach($d['byProperty'] as $p)
            <tr>
                <td class="wide"><span class="rd-b">{{ $p['property'] }}</span><div class="rd-sub">{{ $p['units'] }} unit{{ $p['units'] > 1 ? 's' : '' }}</div></td>
                <td data-l="Next 30 nights">{!! $meter($p['p30']) !!}</td>
                <td data-l="Next 60">{!! $meter($p['p60']) !!}</td>
                <td data-l="Next 90">{!! $meter($p['p90']) !!}</td>
                <td data-l="On a long stay"><span class="rd-num">{{ $p['longUnits'] }}</span> <span class="rd-mute">of {{ $p['units'] }}</span></td>
                <td data-l="Guests book"><span class="rd-num">{{ $days($p['lead']) }}</span>@if($p['sameDay'] !== null)<div class="rd-sub">{{ $p['sameDay'] }}% within a day</div>@endif</td>
                <td data-l="Booked a week or more ahead"><span class="rd-num">{{ $p['early'] === null ? '–' : $p['early'] . '%' }}</span></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="rd-card">
    <h2 style="padding-bottom:0">By unit</h2>
    <div class="ah-bar">
        <div class="ah-tabs" id="ah-tabs">
            <button type="button" data-f="far" class="on">Short stays booked far ahead <b>{{ $counts['far'] ?? 0 }}</b></button>
            <button type="button" data-f="long">On a long stay <b>{{ $counts['long'] ?? 0 }}</b></button>
            <button type="button" data-f="near">Booked close in only <b>{{ $counts['near'] ?? 0 }}</b></button>
            <button type="button" data-f="none">Nothing booked <b>{{ $counts['none'] ?? 0 }}</b></button>
            <button type="button" data-f="all">All <b>{{ count($d['units']) }}</b></button>
        </div>
        <div class="ah-tools">
            <select id="ah-b" aria-label="Building"><option value="">All buildings</option>@foreach($d['byProperty'] as $p)<option>{{ $p['property'] }}</option>@endforeach</select>
            <input type="search" id="ah-q" placeholder="Find a unit" autocomplete="off" aria-label="Find a unit">
        </div>
    </div>
    <div class="rd-wrap">
        <table class="rd-table cards" id="ah-table" style="min-width:0">
            <thead><tr><th data-k="name">Unit</th><th data-k="n30">Next 30 nights</th><th data-k="n60">Next 60</th><th data-k="n90">Next 90</th><th data-k="last">Booked up to</th><th data-k="lead">Guests book</th></tr></thead>
            <tbody>
            @foreach($d['units'] as $u)
            <tr data-kind="{{ $kind($u) }}" data-property="{{ $u['property'] }}" data-name="{{ $u['name'] }}" data-n30="{{ $u['n30'] }}" data-n60="{{ $u['n60'] }}" data-n90="{{ $u['n90'] }}" data-last="{{ $u['last'] ? $u['last']->format('Ymd') : 0 }}" data-lead="{{ $u['lead'] ?? -1 }}">
                <td class="wide"><span class="rd-b">@if($canUnitReport)<a href="/admin/unit-report?listing_id={{ $u['id'] }}">{{ $u['name'] }}</a>@else{{ $u['name'] }}@endif</span><div class="rd-sub">{{ $u['room'] ?: $u['property'] }}</div></td>
                <td data-l="Next 30 nights">{!! $meter($u['p30'], (bool) $u['until']) !!}</td>
                <td data-l="Next 60">{!! $meter($u['p60'], (bool) $u['until']) !!}</td>
                <td data-l="Next 90">{!! $meter($u['p90'], (bool) $u['until']) !!}</td>
                <td data-l="Booked up to">@if($u['last'])<span class="rd-num">{{ $u['last']->format('j M Y') }}</span><div class="rd-sub">@if($u['until'])<span class="rd-chip grey">Long stay</span>@else {{ $today->diffInDays($u['last']) }} days from now @endif</div>@else<span class="rd-mute">–</span>@endif</td>
                <td data-l="Guests book"><span class="rd-num">{{ $days($u['lead']) }}</span>@if($u['stays'])<div class="rd-sub">{{ $u['stays'] }} short stay{{ $u['stays'] > 1 ? 's' : '' }}, {{ $u['early'] }}% a week ahead</div>@endif</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        <div class="rd-empty" id="ah-none" hidden>No units here. Try another tab or building.</div>
    </div>
</div>

<div class="rd-card">
    <details class="rd-note">
        <summary>How to read this page</summary>
        <ul>
            <li><strong>Next 30, 60 and 90 nights:</strong> nights already booked out of the nights in that period, starting tonight. A grey bar is a unit on a long stay.</li>
            <li><strong>Long stay:</strong> one stay of {{ R::LONG_STAY }} nights or more. A monthly rental keyed month by month in eZee shows as booked only up to the last month keyed.</li>
            <li><strong>Guests book:</strong> the typical number of days between a short stay being booked and its check-in, from short stays that checked in since {{ $d['since']->format('j F') }}. Booking times are reliable from late September 2026, when reservations began arriving from eZee every hour.</li>
            <li><strong>Short stays booked far ahead:</strong> units with a short stay ending more than 30 days from now. Check whether those dates were sold too cheaply.</li>
        </ul>
    </details>
</div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    var table = document.getElementById('ah-table'); if (!table) return;
    var body = table.tBodies[0], tabs = document.getElementById('ah-tabs'), sel = document.getElementById('ah-b'), q = document.getElementById('ah-q'), none = document.getElementById('ah-none');
    var filter = 'far';
    function apply(){
        var shown = 0, text = q.value.trim().toLowerCase();
        Array.prototype.forEach.call(body.rows, function(r){
            var ok = (!sel.value || r.dataset.property === sel.value) && (text ? r.dataset.name.toLowerCase().indexOf(text) !== -1 : (filter === 'all' || r.dataset.kind === filter));
            r.hidden = !ok; if (ok) shown++;
        });
        none.hidden = shown > 0;
    }
    tabs.addEventListener('click', function(e){
        var b = e.target.closest('button'); if (!b) return;
        filter = b.dataset.f; q.value = '';
        Array.prototype.forEach.call(tabs.children, function(x){ x.classList.toggle('on', x === b); });
        apply();
    });
    sel.addEventListener('change', apply); q.addEventListener('input', apply);
    var dir = {};
    table.querySelectorAll('th[data-k]').forEach(function(th){
        th.addEventListener('click', function(){
            var k = th.dataset.k, text = k === 'name';
            dir[k] = dir[k] ? (dir[k] === 'asc' ? 'desc' : 'asc') : (text ? 'asc' : 'desc');
            var rows = Array.prototype.slice.call(body.rows);
            rows.sort(function(a, b){
                var x = a.dataset[k], y = b.dataset[k];
                if (text) return dir[k] === 'asc' ? x.localeCompare(y) : y.localeCompare(x);
                x = parseFloat(x) || 0; y = parseFloat(y) || 0; return dir[k] === 'asc' ? x - y : y - x;
            });
            rows.forEach(function(r){ body.appendChild(r); });
            table.querySelectorAll('th').forEach(function(t){ t.classList.remove('sorted'); }); th.classList.add('sorted');
        });
    });
    apply();
})();
</script>
@endpush
