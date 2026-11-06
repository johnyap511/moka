@extends('admin.layout')
@section('title', 'PMS month sheet')
@section('page-title', 'PMS month sheet')

@push('styles')
<style>
.ms-head{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;margin-bottom:12px}.ms-head h1{margin:0 0 2px;font-size:20px}.ms-sub{margin:0;font-size:12.5px;color:var(--text-secondary,#64748b);max-width:760px}
.ms-bar{display:flex;gap:6px;align-items:center;flex-wrap:wrap}.ms-bar input{padding:6px 8px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px}
.ms-tiles{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:12px}@media (max-width:900px){.ms-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
.ms-tile{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:10px 12px;display:grid;gap:2px;text-decoration:none;color:inherit}.ms-tile span{font-size:11.5px;color:var(--text-secondary,#64748b)}.ms-tile b{font-size:20px;line-height:1.1}.ms-tile small{font-size:11px;color:var(--text-secondary,#64748b)}
.ms-tile.on{outline:2px solid #0f766e}.ms-ok b{color:#047857}.ms-review b{color:#b45309}.ms-notok b{color:#b91c1c}.ms-ezee b{color:#5b21b6}
.ms-gate{border-radius:10px;padding:10px 12px;font-size:13px;margin-bottom:12px}.ms-gate--ok{background:#dcfce7;color:#166534}.ms-gate--no{background:#fef3c7;color:#92400e}
.ms-list{width:100%;min-width:0!important;border-collapse:collapse;font-size:12.5px;background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:10px;overflow:hidden}.ms-list th{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary,#64748b);text-align:left;padding:8px 10px;border-bottom:1px solid var(--border,#e5e7eb);background:#f8fafc;white-space:nowrap}.ms-list td{padding:7px 10px;border-bottom:1px solid #f1f5f9;vertical-align:top}.ms-list tr:last-child td{border-bottom:0}.ms-list td.num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
.ms-st{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}.ms-st.OK{background:#dcfce7;color:#166534}.ms-st.REVIEW{background:#fef3c7;color:#92400e}.ms-st.NOTOK{background:#fee2e2;color:#991b1b}.ms-st.EZEEONLY{background:#ede9fe;color:#5b21b6}
.ms-muted{font-size:11.5px;color:var(--text-secondary,#64748b)}.ms-why{max-width:520px}.ms-why b{display:block;font-size:11px;color:#334155;margin-top:3px}
.ms-empty{padding:26px;text-align:center;color:var(--text-secondary,#64748b);font-size:13px}
.ms-foot{margin-top:10px;font-size:11.5px;color:var(--text-secondary,#64748b)}
</style>
@endpush

@section('content')
@php $label = \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y'); $fmt = fn ($v) => $v === null ? '—' : number_format((float) $v, 2); @endphp
<div class="ms-head">
    <div>
        <h1>Month sheet · {{ $label }}</h1>
        <p class="ms-sub">Every booking checking in this month next to its eZee figure, with a verdict and what to do. OK needs nothing. REVIEW and NOT OK are ticked against the eZee folio by a person; the fix goes into Homemoka or eZee, then run the month check again. Rules run in order, first match wins.</p>
    </div>
    <form method="get" action="{{ route('admin.pms.sheet') }}" class="ms-bar">
        <input type="month" name="month" value="{{ $month }}">
        <input type="hidden" name="show" value="{{ $show }}">
        <input type="search" name="q" value="{{ $q }}" placeholder="Unit, booking #, RES, folio" style="min-width:190px">
        <button class="btn btn-sm" type="submit">Show</button>
        <a class="btn btn-sm" href="{{ route('admin.pms.sheet.export', ['month' => $month]) }}">Download Excel</a>
        <a class="btn btn-sm" href="{{ route('admin.pms.index', ['month' => $month]) }}">Differences list</a>
    </form>
</div>

<div class="ms-tiles">
    @foreach([['all', 'All rows', $summary['total'], '', 'bookings + eZee-only'], ['ok', 'OK', $summary['OK'], 'ms-ok', 'nothing to do'], ['review', 'REVIEW', $summary['REVIEW'], 'ms-review', 'tick against eZee'], ['notok', 'NOT OK', $summary['NOT OK'], 'ms-notok', 'fix here or in eZee'], ['ezee', 'eZee only', $summary['EZEE ONLY'], 'ms-ezee', 'no Homemoka booking']] as [$key, $name, $n, $cls, $hint])
        <a class="ms-tile {{ $cls }} {{ $show === $key ? 'on' : '' }}" href="{{ route('admin.pms.sheet', array_filter(['month' => $month, 'show' => $key, 'q' => $q])) }}"><span>{{ $name }}</span><b>{{ $n }}</b><small>{{ $hint }}</small></a>
    @endforeach
</div>

<div class="ms-gate {{ $gate['ok'] ? 'ms-gate--ok' : 'ms-gate--no' }}">
    <b>{{ $label }} owner reports:</b> {{ $gate['ok'] ? 'clear to send. Month check ran ' . $gate['run']->ran_at->format('j M H:i') . ' with no open difference.' : $gate['why'] }}
    @if(!$monthRun) <span class="ms-muted">The eZee-only rows appear once the month check has run.</span>@endif
</div>

@if($show === 'attention' && $rows->isEmpty())
    <div class="ms-empty">Nothing needs attention{{ $q ? ' for "' . $q . '"' : '' }}: every booking of {{ $label }} agrees with eZee or is explained.</div>
@else
<table class="ms-list">
    <thead><tr><th>Status</th><th>Booking</th><th>Unit</th><th>Stay</th><th>Source</th><th style="text-align:right">Homemoka</th><th style="text-align:right">eZee</th><th style="text-align:right">Diff</th><th>Why, and what to do</th></tr></thead>
    <tbody>
    @forelse($rows as $r)
        <tr>
            <td><span class="ms-st {{ str_replace(' ', '', $r['status']) }}">{{ $r['status'] }}</span><div class="ms-muted">{{ $r['code'] }}</div></td>
            <td>
                @if($r['booking_id'])<a href="/admin/book/{{ $r['booking_id'] }}/edit">#{{ $r['booking_id'] }}</a>@endif
                @if($r['res'])<div><a href="/admin/ezee/assignment-log?q={{ urlencode($r['res']) }}">{{ $r['res'] }}</a></div>@endif
                @if($r['folio'])<div class="ms-muted">{{ $r['folio'] }}</div>@endif
            </td>
            <td>{{ $r['unit'] }}@if($r['extra_room'])<div class="ms-muted">extra room</div>@endif</td>
            <td style="white-space:nowrap">{{ \Carbon\Carbon::parse($r['check_in'])->format('j M') }} → {{ \Carbon\Carbon::parse($r['check_out'])->format('j M') }}<div class="ms-muted">{{ $r['nights'] }} night{{ $r['nights'] == 1 ? '' : 's' }}{{ $r['segment'] ? ' · segment' : '' }}</div></td>
            <td>{{ $r['source'] ?: '—' }}@if($r['ezee_source'] && strcasecmp((string) $r['ezee_source'], (string) $r['source']) !== 0)<div class="ms-muted">eZee: {{ $r['ezee_source'] }}</div>@endif</td>
            <td class="num">{{ $fmt($r['ours']) }}</td>
            <td class="num">{{ $fmt($r['theirs']) }}</td>
            <td class="num">{{ $r['diff'] === null ? '—' : number_format($r['diff'], 2) }}</td>
            <td class="ms-why">{{ $r['reason'] }}@if($r['action'])<b>→ {{ $r['action'] }}</b>@endif</td>
        </tr>
    @empty
        <tr><td colspan="9" class="ms-empty">No rows{{ $q ? ' for "' . $q . '"' : '' }}.</td></tr>
    @endforelse
    </tbody>
</table>
@endif

<div class="ms-foot">Thresholds: exact within RM {{ number_format($thresholds['sst_rounding'], 2) }}, rounding within RM {{ number_format($thresholds['rounding'], 2) }}, whole-stay materiality RM {{ number_format($thresholds['materiality'], 0) }}, deposit RM {{ number_format($thresholds['deposit'], 0) }}, long term from {{ $thresholds['ltr_min_nights'] }} nights. Set in config, not in code. Totals shown: Homemoka RM {{ number_format($summary['ours'], 2) }} · eZee RM {{ number_format($summary['theirs'], 2) }} (eZee-only rows included in the eZee total).</div>
@endsection
