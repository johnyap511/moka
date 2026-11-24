@extends('admin.layout')
@section('title', 'Units to push')
@section('page-title', 'Units to push')

@push('styles')
<style>
.up-head{display:flex;justify-content:space-between;align-items:baseline;gap:12px;flex-wrap:wrap;margin-bottom:12px}.up-head h1{font-size:20px;margin:0}.up-head span{font-size:12.5px;color:var(--text-secondary)}
.up-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:14px}@media (max-width:800px){.up-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
.up-kpi{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:12px 14px;display:grid;gap:3px;min-width:0;border-top:3px solid transparent}.up-kpi .l{font-size:11.5px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.03em}.up-kpi .v{font-size:24px;font-weight:700;letter-spacing:-.3px}.up-kpi .s{font-size:11.5px;color:var(--text-secondary)}
.up-kpi.push{border-top-color:#dc2626}.up-kpi.watch{border-top-color:#d97706}
.up-card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:14px 16px;min-width:0;margin-bottom:14px}
.up-filters{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px}
.up-tabs{display:flex;gap:4px;flex-wrap:wrap}.up-tabs button{padding:7px 13px;border-radius:999px;font-size:13px;color:var(--text-secondary);border:1px solid var(--border,#e5e7eb);background:#fff;cursor:pointer;font-family:inherit}.up-tabs button.on{background:#0f766e;border-color:#0f766e;color:#fff;font-weight:600}.up-tabs button b{font-weight:600;opacity:.75;margin-left:3px}
.up-filters select{width:auto;min-width:190px;max-width:100%}
.up-wrap{overflow:auto}
.up-table{width:100%;border-collapse:collapse;font-size:13px}.up-table th,.up-table td{padding:8px 8px;border-bottom:1px solid var(--border,#e5e7eb);text-align:left;white-space:nowrap;vertical-align:middle}.up-table th{font-size:11px;text-transform:uppercase;letter-spacing:.03em;color:var(--text-secondary);cursor:pointer;user-select:none;white-space:normal;line-height:1.3;vertical-align:bottom}.up-table th.num{max-width:92px}.up-table th.sorted{color:#0f766e}.up-table .num{text-align:right;font-variant-numeric:tabular-nums}.up-table tbody tr:hover{background:#fafcfb}.up-table td.why{white-space:normal;min-width:170px;color:var(--text-secondary);font-size:12.5px}.up-table a{color:inherit;text-decoration:none;font-weight:600}.up-table a:hover{color:#0f766e}.up-table td.unit small{display:block;font-size:11.5px;color:var(--text-secondary);font-weight:400}
.up-st{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap}.up-st.push{background:#fee2e2;color:#991b1b}.up-st.watch{background:#fef3c7;color:#92400e}.up-st.ok{background:#dcfce7;color:#166534}.up-st.full{background:#e0f2fe;color:#075985}.up-st.idle{background:#f1f5f9;color:#475569}
.up-chg{font-weight:600}.up-chg.down{color:#b91c1c}.up-chg.up{color:#166534}.up-mute{color:var(--text-secondary)}
.up-bar{display:inline-block;width:44px;height:6px;background:#f1f5f9;border-radius:999px;overflow:hidden;vertical-align:middle;margin-left:8px}.up-bar i{display:block;height:100%;background:#0f766e}
.up-empty{font-weight:700}.up-empty.all{color:#b91c1c}
.up-note{font-size:12.5px;color:var(--text-secondary);line-height:1.5}.up-note summary{cursor:pointer;font-weight:600;color:var(--text)}.up-note ul{margin:8px 0 0;padding-left:18px}.up-note li{margin-bottom:4px}
.up-banner{background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:10px;padding:10px 14px;font-size:13px;margin-bottom:14px}
.up-none{padding:22px 8px;text-align:center;color:var(--text-secondary);font-size:13px}
.up-bld{width:100%;border-collapse:collapse;font-size:13px}.up-bld th,.up-bld td{padding:6px 8px;border-bottom:1px solid var(--border,#e5e7eb);text-align:left;white-space:nowrap}.up-bld th{font-size:11px;text-transform:uppercase;letter-spacing:.03em;color:var(--text-secondary)}.up-bld .num{text-align:right;font-variant-numeric:tabular-nums}.up-bld tr:last-child td{border-bottom:0}.up-bld a{color:inherit;font-weight:600;text-decoration:none;cursor:pointer}.up-bld a:hover{color:#0f766e}
.up-sm{display:none}
@media (max-width:900px){.up-hide-sm{display:none}}
@media (max-width:600px){.up-hide-xs{display:none}.up-sm{display:block;margin-top:4px;white-space:normal;font-size:12px;color:var(--text-secondary);font-weight:400}.up-sm .up-st{margin-right:4px}.up-table th,.up-table td{padding:8px 5px}.up-table td.unit{white-space:normal;min-width:128px}.up-bar{display:none}.up-kpi .v{font-size:20px}.up-filters select{width:100%}}
</style>
@endpush

@section('content')
@php
    use App\Support\UnitsToPush as U;
    $rm = fn ($v) => 'RM ' . number_format((float) $v, 0);
    $c = $d['counts']; $labels = U::STATUSES;
    $attention = $c['push'] + $c['watch'];
@endphp

<div class="up-head">
    <h1>Units to push</h1>
    <span>{{ $d['today']->format('j F Y') }} · {{ $d['trading'] }} units trading · room charges before SST</span>
</div>

@if($d['early'])
<div class="up-banner">It is early in the month. Units are compared with their usual and with their building from the {{ U::EARLY_DAYS + 1 }}th onwards; until then this page shows empty nights only.</div>
@endif

<div class="up-kpis">
    <div class="up-kpi push"><div class="l">Push now</div><div class="v">{{ $c['push'] }}</div><div class="s">units well behind</div></div>
    <div class="up-kpi watch"><div class="l">Watch</div><div class="v">{{ $c['watch'] }}</div><div class="s">units a little behind</div></div>
    <div class="up-kpi"><div class="l">Empty nights, next 7 days</div><div class="v">{{ number_format($d['empty7']) }}</div><div class="s">up to {{ $d['today']->copy()->addDays(6)->format('j M') }}, all units</div></div>
    <div class="up-kpi"><div class="l">Booked, {{ $d['today']->format('F') }}</div><div class="v">{{ number_format($d['occupancy'], 0) }}%</div><div class="s">{{ $rm($d['revenue']) }} on the books</div></div>
</div>

<div class="up-card">
    <div class="up-filters">
        <div class="up-tabs" id="up-tabs">
            <button type="button" data-f="attention" class="on">Needs attention <b>{{ $attention }}</b></button>
            <button type="button" data-f="ok">On track <b>{{ $c['ok'] }}</b></button>
            <button type="button" data-f="full">Full <b>{{ $c['full'] }}</b></button>
            <button type="button" data-f="idle">No bookings <b>{{ $c['idle'] }}</b></button>
            <button type="button" data-f="all">All <b>{{ count($d['units']) }}</b></button>
        </div>
        <select id="up-building" class="form-select" aria-label="Building">
            <option value="">All buildings</option>
            @foreach($d['byProperty'] as $p)
            <option value="{{ $p['property'] }}">{{ $p['property'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="up-wrap">
        <table class="up-table" id="up-table">
            <thead><tr>
                <th data-k="name">Unit</th>
                <th data-k="rank" class="up-hide-xs">Status</th>
                <th class="up-hide-xs">Why</th>
                <th class="num" data-k="empty7" title="Nights with no booking from today for 7 nights">Empty, next 7</th>
                <th class="num up-hide-sm" data-k="empty14" title="Nights with no booking from today for 14 nights">Empty, next 14</th>
                <th class="num" data-k="occupancy" title="Nights booked this month, out of {{ $d['days'] }}">Booked this month</th>
                <th class="num up-hide-sm" data-k="building" title="Average for the building's other short-stay units">Building</th>
                <th class="num up-hide-xs" data-k="revenue" title="Room charges on the books for this month">Revenue</th>
                <th class="num" data-k="vs" title="Room charges for the 1st to yesterday, against the same days in {{ implode(', ', $d['usualMonths']) }}">Against its usual</th>
                <th class="num up-hide-sm" data-k="adr" title="Average room charge per night booked">Rate a night</th>
            </tr></thead>
            <tbody>
            @foreach($d['units'] as $i => $u)
            <tr data-status="{{ $u['status'] }}" data-property="{{ $u['property'] }}" data-name="{{ $u['name'] }}" data-rank="{{ $i }}" data-empty7="{{ $u['empty7'] }}" data-empty14="{{ $u['empty14'] }}" data-occupancy="{{ $u['occupancy'] }}" data-building="{{ $u['building'] ?? -1 }}" data-revenue="{{ $u['revenue'] }}" data-vs="{{ $u['vs'] ?? 9999 }}" data-adr="{{ $u['adr'] }}">
                <td class="unit">@if($canUnitReport)<a href="/admin/unit-report?listing_id={{ $u['id'] }}">{{ $u['name'] }}</a>@else<strong>{{ $u['name'] }}</strong>@endif<span class="up-sm"><span class="up-st {{ $u['status'] }}">{{ $labels[$u['status']] }}</span>{{ $u['why'] }}</span></td>
                <td class="up-hide-xs"><span class="up-st {{ $u['status'] }}">{{ $labels[$u['status']] }}</span></td>
                <td class="why up-hide-xs">{{ $u['why'] ?: '' }}</td>
                <td class="num"><span class="up-empty {{ $u['empty7'] === 7 ? 'all' : '' }}">{{ $u['empty7'] }}</span> <span class="up-mute">of 7</span></td>
                <td class="num up-hide-sm">{{ $u['empty14'] }} <span class="up-mute">of {{ U::AHEAD_DAYS }}</span></td>
                <td class="num">{{ $u['occupancy'] }}%<span class="up-bar"><i style="width:{{ min(100, $u['occupancy']) }}%"></i></span></td>
                <td class="num up-hide-sm">@if($u['building'] !== null && !in_array($u['status'], ['full', 'idle'])){{ $u['building'] }}%@else<span class="up-mute">–</span>@endif</td>
                <td class="num up-hide-xs">{{ $rm($u['revenue']) }}</td>
                <td class="num">
                    @if($u['vs'] !== null)<span class="up-chg {{ $u['vs'] < 0 ? 'down' : ($u['vs'] > 0 ? 'up' : '') }}">{{ $u['vs'] > 0 ? '+' : '' }}{{ $u['vs'] }}%</span>
                    @elseif($u['isNew'])<span class="up-mute">New unit</span>
                    @else<span class="up-mute">–</span>@endif
                </td>
                <td class="num up-hide-sm">@if($u['adr'] > 0){{ $rm($u['adr']) }}@else<span class="up-mute">–</span>@endif</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        <div class="up-none" id="up-none" hidden>No units here.</div>
    </div>
</div>

<div class="up-card">
    <div class="up-wrap">
        <table class="up-bld">
            <thead><tr><th>Building</th><th class="num">Units trading</th><th class="num">Push now</th><th class="num">Watch</th><th class="num">Empty, next 7 days</th><th class="num">Booked this month</th></tr></thead>
            <tbody>
            @foreach($d['byProperty'] as $p)
            <tr><td><a data-building="{{ $p['property'] }}">{{ $p['property'] }}</a></td><td class="num">{{ $p['units'] }}</td><td class="num">{{ $p['push'] ?: '–' }}</td><td class="num">{{ $p['watch'] ?: '–' }}</td><td class="num">{{ number_format($p['empty7']) }}</td><td class="num">{{ $p['occupancy'] }}%</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="up-card">
    <details class="up-note">
        <summary>How to read this page</summary>
        <ul>
            <li><strong>Against its usual:</strong> room charges from the 1st to yesterday, compared with the same days in {{ implode(', ', $d['usualMonths']) }}. Each unit is measured against its own record.</li>
            <li><strong>Building:</strong> the average booked % of the other short-stay units in the same building. Full and idle units are left out of it.</li>
            <li><strong>Push now:</strong> {{ abs(U::PUSH_BELOW) }}% or more below its usual, or {{ abs(U::PUSH_GAP) }} points or more below its building.</li>
            <li><strong>Watch:</strong> {{ abs(U::WATCH_BELOW) }}% or more below its usual, or {{ abs(U::WATCH_GAP) }} points or more below its building.</li>
            <li><strong>Full:</strong> every night is booked for the rest of the month and the next {{ U::AHEAD_DAYS }} days. Nothing to sell.</li>
            <li><strong>No bookings:</strong> nothing booked this month, ahead, or in the three months before. Check that the unit is open for sale.</li>
            <li><strong>Revenue</strong> is nights × nightly rate for this month, before SST, cleaning and channel fees. It is the same figure as the Portfolio page.</li>
            <li>Extra rooms are not shown. Bookings come from eZee and refresh through the day.</li>
        </ul>
    </details>
</div>
@endsection

@push('scripts')
<script>
(function(){
    var table = document.getElementById('up-table'); if (!table) return;
    var body = table.tBodies[0], tabs = document.getElementById('up-tabs'), sel = document.getElementById('up-building'), none = document.getElementById('up-none');
    var filter = 'attention';
    function apply(){
        var shown = 0;
        Array.prototype.forEach.call(body.rows, function(r){
            var s = r.dataset.status;
            var ok = (filter === 'all') || (filter === 'attention' ? (s === 'push' || s === 'watch') : s === filter);
            if (ok && sel.value && r.dataset.property !== sel.value) ok = false;
            r.hidden = !ok; if (ok) shown++;
        });
        none.hidden = shown > 0;
    }
    tabs.addEventListener('click', function(e){
        var b = e.target.closest('button'); if (!b) return;
        filter = b.dataset.f;
        Array.prototype.forEach.call(tabs.children, function(x){ x.classList.toggle('on', x === b); });
        apply();
    });
    sel.addEventListener('change', apply);
    document.querySelectorAll('.up-bld a[data-building]').forEach(function(a){
        a.addEventListener('click', function(){ sel.value = a.dataset.building; apply(); table.scrollIntoView({block: 'start'}); });
    });
    // click a heading to sort; click again to reverse
    var dir = {};
    table.querySelectorAll('th[data-k]').forEach(function(th){
        th.addEventListener('click', function(){
            var k = th.dataset.k, text = k === 'name';
            dir[k] = dir[k] === 'asc' ? 'desc' : 'asc';
            if (!dir[k + '_set'] && !text && k !== 'rank' && k !== 'vs' && k !== 'occupancy') { dir[k] = 'desc'; } dir[k + '_set'] = 1;
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
