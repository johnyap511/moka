@extends($layout ?? 'owner.layout')
@section('title', $pageTitle ?? 'Dashboard')
@section('page-title', $pageTitle ?? 'Dashboard')

@php
    // One palette for channels and categories, shared with the calendar.
    $channelColour = \App\Support\Channel::colours();
    $catColours    = ['Vacation' => '#F36523', 'Co-living' => '#0a8a72', 'Event' => '#7c3aed', 'Tours' => '#0891b2', 'Business' => '#1d4ed8', 'Others' => '#64748b'];
    $isPool        = !empty($pool);
    $fmtW          = fn ($w) => rtrim(rtrim(number_format($w, 2), '0'), '.');
@endphp

@push('styles')
<style>
.db-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px}
.db-head h1{font-size:24px;font-weight:700;letter-spacing:-.4px}
.db-head p{color:var(--text-secondary);margin-top:4px;font-size:14px}
.db-toolbar{display:grid;grid-template-columns:minmax(220px,1fr) 190px auto;gap:10px;align-items:center;background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:12px 14px;margin-bottom:18px}
.db-toolbar .btn{padding:10px 22px;font-size:13.5px;font-weight:600;justify-content:center}
@media (max-width:700px){.db-toolbar{grid-template-columns:1fr 1fr}.db-toolbar .db-combo{grid-column:1/-1}}
.db-combo{position:relative;min-width:0}.db-combo input[type=text]{width:100%;padding-right:34px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236b7280' viewBox='0 0 16 16'%3E%3Cpath d='M4 6l4 4 4-4'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center}
.db-combo-list{position:absolute;z-index:40;top:calc(100% + 4px);left:0;right:0;max-height:340px;overflow:auto;background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:10px;box-shadow:0 10px 30px -10px rgba(0,0,0,.25);padding:4px}
.db-combo-group{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary);padding:8px 10px 3px}.db-combo-opt{padding:7px 10px;border-radius:7px;cursor:pointer;font-size:13.5px}.db-combo-opt span{color:var(--text-secondary)}.db-combo-opt:hover,.db-combo-opt.is-act{background:#f0fdfa;color:#0f766e}.db-combo-opt.is-sel{font-weight:600}.db-combo-empty{padding:12px 10px;color:var(--text-secondary);font-size:13px}
.kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:18px}
.kpi{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:16px 16px 14px;display:flex;flex-direction:column;gap:6px;min-width:0}
.kpi .ic{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;margin-bottom:4px}
.kpi .ic svg{width:18px;height:18px}
.kpi .v{font-size:clamp(14px,1.3vw,22px);font-weight:700;letter-spacing:-.4px;line-height:1.15;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.kpi .l{font-size:12.5px;color:var(--text-secondary);font-weight:500}
.kpi .s{font-size:11.5px;color:var(--text-secondary)}
.kpi.hero{background:linear-gradient(135deg,#F36523,#ff8a4c);color:#fff}
.kpi.hero .l,.kpi.hero .s{color:rgba(255,255,255,.85)}
.kpi.hero .ic{background:rgba(255,255,255,.2);color:#fff}
.kpi.teal{background:linear-gradient(135deg,#0a8a72,#2fb59b);color:#fff}
.kpi.teal .l,.kpi.teal .s{color:rgba(255,255,255,.85)}
.kpi.teal .ic{background:rgba(255,255,255,.2);color:#fff}
.kpi.dark{background:linear-gradient(135deg,#0f3d3a,#14524e);color:#fff}
.kpi.dark .l,.kpi.dark .s{color:rgba(255,255,255,.8)}
.kpi.dark .ic{background:rgba(255,255,255,.15);color:#fff}
.kpi.plain .ic{background:#fff7ed;color:#F36523}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
.grid-3{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) minmax(0,1.4fr);gap:14px;margin-bottom:14px}
.panel{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:18px 20px;min-width:0}
.panel h3{font-size:13px;font-weight:600;color:var(--text);margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:8px}
.panel h3 small{font-weight:500;color:var(--text-secondary);font-size:12px}
.donut{display:grid;grid-template-columns:118px minmax(0,1fr);gap:12px;align-items:center}
.donut .chart-box{position:relative;height:118px;width:118px}
.legend{display:flex;flex-direction:column;gap:5px;font-size:11.5px;min-width:0}
.legend .row{display:grid;grid-template-columns:10px minmax(0,1fr) auto auto;align-items:center;gap:6px;min-width:0}
.legend .dot{width:10px;height:10px;border-radius:3px;flex-shrink:0}
.legend .name{color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}
.legend .pct{font-weight:600;color:var(--text);font-variant-numeric:tabular-nums}
.legend .cnt{color:var(--text-secondary);font-variant-numeric:tabular-nums;min-width:18px;text-align:right}
.legend .empty{color:var(--text-secondary);font-size:12.5px}
.bars{display:grid;grid-template-columns:1fr 1fr;gap:6px 26px}
.bar{display:grid;grid-template-columns:82px 1fr 40px;align-items:center;gap:8px;font-size:12px;padding:3px 0}
.bar .name{color:var(--text);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bar .track{height:8px;border-radius:8px;background:#f0f0f2;overflow:hidden}
.bar .fill{height:100%;border-radius:8px}
.bar .pct{color:var(--text-secondary);text-align:right;font-variant-numeric:tabular-nums}
.trend .chart-box{position:relative;height:240px}
canvas{display:block}
@media (max-width:1280px){.kpis{grid-template-columns:repeat(3,minmax(0,1fr))}.grid-3{grid-template-columns:1fr 1fr}.grid-3 .panel:last-child{grid-column:1 / -1}}
@media (max-width:1024px){.db-toolbar{grid-template-columns:1fr 170px auto}}
@media (max-width:700px){
  .db-toolbar{grid-template-columns:1fr}
  .kpis{grid-template-columns:1fr 1fr;gap:10px}
  .kpi .v{font-size:19px}
  .grid-2,.grid-3{grid-template-columns:1fr}
  .donut{grid-template-columns:130px 1fr}.donut .chart-box{height:130px;width:130px}
  .bars{grid-template-columns:1fr}
  .trend .chart-box{height:200px}
  .db-head h1{font-size:20px}
}
</style>
@endpush

@section('content')

@if(!empty($extras) && admin_is_super())
<div class="pf-tabs" style="display:flex;gap:4px;margin-bottom:12px"><a href="/admin/portfolio" style="padding:7px 14px;border-radius:999px;font-size:13px;text-decoration:none;color:var(--text-secondary);border:1px solid var(--border,#e5e7eb);background:#fff">Portfolio</a><a href="/admin/unit-report" style="padding:7px 14px;border-radius:999px;font-size:13px;text-decoration:none;color:#fff;background:#0f766e;border:1px solid #0f766e;font-weight:600">Unit report</a></div>
@endif
<div class="db-head">
    <div>
        <h1>{{ $heading ?? 'Hello ' . Auth::user()->name }}</h1>
        <p>{{ $listing->name ?? 'Your portfolio' }}{!! $isPool ? ' · <b>' . e($pool['group']->name ?? 'Pool') . '</b>, ' . $pool['units'] . ' units, pool figures' : '' !!} · {{ $selDate->format('F Y') }}</p>
        @php $pmsStamp = \App\Support\PmsReconcile::latestRun(); @endphp
        <p style="font-size:11.5px;color:var(--text-secondary,#64748b);margin-top:2px">Live figures, provisional until your monthly report.{{ $pmsStamp ? ' Last checked against the property management system ' . $pmsStamp->ran_at->format('j M Y, H:i') . '.' : '' }}</p>
    </div>
</div>

<form method="GET" action="{{ $formAction ?? '/owner/dashboard' }}" class="db-toolbar">
    @if(!empty($groupedListings))
    {{-- One box that is both the dropdown and the search: type to narrow, pick to open. --}}
    <div class="db-combo">
        <input type="text" id="db-combo-input" class="form-select" value="{{ $listing ? $listing->name . ($listing->owner_name ?? null ? ' · ' . $listing->owner_name : '') : '' }}" placeholder="Choose a unit…" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="db-combo-list" aria-label="Unit">
        <input type="hidden" name="listing_id" id="db-combo-id" value="{{ $id }}">
        <div class="db-combo-list" id="db-combo-list" role="listbox" hidden>
            @foreach($groupedListings as $property => $units)
            <div class="db-combo-group">{{ $property }}</div>
            @foreach($units as $l)
            <div class="db-combo-opt{{ $id == $l->id ? ' is-sel' : '' }}" role="option" data-id="{{ $l->id }}" data-text="{{ strtolower($l->name . ' ' . ($l->owner_name ?? '')) }}">{{ $l->name }}@if($l->owner_name)<span> · {{ $l->owner_name }}</span>@endif</div>
            @endforeach
            @endforeach
        </div>
    </div>
    @else
    <select name="listing_id" class="form-select" aria-label="Unit" id="db-unit-select">
        @foreach($allListings as $l)
            <option value="{{ $l->id }}" {{ ($id == $l->id) ? 'selected' : '' }}>{{ $l->name }}</option>
        @endforeach
    </select>
    @endif
    <input type="month" name="date" class="form-input" value="{{ $selDate->format('Y-m') }}" aria-label="Month">
    <button type="submit" class="btn btn-primary">Update</button>
</form>

<div class="kpis">
    <div class="kpi hero">
        <div class="ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        <div class="v">RM {{ number_format($monthRevenue, 2) }}</div>
        <div class="l">{{ $isPool ? 'Pool revenue' : 'Revenue' }}</div>
        <div class="s">{{ $isPool ? 'room + cleaning, before SST · ' . $pool['units'] . ' units' : $selDate->format('M Y') . ' · room charge, before SST' }}</div>
    </div>
    <div class="kpi teal">
        <div class="ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
        <div class="v">{{ number_format($occupancy, 1) }}%</div>
        <div class="l">{{ $isPool ? 'Pool occupancy' : 'Occupancy' }}</div>
        <div class="s">{{ $isPool ? 'across ' . $pool['units'] . ' units' : 'nights sold ÷ nights available' }}</div>
    </div>
    <div class="kpi dark">
        <div class="ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
        <div class="v">{{ $bookingCount }}</div>
        <div class="l">{{ $isPool ? 'Pool bookings' : 'Bookings' }}</div>
        <div class="s">{{ $isPool ? $pool['units'] . ' units · ' : '' }}touching {{ $selDate->format('M') }}</div>
    </div>
    <div class="kpi plain">
        <div class="ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
        <div class="v">RM {{ number_format($avgDailyRate, 2) }}</div>
        <div class="l">Average daily rate</div>
        <div class="s">room charge per night sold</div>
    </div>
    <div class="kpi plain">
        <div class="ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        <div class="v">{{ $avgLengthOfStay }} {{ $avgLengthOfStay == 1 ? 'night' : 'nights' }}</div>
        <div class="l">Average length of stay</div>
        <div class="s">per booking</div>
    </div>
    <div class="kpi plain">
        <div class="ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg></div>
        <div class="v">RM {{ number_format($accumulatedSales, 2) }}</div>
        <div class="l">{{ $isPool ? 'Accumulated pool revenue' : 'Accumulated revenue' }}</div>
        <div class="s">Jan to {{ $selDate->format('M Y') }}</div>
    </div>
</div>

<div class="grid-3">
    <div class="panel">
        <h3>Booking sources <small>{{ $selDate->format('M Y') }}</small></h3>
        <div class="donut">
            <div class="chart-box"><canvas id="sourceChart"></canvas></div>
            <div class="legend">
                @php $any = false; @endphp
                @foreach($sourceBreakdown as $src => $d)
                    @if($d['count'] > 0)
                        @php $any = true; @endphp
                        <div class="row"><span class="dot" style="background:{{ $channelColour[$src] ?? '#64748b' }}"></span><span class="name" title="{{ $src }}">{{ $src }}</span><span class="cnt">{{ $d['count'] }}</span><span class="pct">{{ number_format($d['pct'], 0) }}%</span></div>
                    @endif
                @endforeach
                @unless($any)<div class="empty">No bookings this month</div>@endunless
            </div>
        </div>
    </div>
    <div class="panel">
        <h3>Booking categories <small>{{ $selDate->format('M Y') }}</small></h3>
        <div class="donut">
            <div class="chart-box"><canvas id="catChart"></canvas></div>
            <div class="legend">
                @php $any = false; @endphp
                @foreach($categoryBreakdown as $cat => $d)
                    @if($d['count'] > 0)
                        @php $any = true; @endphp
                        <div class="row"><span class="dot" style="background:{{ $catColours[$cat] ?? '#64748b' }}"></span><span class="name" title="{{ $cat }}">{{ $cat }}</span><span class="cnt">{{ $d['count'] }}</span><span class="pct">{{ number_format($d['pct'], 0) }}%</span></div>
                    @endif
                @endforeach
                @unless($any)<div class="empty">No bookings this month</div>@endunless
            </div>
        </div>
    </div>
    <div class="panel">
        <h3>Channel and category mix <small>share of bookings</small></h3>
        <div class="bars">
            <div>
                @foreach($sourceBreakdown as $src => $d)
                    <div class="bar"><span class="name" title="{{ $src }}">{{ $src }}</span><span class="track"><span class="fill" style="width:{{ min(100, $d['pct']) }}%;background:{{ $channelColour[$src] ?? '#64748b' }}"></span></span><span class="pct">{{ number_format($d['pct'], 0) }}%</span></div>
                @endforeach
            </div>
            <div>
                @foreach($categoryBreakdown as $cat => $d)
                    <div class="bar"><span class="name" title="{{ $cat }}">{{ $cat }}</span><span class="track"><span class="fill" style="width:{{ min(100, $d['pct']) }}%;background:{{ $catColours[$cat] ?? '#64748b' }}"></span></span><span class="pct">{{ number_format($d['pct'], 0) }}%</span></div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="grid-2">
    <div class="panel trend">
        <h3>Average daily rate <small>last 6 months</small></h3>
        <div class="chart-box"><canvas id="rateChart"></canvas></div>
    </div>
    <div class="panel trend">
        <h3>Occupancy <small>last 6 months</small></h3>
        <div class="chart-box"><canvas id="occChart"></canvas></div>
    </div>
</div>
@if(!empty($extras))
    @include('admin.unit_report_extras')
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    var inp = document.getElementById('db-combo-input'), hid = document.getElementById('db-combo-id'), list = document.getElementById('db-combo-list');
    if (!inp || !list) return;
    var opts = Array.prototype.slice.call(list.querySelectorAll('.db-combo-opt')), groups = Array.prototype.slice.call(list.querySelectorAll('.db-combo-group'));
    var startValue = inp.value, act = -1, empty = document.createElement('div'); empty.className = 'db-combo-empty'; empty.textContent = 'No unit matches'; empty.hidden = true; list.appendChild(empty);
    function open(){ list.hidden = false; inp.setAttribute('aria-expanded', 'true'); var sel = list.querySelector('.is-sel'); if (sel) sel.scrollIntoView({block: 'center'}); }
    function close(){ list.hidden = true; inp.setAttribute('aria-expanded', 'false'); act = -1; opts.forEach(function(o){ o.classList.remove('is-act'); }); }
    function visible(){ return opts.filter(function(o){ return !o.hidden; }); }
    function filter(){
        var t = inp.value.trim().toLowerCase(), n = 0;
        opts.forEach(function(o){ var hit = !t || o.dataset.text.indexOf(t) !== -1; o.hidden = !hit; if (hit) n++; });
        groups.forEach(function(g){ var e = g.nextElementSibling, any = false; while (e && e.classList.contains('db-combo-opt')) { if (!e.hidden) any = true; e = e.nextElementSibling; } g.hidden = !any; });
        empty.hidden = n > 0; act = -1; open();
    }
    function choose(o){ hid.value = o.dataset.id; inp.value = o.textContent; close(); inp.form.submit(); }
    inp.addEventListener('focus', function(){ inp.select(); filter(); });
    inp.addEventListener('input', filter);
    inp.addEventListener('keydown', function(e){
        var v = visible();
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); if (list.hidden) open(); act = e.key === 'ArrowDown' ? Math.min(act + 1, v.length - 1) : Math.max(act - 1, 0); opts.forEach(function(o){ o.classList.remove('is-act'); }); if (v[act]) { v[act].classList.add('is-act'); v[act].scrollIntoView({block: 'nearest'}); } }
        else if (e.key === 'Enter') { e.preventDefault(); var pick = v[act] || v[0]; if (pick) choose(pick); }
        else if (e.key === 'Escape') { inp.value = startValue; close(); }
    });
    list.addEventListener('mousedown', function(e){ var o = e.target.closest('.db-combo-opt'); if (o) { e.preventDefault(); choose(o); } });
    document.addEventListener('click', function(e){ if (!e.target.closest('.db-combo')) { if (!list.hidden) inp.value = startValue; close(); } });
})();
</script>
<script>
(function () {
    var labels  = {!! json_encode(array_column($graphArray, 0)) !!};
    var occ     = {!! json_encode(array_column($graphArray, 1)) !!};
    var rate    = {!! json_encode(array_column($graphavg, 1)) !!};
    var src     = {!! json_encode(array_values(array_filter($sourceBreakdown, fn ($d) => $d['count'] > 0))) !!};
    var srcL    = {!! json_encode(array_keys(array_filter($sourceBreakdown, fn ($d) => $d['count'] > 0))) !!};
    var srcC    = srcL.map(function (k) { return ({!! json_encode($channelColour) !!})[k] || '#64748b'; });
    var cat     = {!! json_encode(array_values(array_filter($categoryBreakdown, fn ($d) => $d['count'] > 0))) !!};
    var catL    = {!! json_encode(array_keys(array_filter($categoryBreakdown, fn ($d) => $d['count'] > 0))) !!};
    var catC    = catL.map(function (k) { return ({!! json_encode($catColours) !!})[k] || '#64748b'; });
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#6e6e73';

    function donut(id, labels, rows, colours) {
        var el = document.getElementById(id); if (!el) return;
        var data = rows.map(function (r) { return r.count; });
        if (!data.length) { data = [1]; labels = ['None']; colours = ['#eceef1']; }
        new Chart(el, { type: 'doughnut', data: { labels: labels, datasets: [{ data: data, backgroundColor: colours, borderWidth: 2, borderColor: '#fff', hoverOffset: 4 }] },
            options: { maintainAspectRatio: false, animation: false, resizeDelay: 300, cutout: '68%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return ' ' + c.label + ': ' + c.parsed; } } } } } });
    }
    donut('sourceChart', srcL, src, srcC);
    donut('catChart', catL, cat, catC);

    function line(id, data, colour, yOpts, fmt) {
        var el = document.getElementById(id); if (!el) return;
        var ctx = el.getContext('2d'), g = ctx.createLinearGradient(0, 0, 0, 240); g.addColorStop(0, colour + '33'); g.addColorStop(1, colour + '00');
        new Chart(el, { type: 'line', data: { labels: labels, datasets: [{ data: data, borderColor: colour, backgroundColor: g, fill: true, tension: .35, pointRadius: 3.5, pointHoverRadius: 6, pointBackgroundColor: '#fff', pointBorderColor: colour, pointBorderWidth: 2, borderWidth: 2.5 }] },
            options: { maintainAspectRatio: false, animation: false, resizeDelay: 300, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return ' ' + fmt(c.parsed.y); } } } },
                scales: { y: Object.assign({ grid: { color: '#f0f0f2' }, border: { display: false }, ticks: { callback: fmt } }, yOpts), x: { grid: { display: false }, border: { display: false } } } } });
    }
    line('rateChart', rate, '#0a8a72', { beginAtZero: false }, function (v) { return 'RM ' + Number(v).toFixed(0); });
    line('occChart', occ, '#F36523', { beginAtZero: true, max: 100 }, function (v) { return Number(v).toFixed(0) + '%'; });
})();
</script>
@endpush
