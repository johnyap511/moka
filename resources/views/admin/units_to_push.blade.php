@extends('admin.layout')
@section('title', 'Units to push')
@section('page-title', 'Units to push')

@push('styles')
<style>
.up{--teal:#0f766e;--red:#b91c1c;--red-bg:#fee2e2;--amber:#92400e;--amber-bg:#fef3c7;--green:#166534;--green-bg:#dcfce7;--line:var(--border,#e5e7eb);--mute:var(--text-secondary,#6e6e73)}
.up-head{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:14px}.up-head h1{font-size:22px;margin:0 0 2px;letter-spacing:-.2px}.up-head p{font-size:13px;color:var(--mute);margin:0}
.up-search{position:relative;display:block}.up-search input{width:230px;max-width:100%;padding:8px 12px 8px 34px;border:1px solid var(--line);border-radius:10px;font:inherit;font-size:13px;background:#fff}.up-search input:focus{outline:2px solid rgba(15,118,110,.25);border-color:var(--teal)}.up-search svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--mute)}
.up-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:12px}
.up-kpi{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:13px 15px 13px 17px;min-width:0;display:grid;gap:2px;position:relative;overflow:hidden}.up-kpi::before{content:"";position:absolute;inset:0 auto 0 0;width:4px;background:transparent}.up-kpi.push::before{background:#dc2626}.up-kpi.watch::before{background:#d97706}
.up-kpi .l{font-size:11.5px;color:var(--mute);font-weight:600;text-transform:uppercase;letter-spacing:.03em}.up-kpi .v{font-size:26px;font-weight:700;letter-spacing:-.4px;line-height:1.15}.up-kpi .v small{font-size:13px;font-weight:500;color:var(--mute);letter-spacing:0;margin-left:4px}.up-kpi .s{font-size:12px;color:var(--mute)}
.up-blds{display:flex;gap:8px;overflow-x:auto;padding:2px 2px 10px;margin-bottom:4px;scrollbar-width:thin}
.up-bld{flex:0 0 auto;text-align:left;background:#fff;border:1px solid var(--line);border-radius:12px;padding:9px 13px;cursor:pointer;font:inherit;display:grid;gap:2px;min-width:118px}.up-bld b{font-size:13px;font-weight:600;color:var(--text,#1d1d1f)}.up-bld span{font-size:11.5px;color:var(--mute)}.up-bld span em{font-style:normal;font-weight:600;color:var(--red)}.up-bld span i{font-style:normal;font-weight:600;color:var(--amber)}
.up-bld:hover{border-color:#94a3b8}.up-bld.on{border-color:var(--teal);box-shadow:inset 0 0 0 1px var(--teal);background:#f0fdfa}
.up-card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);min-width:0;margin-bottom:14px}
.up-bar{display:flex;justify-content:space-between;align-items:center;gap:10px 16px;flex-wrap:wrap;padding:12px 16px;border-bottom:1px solid var(--line)}
.up-tabs{display:flex;gap:4px;flex-wrap:wrap}.up-tabs button{padding:7px 13px;border-radius:999px;font:inherit;font-size:13px;color:var(--mute);border:1px solid var(--line);background:#fff;cursor:pointer}.up-tabs button:hover{border-color:#94a3b8}.up-tabs button.on{background:var(--teal);border-color:var(--teal);color:#fff;font-weight:600}.up-tabs button b{font-weight:600;opacity:.75;margin-left:3px}
.up-legend{display:flex;gap:12px;align-items:center;font-size:11.5px;color:var(--mute);flex-wrap:wrap}.up-legend span{display:inline-flex;align-items:center;gap:5px}
.up-wrap{overflow-x:auto}
.up-table{width:100%;border-collapse:collapse;font-size:13px}.up-table th,.up-table td{padding:12px 9px;border-bottom:1px solid var(--line);text-align:left;vertical-align:top}.up-table th:first-child,.up-table td:first-child{padding-left:16px}.up-table th:last-child,.up-table td:last-child{padding-right:16px}
.up-table th{font-size:11px;text-transform:uppercase;letter-spacing:.03em;color:var(--mute);font-weight:600;cursor:pointer;user-select:none;white-space:nowrap;padding-top:10px;padding-bottom:10px;background:#fafafa;vertical-align:middle}.up-table th.sorted{color:var(--teal)}.up-table th.nosort{cursor:default}
.up-table tbody tr:hover{background:#fafcfb}.up-table tbody tr:last-child td{border-bottom:0}
.up-unit{white-space:nowrap}.up-unit a,.up-unit strong{color:inherit;text-decoration:none;font-weight:600;font-size:13.5px}.up-unit a:hover{color:var(--teal)}.up-unit small{display:block;font-size:11.5px;color:var(--mute);margin:1px 0 6px}
.up-st{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap}.up-st::before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}.up-st.push{background:var(--red-bg);color:#991b1b}.up-st.watch{background:var(--amber-bg);color:var(--amber)}.up-st.ok{background:var(--green-bg);color:var(--green)}.up-st.full{background:#e0f2fe;color:#075985}.up-st.idle{background:#f1f5f9;color:#475569}
.up-nights{display:flex;gap:2px;align-items:flex-end;height:20px}.up-n{width:10px;height:15px;border-radius:3px;background:var(--teal);flex:0 0 auto}.up-n.e{background:#fff;box-shadow:inset 0 0 0 1.5px #fca5a5}.up-n.we{height:20px}.up-n.gap{margin-left:4px}
.up-sub{font-size:11.5px;color:var(--mute);margin-top:4px;white-space:nowrap}.up-sub b{color:var(--red);font-weight:700}
.up-num{font-weight:600;font-size:13.5px;font-variant-numeric:tabular-nums;white-space:nowrap}
.up-meter{position:relative;width:84px;height:7px;border-radius:999px;background:#eef2f5;margin-top:6px}.up-meter i{position:absolute;inset:0 auto 0 0;border-radius:999px;background:var(--teal)}.up-meter u{position:absolute;top:-3px;bottom:-3px;width:2px;background:#1e293b;border-radius:1px}
.up-d{display:inline-block;font-size:11.5px;font-weight:700;padding:1px 6px;border-radius:999px;margin-left:5px;font-variant-numeric:tabular-nums;white-space:nowrap}.up-d.bad{background:var(--red-bg);color:#991b1b}.up-d.warn{background:var(--amber-bg);color:var(--amber)}.up-d.good{background:var(--green-bg);color:var(--green)}.up-d.flat{background:#f1f5f9;color:#475569}.up-d.solo{margin-left:0;font-size:12.5px;padding:2px 8px}
.up-do{min-width:200px;max-width:320px}.up-do p{margin:0;font-size:13px;line-height:1.4;color:var(--text,#1d1d1f)}.up-do small{display:block;margin-top:5px;font-size:11.5px;line-height:1.35;color:var(--mute)}
.up-mute{color:var(--mute)}
.up-none{padding:34px 16px;text-align:center;color:var(--mute);font-size:13.5px}
.up-note{font-size:13px;color:#44403c;line-height:1.55;padding:14px 16px}.up-note summary{cursor:pointer;font-weight:600;color:var(--text,#1d1d1f)}.up-note ul{margin:10px 0 0;padding-left:18px;display:grid;gap:5px}
.up-banner{background:#fffbeb;border:1px solid #fde68a;color:var(--amber);border-radius:10px;padding:10px 14px;font-size:13px;margin-bottom:12px}
@media (max-width:1100px){
  .up-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}
  .up-legend{display:none}
  .up-table thead{display:none}.up-table,.up-table tbody{display:block}
  .up-table tbody tr{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px 10px;padding:14px 16px;border-bottom:1px solid var(--line)}.up-table tbody tr[hidden]{display:none}.up-table tbody tr:last-child{border-bottom:0}
  .up-table td{display:block;padding:0!important;border:0}
  .up-table td.up-unit{grid-column:1 / 3;white-space:normal}.up-table td.c-nights{grid-column:3 / -1}.up-table td.up-do{grid-column:1 / -1;max-width:none;min-width:0;order:9}
  .up-table td[data-l]::before{content:attr(data-l);display:block;font-size:10.5px;text-transform:uppercase;letter-spacing:.03em;color:var(--mute);font-weight:600;margin-bottom:4px}
  .up-sub{white-space:normal}
}
@media (max-width:640px){
  .up-kpi .v{font-size:22px}.up-head{align-items:stretch}.up-search input{width:100%}
  .up-table tbody tr{grid-template-columns:repeat(2,minmax(0,1fr))}
  .up-table td.up-unit,.up-table td.c-nights{grid-column:1 / -1}
  .up-n{flex:1 1 0;max-width:20px;height:17px}.up-n.we{height:22px}.up-nights{height:22px}
  .up-d{margin-left:0;margin-top:3px}.up-num{display:block}
}
</style>
@endpush

@section('content')
@php
    use App\Support\UnitsToPush as U;
    $rm = fn ($v) => 'RM ' . number_format((float) $v, 0);
    $c = $d['counts']; $labels = U::STATUSES; $today = $d['today'];
    $attention = $c['push'] + $c['watch'];
    // one coloured figure per comparison: red and amber use the same lines as the status
    $delta = function ($v, $watch, $push, $unit = '%', $solo = false) {
        if ($v === null) return '';
        $tone = $v <= $push ? 'bad' : ($v <= $watch ? 'warn' : ($v > 0 ? 'good' : 'flat'));
        return '<span class="up-d ' . $tone . ($solo ? ' solo' : '') . '">' . ($v > 0 ? '+' : ($v < 0 ? '−' : '')) . abs($v) . $unit . '</span>';
    };
    // the rate is neither good nor bad on its own, so it is shown without red or green
    $rateTag = fn ($v) => $v === null ? '' : '<span class="up-d flat">' . ($v > 0 ? '+' : ($v < 0 ? '−' : '')) . abs($v) . '%</span>';
    $days = collect(range(0, U::AHEAD_DAYS - 1))->map(fn ($i) => $today->copy()->addDays($i));
@endphp

<div class="up">
<div class="up-head">
    <div>
        <h1>Units to push</h1>
        <p>{{ $today->format('l, j F Y') }} · {{ $d['trading'] }} units trading · room charges before SST</p>
    </div>
    <label class="up-search" aria-label="Find a unit">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        <input type="search" id="up-q" placeholder="Find a unit" autocomplete="off">
    </label>
</div>

@if($d['early'])
<div class="up-banner">It is early in the month. Units are compared with their building, similar units and their own usual from the {{ U::EARLY_DAYS + 1 }}th onwards; until then this page shows empty nights only.</div>
@endif

<div class="up-kpis">
    <div class="up-kpi push"><div class="l">Push now</div><div class="v">{{ $c['push'] }}<small>units</small></div><div class="s">well behind, act this week</div></div>
    <div class="up-kpi watch"><div class="l">Watch</div><div class="v">{{ $c['watch'] }}<small>units</small></div><div class="s">a little behind</div></div>
    <div class="up-kpi"><div class="l">Empty nights, next 7 days</div><div class="v">{{ number_format($d['empty7']) }}</div><div class="s">to {{ $today->copy()->addDays(6)->format('j M') }}, across all units</div></div>
    <div class="up-kpi"><div class="l">Booked in {{ $today->format('F') }}</div><div class="v">{{ number_format($d['occupancy'], 0) }}%</div><div class="s">{{ $rm($d['revenue']) }} on the books</div></div>
</div>

<div class="up-blds" id="up-blds">
    <button type="button" class="up-bld on" data-b=""><b>All buildings</b><span>{{ $d['trading'] }} units · {{ number_format($d['occupancy'], 0) }}% booked</span></button>
    @foreach($d['byProperty'] as $p)
    <button type="button" class="up-bld" data-b="{{ $p['property'] }}"><b>{{ $p['property'] }}</b><span>@if($p['push'])<em>{{ $p['push'] }} to push</em>@elseif($p['watch'])<i>{{ $p['watch'] }} to watch</i>@else on track @endif · {{ $p['occupancy'] }}% booked</span></button>
    @endforeach
</div>

<div class="up-card">
    <div class="up-bar">
        <div class="up-tabs" id="up-tabs">
            <button type="button" data-f="attention" class="on">Needs attention <b id="up-n-attention">{{ $attention }}</b></button>
            <button type="button" data-f="ok">On track <b id="up-n-ok">{{ $c['ok'] }}</b></button>
            <button type="button" data-f="full">Full <b id="up-n-full">{{ $c['full'] }}</b></button>
            <button type="button" data-f="idle">No bookings <b id="up-n-idle">{{ $c['idle'] }}</b></button>
            <button type="button" data-f="all">All <b id="up-n-all">{{ count($d['units']) }}</b></button>
        </div>
        <div class="up-legend">
            <span><span class="up-n"></span> booked</span>
            <span><span class="up-n e"></span> empty</span>
            <span><span class="up-n we"></span> taller: Friday or Saturday</span>
            <span><span class="up-meter" style="width:26px;margin:0"><u style="left:60%"></u></span> building average</span>
        </div>
    </div>

    <div class="up-wrap">
        {{-- min-width:0 tells the layout script not to force a wide table: this one turns into cards on small screens --}}
        <table class="up-table" id="up-table" style="min-width:0">
            <thead><tr>
                <th data-k="name">Unit</th>
                <th data-k="empty14" title="Each bar is one night, starting tonight">Next {{ U::AHEAD_DAYS }} nights</th>
                <th data-k="occupancy" title="Nights booked this month out of {{ $d['days'] }}. The dark line is the average of the building's other short-stay units.">Booked this month</th>
                <th data-k="adr" title="Average room charge per night booked this month, against similar units">Rate a night</th>
                <th data-k="revenue" title="Room charges on the books for this month, against similar units: the same room type in the same building">Revenue</th>
                <th data-k="vs" title="Room charges from the 1st to yesterday, against the same days in {{ implode(', ', $d['usualMonths']) }}">Against its usual</th>
                <th class="nosort">What to do</th>
            </tr></thead>
            <tbody>
            @foreach($d['units'] as $i => $u)
            @php $live = !in_array($u['status'], ['full', 'idle']); $who = $u['peerScope'] === 'type' ? 'similar units' : 'building'; @endphp
            <tr data-status="{{ $u['status'] }}" data-property="{{ $u['property'] }}" data-name="{{ $u['name'] }}" data-empty14="{{ $u['empty14'] }}" data-occupancy="{{ $u['occupancy'] }}" data-adr="{{ $u['adr'] }}" data-revenue="{{ $u['revenue'] }}" data-vs="{{ $u['vs'] ?? 9999 }}">
                <td class="up-unit">
                    @if($canUnitReport)<a href="/admin/unit-report?listing_id={{ $u['id'] }}" title="Open the unit report">{{ $u['name'] }}</a>@else<strong>{{ $u['name'] }}</strong>@endif
                    <small>{{ $u['room'] ?: $u['property'] }}</small>
                    <span class="up-st {{ $u['status'] }}">{{ $labels[$u['status']] }}</span>
                </td>
                <td class="c-nights" data-l="Next {{ U::AHEAD_DAYS }} nights">
                    <div class="up-nights" role="img" aria-label="{{ $u['empty14'] }} of the next {{ U::AHEAD_DAYS }} nights are empty">
                        @foreach($days as $k => $day)<span class="up-n {{ $u['ahead'][$k] === '1' ? '' : 'e' }} {{ in_array($day->dayOfWeek, [5, 6]) ? 'we' : '' }} {{ $k > 0 && $day->day === 1 ? 'gap' : '' }}" title="{{ $day->format('D j M') }}: {{ $u['ahead'][$k] === '1' ? 'booked' : 'empty' }}"></span>@endforeach
                    </div>
                    <div class="up-sub">@if($u['empty14'] === 0)all booked @else<b>{{ $u['empty14'] }}</b> empty, {{ $u['empty7'] }} in the next 7 @endif</div>
                </td>
                <td data-l="Booked this month">
                    <span class="up-num">{{ $u['occupancy'] }}%</span>@if($live){!! $delta($u['gap'], U::WATCH_GAP, U::PUSH_GAP, ' pts') !!}@endif
                    <div class="up-meter"><i style="width:{{ min(100, $u['occupancy']) }}%"></i>@if($live && $u['building'] !== null)<u style="left:calc({{ min(100, $u['building']) }}% - 1px)"></u>@endif</div>
                    <div class="up-sub">@if($live && $u['building'] !== null)building {{ $u['building'] }}% @else {{ $u['nights'] }} of {{ $d['days'] }} nights @endif</div>
                </td>
                <td data-l="Rate a night">
                    @if($u['adr'] > 0)<span class="up-num">{{ $rm($u['adr']) }}</span>@if($live){!! $rateTag($u['adrVs']) !!}@endif
                    <div class="up-sub">@if($live && $u['peerAdr']){{ $who }} {{ $rm($u['peerAdr']) }}@else&nbsp;@endif</div>
                    @else<span class="up-mute">–</span>@endif
                </td>
                <td data-l="Revenue">
                    <span class="up-num">{{ $rm($u['revenue']) }}</span>@if($live){!! $delta($u['peerVs'], U::WATCH_PEER, U::PUSH_PEER) !!}@endif
                    <div class="up-sub">@if($live && $u['peer']){{ $who }} {{ $rm($u['peer']) }}@else&nbsp;@endif</div>
                </td>
                <td data-l="Against its usual">
                    @if($u['vs'] !== null){!! $delta($u['vs'], U::WATCH_BELOW, U::PUSH_BELOW, '%', true) !!}<div class="up-sub">usual {{ $rm($u['usual']) }} by now</div>
                    @elseif($u['isNew'])<span class="up-mute">New unit</span><div class="up-sub">no record yet</div>
                    @else<span class="up-mute">–</span>@endif
                </td>
                <td class="up-do" @if($u['action'] || $u['why']) data-l="What to do" @endif>
                    @if($u['action'])<p>{{ $u['action'] }}</p>@endif
                    @if($u['why'])<small>{{ $u['status'] === 'idle' ? '' : 'Flagged: ' }}{{ $u['why'] }}</small>@endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        <div class="up-none" id="up-none" hidden>No units here. Try another tab or building.</div>
    </div>
</div>

<div class="up-card">
    <details class="up-note">
        <summary>How to read this page</summary>
        <ul>
            <li><strong>Three checks decide the status.</strong> A unit is flagged when it fails any one of them. Red and amber figures show which.</li>
            <li><strong>Booked this month:</strong> the unit's booked nights against the average of the other short-stay units in its building. {{ abs(U::WATCH_GAP) }} points below is Watch, {{ abs(U::PUSH_GAP) }} points below is Push now.</li>
            <li><strong>Revenue:</strong> this month's room charges against similar units, meaning the same room type in the same building. Where a room type has fewer than {{ U::MIN_PEERS }} units, the building average is used and the row says "building". {{ abs(U::WATCH_PEER) }}% below is Watch, {{ abs(U::PUSH_PEER) }}% below is Push now.</li>
            <li><strong>Against its usual:</strong> room charges from the 1st to yesterday against the same days in {{ implode(', ', $d['usualMonths']) }}. {{ abs(U::WATCH_BELOW) }}% below is Watch, {{ abs(U::PUSH_BELOW) }}% below is Push now.</li>
            <li><strong>Rate a night</strong> does not change the status. It tells you whether the problem is the price: a rate {{ U::RATE_BAND }}% or more above similar units with empty nights suggests lowering it; a rate {{ U::RATE_BAND }}% or more below with good bookings suggests raising it.</li>
            <li><strong>What to do</strong> is a suggestion from these simple rules. It cannot see photos, reviews, repairs or owner instructions, so use your own judgement.</li>
            <li><strong>Full:</strong> every night is booked for the rest of the month and the next {{ U::AHEAD_DAYS }} nights. <strong>No bookings:</strong> nothing booked this month, ahead, or in the three months before.</li>
            <li><strong>Revenue</strong> is nights × nightly rate, before SST, cleaning and channel fees, the same figure as the Portfolio page. Extra rooms are not shown. Bookings come from eZee and refresh through the day.</li>
        </ul>
    </details>
</div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    var table = document.getElementById('up-table'); if (!table) return;
    var body = table.tBodies[0], tabs = document.getElementById('up-tabs'), blds = document.getElementById('up-blds'), q = document.getElementById('up-q'), none = document.getElementById('up-none');
    var filter = 'attention', building = '';
    function matches(r, f){ var s = r.dataset.status; return f === 'all' || (f === 'attention' ? (s === 'push' || s === 'watch') : s === f); }
    function apply(){
        var shown = 0, text = q.value.trim().toLowerCase(), n = {attention: 0, ok: 0, full: 0, idle: 0, all: 0};
        Array.prototype.forEach.call(body.rows, function(r){
            var inScope = (!building || r.dataset.property === building) && (!text || r.dataset.name.toLowerCase().indexOf(text) !== -1);
            if (inScope) { for (var k in n) { if (matches(r, k)) n[k]++; } }
            // a search looks in every status, so a unit is found wherever it sits
            var ok = inScope && (text ? true : matches(r, filter));
            r.hidden = !ok; if (ok) shown++;
        });
        for (var k in n) { document.getElementById('up-n-' + k).textContent = n[k]; }
        none.hidden = shown > 0;
    }
    tabs.addEventListener('click', function(e){
        var b = e.target.closest('button'); if (!b) return;
        filter = b.dataset.f; q.value = '';
        Array.prototype.forEach.call(tabs.children, function(x){ x.classList.toggle('on', x === b); });
        apply();
    });
    blds.addEventListener('click', function(e){
        var b = e.target.closest('button'); if (!b) return;
        building = b.dataset.b;
        Array.prototype.forEach.call(blds.children, function(x){ x.classList.toggle('on', x === b); });
        apply();
    });
    q.addEventListener('input', apply);
    // click a heading to sort; click again to reverse
    var dir = {};
    table.querySelectorAll('th[data-k]').forEach(function(th){
        th.addEventListener('click', function(){
            var k = th.dataset.k, text = k === 'name';
            dir[k] = dir[k] ? (dir[k] === 'asc' ? 'desc' : 'asc') : (k === 'empty14' || k === 'revenue' || k === 'adr' ? 'desc' : 'asc');
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
