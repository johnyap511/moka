@extends('admin.layout')
@section('title', 'PMS check')
@section('page-title', 'PMS check')

@push('styles')
<style>
.pc-head{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;margin-bottom:12px}.pc-head h1{margin:0 0 2px;font-size:20px}.pc-sub{margin:0;font-size:12.5px;color:var(--text-secondary,#64748b);max-width:760px}
.pc-run{display:flex;gap:6px;align-items:center;flex-wrap:wrap}.pc-run input{padding:6px 8px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px}
.pc-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:12px}@media (max-width:900px){.pc-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
.pc-tile{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:10px 12px;display:grid;gap:2px}.pc-tile span{font-size:11.5px;color:var(--text-secondary,#64748b)}.pc-tile b{font-size:20px;line-height:1.1}.pc-tile small{font-size:11px;color:var(--text-secondary,#64748b)}.pc-tile--ok b{color:#047857}.pc-tile--warn b{color:#b45309}
.pc-gate{border-radius:10px;padding:10px 12px;font-size:13px;margin-bottom:12px}.pc-gate--ok{background:#dcfce7;color:#166534}.pc-gate--no{background:#fef3c7;color:#92400e}
.pc-kinds{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px}.pc-kinds a{font-size:12px;padding:3px 10px;border-radius:999px;border:1px solid var(--border,#e5e7eb);text-decoration:none;color:#334155;background:#fff}.pc-kinds a.on{background:#0f766e;border-color:#0f766e;color:#fff}.pc-kinds a b{margin-left:4px}
.pc-tabs{display:flex;gap:14px;border-bottom:1px solid var(--border,#e5e7eb);margin-bottom:10px;font-size:13px}.pc-tabs a{padding:6px 2px;text-decoration:none;color:#64748b;border-bottom:2px solid transparent}.pc-tabs a.on{color:#0f766e;border-color:#0f766e;font-weight:600}
.pc-list{width:100%;min-width:0!important;border-collapse:collapse;font-size:12.5px;background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:10px;overflow:hidden}.pc-list th{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary,#64748b);text-align:left;padding:8px 10px;border-bottom:1px solid var(--border,#e5e7eb);background:#f8fafc}.pc-list td{padding:8px 10px;border-bottom:1px solid #f1f5f9;vertical-align:top}.pc-list tr:last-child td{border-bottom:0}
.pc-kind{display:inline-block;width:20px;height:20px;border-radius:6px;background:#e2e8f0;color:#334155;font-weight:700;font-size:11px;text-align:center;line-height:20px;margin-right:6px}.pc-kind.C,.pc-kind.D,.pc-kind.E{background:#fee2e2;color:#991b1b}.pc-kind.G{background:#fef3c7;color:#92400e}.pc-kind.A,.pc-kind.B{background:#ede9fe;color:#5b21b6}
.pc-muted{font-size:11.5px;color:var(--text-secondary,#64748b)}.pc-detail{max-width:520px}
.pc-res{display:flex;gap:4px}.pc-res input{flex:1;min-width:140px;padding:4px 8px;border:1px solid var(--border,#e5e7eb);border-radius:6px;font-size:12px}.pc-res button{padding:4px 9px;font-size:12px}
.pc-runs{margin-top:16px;font-size:12px;color:#475569}.pc-runs td{padding:3px 8px}
.pc-empty{padding:26px;text-align:center;color:var(--text-secondary,#64748b);font-size:13px}
</style>
@endpush

@section('content')
<div class="pc-head">
    <div>
        <h1>PMS check</h1>
        <p class="pc-sub">Every live eZee reservation compared with its Homemoka booking on unit, dates, status and amount. Runs daily at 06:30 for the last 60 days. Run it for a month before the owner reports: the report cannot start while a difference for that month is open.</p>
    </div>
    @if(admin_can('ezee.manage'))
    <form method="post" action="{{ route('admin.pms.run') }}" class="pc-run">@csrf
        <input type="month" name="month" value="{{ $month ?: now()->subMonthNoOverflow()->format('Y-m') }}">
        <button class="btn btn-primary btn-sm" type="submit" onclick="this.disabled=true;this.textContent='Checking…';this.form.submit()">Run month check</button>
        <button class="btn btn-sm" type="submit" formaction="{{ route('admin.pms.run') }}" name="month" value="" onclick="this.disabled=true;this.form.submit()">Run 60-day check</button>
    </form>
    @endif
</div>

<div class="pc-tiles">
    <div class="pc-tile {{ $openTotal ? 'pc-tile--warn' : 'pc-tile--ok' }}"><span>Open differences</span><b>{{ $openTotal }}</b><small>all months</small></div>
    <div class="pc-tile"><span>Last check</span><b style="font-size:15px">{{ $latest ? $latest->ran_at->format('j M H:i') : '—' }}</b><small>{{ $latest ? $latest->scope . ($latest->month ? ' ' . $latest->month : '') . ' · ' . $latest->reservations . ' reservations, ' . $latest->bookings . ' bookings' : 'not run yet' }}</small></div>
    <div class="pc-tile"><span>Last check found</span><b style="font-size:15px">{{ $latest ? $latest->new . ' new' : '—' }}</b><small>{{ $latest ? $latest->cleared . ' cleared by the data' : '' }}</small></div>
    <div class="pc-tile"><span>Months with open items</span><b style="font-size:15px">{{ $months->count() ?: 'none' }}</b><small>{{ $months->map(fn ($n, $m) => $m . ' (' . $n . ')')->implode(', ') }}</small></div>
</div>

@if($gate)
<div class="pc-gate {{ $gate['ok'] ? 'pc-gate--ok' : 'pc-gate--no' }}"><b>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }} owner reports:</b> {{ $gate['ok'] ? 'clear to send. Month check ran ' . $gate['run']->ran_at->format('j M H:i') . ' with no open difference.' : $gate['why'] }}</div>
@endif

<div class="pc-kinds">
    <a href="{{ route('admin.pms.index', array_filter(['tab' => $tab, 'month' => $month])) }}" class="{{ $kind === '' ? 'on' : '' }}">All</a>
    @foreach($kinds as $k => $label)
        <a href="{{ route('admin.pms.index', array_filter(['tab' => $tab, 'month' => $month, 'kind' => $k])) }}" class="{{ $kind === $k ? 'on' : '' }}">{{ $k }} · {{ $label }}<b>{{ $byKind[$k] ?? 0 }}</b></a>
    @endforeach
</div>
<div class="pc-tabs">
    <a href="{{ route('admin.pms.index', array_filter(['month' => $month, 'kind' => $kind])) }}" class="{{ $tab === 'open' ? 'on' : '' }}">Open</a>
    <a href="{{ route('admin.pms.index', array_filter(['tab' => 'resolved', 'month' => $month, 'kind' => $kind])) }}" class="{{ $tab === 'resolved' ? 'on' : '' }}">Resolved</a>
    @if($month)<a href="{{ route('admin.pms.index', array_filter(['tab' => $tab, 'kind' => $kind])) }}">× {{ $month }}</a>@endif
</div>

<table class="pc-list">
    <thead><tr><th>Kind</th><th>Reservation · booking</th><th>Unit</th><th>Stay</th><th>What differs</th><th>{{ $tab === 'open' ? 'Resolve' : 'Resolution' }}</th></tr></thead>
    <tbody>
    @forelse($rows as $r)
        <tr>
            <td><span class="pc-kind {{ $r->kind }}">{{ $r->kind }}</span>{{ $kinds[$r->kind] ?? '' }}</td>
            <td>
                @if($r->sub_booking_id)<a href="/admin/ezee/assignment-log?q={{ urlencode($r->sub_booking_id) }}">{{ $r->sub_booking_id }}</a>@endif
                @if($r->booking_id)<div><a href="/admin/booking/{{ $r->booking_id }}">booking #{{ $r->booking_id }}</a></div>@endif
                <div class="pc-muted">seen {{ $r->first_seen_at?->format('j M') }}@if($r->last_seen_at && $r->first_seen_at && !$r->last_seen_at->isSameDay($r->first_seen_at)) → {{ $r->last_seen_at->format('j M') }}@endif</div>
            </td>
            <td>{{ $r->unit ?: '—' }}</td>
            <td>{{ $r->stay_from?->format('j M') }} → {{ $r->stay_to?->format('j M Y') }}<div class="pc-muted">{{ $r->month }}</div></td>
            <td class="pc-detail">{{ $r->detail }}</td>
            <td>
                @if($tab === 'open')
                    @if(admin_can('ezee.manage'))
                    <form method="post" action="{{ route('admin.pms.resolve', $r) }}" class="pc-res">@csrf<input name="resolution" placeholder="What was checked or done" maxlength="255" required><button class="btn btn-sm" type="submit">Resolve</button></form>
                    @endif
                @else
                    <div>{{ $r->resolution }}</div>
                    <div class="pc-muted">{{ $r->resolved_at?->format('j M H:i') }} · {{ $r->auto_resolved ? 'data' : ($r->resolver->name ?? '') }}</div>
                    @if(admin_can('ezee.manage') && !$r->auto_resolved)<form method="post" action="{{ route('admin.pms.reopen', $r) }}">@csrf<button class="btn btn-sm" type="submit">Reopen</button></form>@endif
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="pc-empty">{{ $tab === 'open' ? 'No open differences' . ($month ? ' for ' . $month : '') . '. eZee and Homemoka agree.' : 'Nothing resolved yet.' }}</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:10px">{{ $rows->links() }}</div>

@if($runs->count())
<div class="pc-runs"><b>Recent checks</b>
<table>@foreach($runs as $run)<tr><td>{{ $run->ran_at->format('j M H:i') }}</td><td>{{ $run->scope }}{{ $run->month ? ' ' . $run->month : '' }}</td><td>{{ $run->from->format('j M') }} → {{ $run->to->format('j M') }}</td><td>{{ $run->reservations }} res · {{ $run->bookings }} bookings</td><td>{{ $run->new }} new · {{ $run->cleared }} cleared · {{ $run->open }} open</td><td>{{ $run->user->name ?? 'scheduler' }}</td><td>{{ $run->duration_ms }} ms</td></tr>@endforeach</table></div>
@endif
@endsection
