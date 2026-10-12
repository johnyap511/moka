@extends('admin.layout')
@section('title', 'Portfolio')
@section('page-title', 'Portfolio')

@push('styles')
<style>
.pf-tabs{display:flex;gap:4px;margin-bottom:12px}.pf-tabs a{padding:7px 14px;border-radius:999px;font-size:13px;text-decoration:none;color:var(--text-secondary);border:1px solid var(--border,#e5e7eb);background:#fff}.pf-tabs a.on{background:#0f766e;border-color:#0f766e;color:#fff;font-weight:600}
.pf-bar{display:grid;grid-template-columns:minmax(220px,1fr) 170px auto;gap:10px;align-items:center;background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:12px 14px;margin-bottom:14px}@media (max-width:700px){.pf-bar{grid-template-columns:1fr 1fr}}
.pf-head{display:flex;justify-content:space-between;align-items:baseline;gap:12px;flex-wrap:wrap;margin-bottom:10px}.pf-head h1{font-size:20px;margin:0}.pf-head span{font-size:12.5px;color:var(--text-secondary)}
.pf-kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;margin-bottom:14px}@media (max-width:1100px){.pf-kpis{grid-template-columns:repeat(3,minmax(0,1fr))}}@media (max-width:600px){.pf-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
.pf-kpi{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:12px 14px;display:grid;gap:3px;min-width:0}.pf-kpi .l{font-size:11.5px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.03em}.pf-kpi .v{font-size:clamp(16px,1.5vw,22px);font-weight:700;letter-spacing:-.3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.pf-kpi .s{font-size:11.5px;color:var(--text-secondary)}
.pf-kpi.hero{background:linear-gradient(135deg,#F36523,#ff8a4c);color:#fff}.pf-kpi.hero .l,.pf-kpi.hero .s{color:rgba(255,255,255,.85)}.pf-kpi.teal{background:linear-gradient(135deg,#0f766e,#14b8a6);color:#fff}.pf-kpi.teal .l,.pf-kpi.teal .s{color:rgba(255,255,255,.85)}
.pf-chg{display:inline-block;font-size:11px;font-weight:700;padding:1px 7px;border-radius:999px;background:rgba(0,0,0,.06);margin-left:4px}.pf-chg--up{background:#dcfce7;color:#166534}.pf-chg--down{background:#fee2e2;color:#991b1b}.hero .pf-chg,.teal .pf-chg{background:rgba(255,255,255,.22);color:#fff}
.pf-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:14px}@media (max-width:900px){.pf-grid{grid-template-columns:1fr}}.pf-grid--prop{grid-template-columns:minmax(0,1.4fr) minmax(0,1fr)}.pf-grid--prop .pf-table{font-size:12px}.pf-grid--prop .pf-table th,.pf-grid--prop .pf-table td{padding:6px 5px}.pf-grid3{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(0,1fr) minmax(0,1fr);gap:12px;margin-bottom:14px}@media (max-width:1100px){.pf-grid3{grid-template-columns:1fr}}
.pf-card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:14px 16px;min-width:0}.pf-card h3{font-size:13.5px;margin:0 0 10px;display:flex;justify-content:space-between;gap:8px}.pf-card h3 small{font-weight:400;color:var(--text-secondary)}
.pf-chart{position:relative;height:220px}.pf-chart--sm{height:200px}
.pf-table{width:100%;border-collapse:collapse;font-size:12.5px}.pf-table th,.pf-table td{padding:6px 7px;border-bottom:1px solid var(--border,#e5e7eb);text-align:left;white-space:nowrap}.pf-table td.wrap{white-space:normal}.pf-table th{font-size:11px;text-transform:uppercase;letter-spacing:.03em;color:var(--text-secondary);cursor:pointer;user-select:none}.pf-table th.sorted{color:#0f766e}.pf-table .num{text-align:right}.pf-table tbody tr:hover{background:#fafcfb}.pf-table a{color:inherit;text-decoration:none;font-weight:600}.pf-table a:hover{color:#0f766e}
.pf-mini{display:inline-block;width:30px;height:6px;background:#f1f5f9;border-radius:999px;overflow:hidden;vertical-align:middle;margin-left:8px}.pf-mini i{display:block;height:100%;background:#0f766e}
.pf-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:6px;vertical-align:middle}
.pf-rows{display:grid;gap:6px;font-size:13px}.pf-rows div{display:flex;justify-content:space-between;gap:10px}.pf-rows .k{color:var(--text-secondary)}.pf-rows .tot{border-top:1px solid var(--border,#e5e7eb);padding-top:6px;font-weight:700}
.pf-stack{display:flex;height:14px;border-radius:999px;overflow:hidden;margin:10px 0 6px;background:#f1f5f9}.pf-stack i{display:block;height:100%}
.pf-legend{display:flex;gap:12px;flex-wrap:wrap;font-size:11.5px;color:var(--text-secondary)}
.pf-note{font-size:11.5px;color:var(--text-secondary);margin-top:8px;line-height:1.4}
.pf-cmp{display:grid;gap:4px}.pf-cmp-row{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid var(--border,#e5e7eb)}.pf-cmp-row:last-child{border-bottom:0}.pf-cmp-l{font-size:13px}.pf-cmp-l small{display:block;font-size:11.5px;color:var(--text-secondary)}.pf-cmp-v{white-space:nowrap;font-size:13.5px;display:flex;align-items:center;gap:6px}.pf-cmp-v .pf-chg{margin:0}
.pf-wrap{overflow:auto}
tr.top td:first-child{box-shadow:inset 3px 0 #16a34a}tr.low td:first-child{box-shadow:inset 3px 0 #dc2626}
</style>
@endpush

@section('content')
@php
    $rm = fn ($v) => 'RM ' . number_format((float) $v, 2);
    $n = $d['now']; $cmp = collect($d['compare'])->keyBy('label');
    $chip = function ($c) use ($d) { if (!$c || $c['pct'] === null) return ''; $cls = $c['pct'] > 0 ? 'pf-chg--up' : ($c['pct'] < 0 ? 'pf-chg--down' : ''); return '<span class="pf-chg ' . $cls . '" title="vs ' . $d['lastYear'] . '">' . ($c['pct'] > 0 ? '+' : '') . number_format($c['pct'], 1) . '%</span>'; };
    $sp = $n['split']; $splitTotal = max(0.01, $sp['room'] + $sp['cleaning'] + $sp['sst']);
    $byUnit = $d['byUnit']; $top = array_slice(array_column($byUnit, 'id'), 0, 3); $low = array_slice(array_column(array_reverse(array_filter($byUnit, fn ($u) => true)), 'id'), 0, 3);
@endphp

<div class="pf-tabs"><a href="/admin/portfolio{{ $property ? '?property=' . urlencode($property) : '' }}" class="on">Portfolio</a><a href="/admin/unit-report">Unit report</a></div>

<form method="GET" action="/admin/portfolio" class="pf-bar">
    <select name="property" class="form-select" aria-label="Scope">
        <option value="">Whole portfolio · {{ $properties->flatten(1)->count() }} units</option>
        @foreach($properties as $p => $units)
        <option value="{{ $p }}" {{ $property === $p ? 'selected' : '' }}>{{ $p }} · {{ $units->count() }} units</option>
        @endforeach
    </select>
    <input type="month" name="date" class="form-input" value="{{ $sel->format('Y-m') }}" aria-label="Month">
    <button type="submit" class="btn btn-primary">Update</button>
</form>

<div class="pf-head"><h1>{{ $d['label'] }}</h1><span>{{ $d['units'] }} units · {{ $d['month'] }} · nights clipped to the month, room charges before SST · change vs {{ $d['lastYear'] }}</span></div>

<div class="pf-kpis">
    <div class="pf-kpi hero"><div class="l">Revenue</div><div class="v">{{ $rm($n['revenue']) }}</div><div class="s">room charges {!! $chip($cmp['Revenue']) !!}</div></div>
    <div class="pf-kpi teal"><div class="l">Occupancy</div><div class="v">{{ number_format($n['occupancy'], 1) }}%</div><div class="s">{{ number_format($n['nights']) }} of {{ number_format($n['available']) }} nights {!! $chip($cmp['Occupancy']) !!}</div></div>
    <div class="pf-kpi"><div class="l">RevPAR</div><div class="v">{{ $rm($n['revpar']) }}</div><div class="s">per available night {!! $chip($cmp['RevPAR']) !!}</div></div>
    <div class="pf-kpi"><div class="l">ADR</div><div class="v">{{ $rm($n['adr']) }}</div><div class="s">per night sold {!! $chip($cmp['ADR']) !!}</div></div>
    <div class="pf-kpi"><div class="l">Bookings</div><div class="v">{{ number_format($n['bookings']) }}</div><div class="s">touching the month {!! $chip($cmp['Bookings']) !!}</div></div>
    <div class="pf-kpi"><div class="l">Unsold nights</div><div class="v">{{ number_format(max(0, $n['available'] - $n['nights'])) }}</div><div class="s">{{ $d['units'] }} units × {{ $sel->daysInMonth }} days</div></div>
</div>

<div class="pf-grid">
    <div class="pf-card"><h3>Revenue, last 6 months <small>room charges</small></h3><div class="pf-chart"><canvas id="pf-rev"></canvas></div></div>
    <div class="pf-card"><h3>Occupancy and RevPAR, last 6 months</h3><div class="pf-chart"><canvas id="pf-occ"></canvas></div></div>
</div>

<div class="pf-grid3">
    <div class="pf-card">
        <h3>Revenue by channel <small>{{ $d['month'] }}</small></h3>
        @php $chTop = $n['channels']->take(7); $chRest = $n['channels']->slice(7); @endphp
        <div style="display:grid;grid-template-columns:124px minmax(0,1fr);gap:12px;align-items:center">
            <div class="pf-chart pf-chart--sm" style="height:124px"><canvas id="pf-ch"></canvas></div>
            <table class="pf-table" style="font-size:12px">
                <thead><tr><th>Channel</th><th class="num">Revenue</th><th class="num">Share</th></tr></thead>
                <tbody>
                @foreach($chTop as $c)
                <tr><td><span class="pf-dot" style="background:{{ $d['colours'][$c['channel']] ?? '#94a3b8' }}"></span>{{ $c['channel'] }}</td><td class="num">{{ $rm($c['revenue']) }}</td><td class="num"><b>{{ number_format($c['share'], 1) }}%</b></td></tr>
                @endforeach
                @if($chRest->count())
                <tr><td><span class="pf-dot" style="background:#cbd5e1"></span>{{ $chRest->count() }} other channels</td><td class="num">{{ $rm($chRest->sum('revenue')) }}</td><td class="num"><b>{{ number_format($chRest->sum('share'), 1) }}%</b></td></tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
    <div class="pf-card">
        <h3>Guest spend <small>{{ $sel->format('M Y') }} rows</small></h3>
        <div class="pf-stack"><i style="width:{{ $sp['room'] / $splitTotal * 100 }}%;background:#0f766e"></i><i style="width:{{ $sp['cleaning'] / $splitTotal * 100 }}%;background:#f59e0b"></i><i style="width:{{ $sp['sst'] / $splitTotal * 100 }}%;background:#94a3b8"></i></div>
        <div class="pf-legend"><span><span class="pf-dot" style="background:#0f766e"></span>Room</span><span><span class="pf-dot" style="background:#f59e0b"></span>Cleaning</span><span><span class="pf-dot" style="background:#94a3b8"></span>SST</span></div>
        <div class="pf-rows" style="margin-top:10px">
            <div><span class="k">Room charges</span><b>{{ $rm($sp['room']) }}</b></div>
            <div><span class="k">Cleaning fees</span><span>{{ $rm($sp['cleaning']) }}</span></div>
            <div><span class="k">SST collected</span><span>{{ $rm($sp['sst']) }}</span></div>
            <div class="tot"><span>Charged to guests</span><span>{{ $rm($sp['gross']) }}</span></div>
            <div><span class="k">Channel / OTA fees</span><span>− {{ $rm($sp['fee']) }}</span></div>
        </div>
    </div>
    <div class="pf-card">
        <h3>Same month last year <small>vs {{ $d['lastYear'] }}</small></h3>
        <div class="pf-cmp">
            @foreach($d['compare'] as $c)
            @php $f = fn ($v) => $c['kind'] === 'rm' ? $rm($v) : ($c['kind'] === 'pct' ? number_format($v, 1) . '%' : number_format($v)); @endphp
            <div class="pf-cmp-row"><div class="pf-cmp-l">{{ $c['label'] }}<small>{{ $d['lastYear'] }}: {{ $f($c['then']) }}</small></div><div class="pf-cmp-v"><b>{{ $f($c['now']) }}</b>{!! $chip($c) !!}</div></div>
            @endforeach
        </div>
    </div>
</div>

@if($property === '' && $d['byProperty']->count() > 1)
<div class="pf-grid pf-grid--prop">
    <div class="pf-card pf-wrap">
        <h3>By property <small>{{ $d['month'] }}</small></h3>
        <table class="pf-table">
            <thead><tr><th>Property</th><th class="num">Units</th><th class="num">Occ.</th><th class="num">Revenue</th><th class="num">ADR</th><th class="num">RevPAR</th></tr></thead>
            <tbody>
            @foreach($d['byProperty'] as $p)
            <tr><td><a href="/admin/portfolio?property={{ urlencode($p['property']) }}&date={{ $sel->format('Y-m') }}">{{ $p['property'] }}</a></td><td class="num">{{ $p['units'] }}</td><td class="num">{{ number_format($p['occupancy'], 1) }}%<span class="pf-mini"><i style="width:{{ min(100, $p['occupancy']) }}%"></i></span></td><td class="num"><b>{{ $rm($p['revenue']) }}</b></td><td class="num">{{ number_format($p['adr'], 0) }}</td><td class="num">{{ number_format($p['revpar'], 0) }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="pf-card"><h3>Revenue by property</h3><div class="pf-chart"><canvas id="pf-prop"></canvas></div></div>
</div>
@endif

<div class="pf-card pf-wrap">
    <h3>By unit <small>{{ count($byUnit) }} units · sorted by revenue, click a heading to re-sort · green top 3, red bottom 3</small></h3>
    <table class="pf-table" id="pf-units">
        <thead><tr><th data-k="name">Unit</th><th data-k="owner">Owner</th><th class="num" data-k="nights">Nights</th><th class="num" data-k="occupancy">Occ.</th><th class="num sorted" data-k="revenue">Revenue ↓</th><th class="num" data-k="adr">ADR</th><th class="num" data-k="revpar">RevPAR</th><th class="num" data-k="bookings">Bkgs</th></tr></thead>
        <tbody>
        @foreach($byUnit as $u)
        <tr class="{{ in_array($u['id'], $top) ? 'top' : (in_array($u['id'], $low) && count($byUnit) > 6 ? 'low' : '') }}" data-name="{{ $u['name'] }}" data-owner="{{ $u['owner'] }}" data-nights="{{ $u['nights'] }}" data-occupancy="{{ $u['occupancy'] }}" data-revenue="{{ $u['revenue'] }}" data-adr="{{ $u['adr'] }}" data-revpar="{{ $u['revpar'] }}" data-bookings="{{ $u['bookings'] }}">
            <td><a href="/admin/unit-report?listing_id={{ $u['id'] }}&date={{ $sel->format('Y-m') }}">{{ $u['name'] }}</a></td>
            <td class="wrap" style="color:var(--text-secondary);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $u['owner'] }}</td>
            <td class="num">{{ $u['nights'] }}</td>
            <td class="num">{{ number_format($u['occupancy'], 1) }}%<span class="pf-mini"><i style="width:{{ min(100, $u['occupancy']) }}%"></i></span></td>
            <td class="num"><b>{{ $rm($u['revenue']) }}</b></td>
            <td class="num">{{ number_format($u['adr'], 0) }}</td>
            <td class="num">{{ number_format($u['revpar'], 0) }}</td>
            <td class="num">{{ $u['bookings'] }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    var trend = @json($d['trend']), labels = trend.map(function(t){ return t.label; });
    var money = function(v){ return 'RM ' + Number(v).toLocaleString('en-MY', {maximumFractionDigits: 0}); };
    new Chart(document.getElementById('pf-rev'), { type: 'bar', data: { labels: labels, datasets: [{ data: trend.map(function(t){ return t.revenue; }), backgroundColor: '#F36523', borderRadius: 6, maxBarThickness: 42 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(c){ return money(c.raw); } } } }, scales: { y: { ticks: { callback: money }, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } } });
    new Chart(document.getElementById('pf-occ'), { type: 'line', data: { labels: labels, datasets: [
        { label: 'Occupancy %', data: trend.map(function(t){ return t.occupancy; }), borderColor: '#0f766e', backgroundColor: 'rgba(15,118,110,.12)', fill: true, tension: .35, yAxisID: 'y', pointRadius: 3.5 },
        { label: 'RevPAR (RM)', data: trend.map(function(t){ return t.revpar; }), borderColor: '#F36523', tension: .35, yAxisID: 'y1', pointRadius: 3.5 } ] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }, scales: { y: { min: 0, max: 100, ticks: { callback: function(v){ return v + '%'; } }, grid: { color: '#f1f5f9' } }, y1: { position: 'right', min: 0, grid: { display: false } }, x: { grid: { display: false } } } } });
    var ch = @json($n['channels']), colours = @json($d['colours']);
    new Chart(document.getElementById('pf-ch'), { type: 'doughnut', data: { labels: ch.map(function(c){ return c.channel; }), datasets: [{ data: ch.length ? ch.map(function(c){ return c.revenue; }) : [1], backgroundColor: ch.length ? ch.map(function(c){ return colours[c.channel] || '#94a3b8'; }) : ['#eceef1'], borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(c){ return c.label + ': ' + money(c.raw); } } } } } });
    var propEl = document.getElementById('pf-prop');
    if (propEl) { var props = @json($d['byProperty']); new Chart(propEl, { type: 'bar', data: { labels: props.map(function(p){ return p.property; }), datasets: [{ data: props.map(function(p){ return p.revenue; }), backgroundColor: '#0f766e', borderRadius: 6, maxBarThickness: 26 }] },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(c){ return money(c.raw); } } } }, scales: { x: { ticks: { callback: money }, grid: { color: '#f1f5f9' } }, y: { grid: { display: false } } } } }); }
    // sortable unit table
    var table = document.getElementById('pf-units'), tbody = table.querySelector('tbody'), dir = {};
    table.querySelectorAll('th[data-k]').forEach(function(th){
        th.addEventListener('click', function(){
            var k = th.dataset.k, num = th.classList.contains('num'); dir[k] = dir[k] === 'asc' ? 'desc' : 'asc';
            var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
            rows.sort(function(a, b){ var x = a.dataset[k], y = b.dataset[k]; if (num) { x = parseFloat(x) || 0; y = parseFloat(y) || 0; return dir[k] === 'asc' ? x - y : y - x; } return dir[k] === 'asc' ? x.localeCompare(y) : y.localeCompare(x); });
            rows.forEach(function(r){ tbody.appendChild(r); });
            table.querySelectorAll('th').forEach(function(t){ t.classList.remove('sorted'); }); th.classList.add('sorted');
        });
    });
})();
</script>
@endpush
