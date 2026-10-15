@extends('admin.layout')
@section('title', 'Tenancy statements')
@section('page-title', 'Tenancy statements')

@push('styles')
<style>
.ts-head{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;margin-bottom:12px}.ts-head h1{margin:0 0 2px;font-size:20px}.ts-sub{margin:0;font-size:12.5px;color:var(--text-secondary,#64748b);max-width:720px}
.ts-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:12px}@media (max-width:900px){.ts-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
.ts-tile{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:10px 12px;display:grid;gap:3px}.ts-tile span{font-size:11.5px;color:var(--text-secondary,#64748b)}.ts-tile b{font-size:20px;line-height:1.1}.ts-tile small{font-size:11px;color:var(--text-secondary,#64748b)}.ts-tile--warn b{color:#b45309}
.ts-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px}.ts-bar input{padding:6px 10px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;min-width:260px}
.ts-list{width:100%;min-width:0!important;border-collapse:collapse;font-size:13px;background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:10px;overflow:hidden}.ts-list th{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary,#64748b);text-align:left;padding:8px 10px;border-bottom:1px solid var(--border,#e5e7eb);background:#f8fafc}.ts-list td{padding:8px 10px;border-bottom:1px solid #f1f5f9;vertical-align:top}.ts-list tr:last-child td{border-bottom:0}.ts-list .num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
.ts-no{font-weight:700;color:#0f766e;text-decoration:none}.ts-no:hover{text-decoration:underline}.ts-muted{font-size:11.5px;color:var(--text-secondary,#64748b)}
.ts-chip{display:inline-block;font-size:11px;padding:1px 8px;border-radius:999px;font-weight:600;white-space:nowrap}.ts-chip--ok{background:#dcfce7;color:#166534}.ts-chip--bad{background:#fee2e2;color:#991b1b}.ts-chip--void{background:#e2e8f0;color:#475569;text-decoration:line-through}.ts-chip--warn{background:#fef3c7;color:#92400e}
.ts-void td{color:#94a3b8}.ts-void .ts-no{color:#94a3b8}
.ts-dl{display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#0f766e;text-decoration:none;font-weight:600}.ts-dl svg{width:14px;height:14px}
.ts-empty{padding:28px;text-align:center;color:var(--text-secondary,#64748b);font-size:13px}
</style>
@endpush

@section('content')
<div class="ts-head">
    <div>
        <h1>Tenancy statements</h1>
        <p class="ts-sub">Utility proration and deposit settlement for long-stay tenants. Every statement issued here is final: the figures and the PDF are kept on file. A wrong one is voided and re-issued, never edited.</p>
    </div>
    @if(admin_can('tenancy.manage'))
    <a href="{{ route('admin.tenancy.create') }}" class="btn btn-primary">+ New statement</a>
    @endif
</div>

<div class="ts-tiles">
    <div class="ts-tile"><span>Issued this month</span><b>{{ $stats['month'] }}</b><small>{{ now()->format('F Y') }}</small></div>
    <div class="ts-tile"><span>Refunds due to tenants</span><b>RM {{ number_format($stats['refund'], 2) }}</b><small>this month's statements</small></div>
    <div class="ts-tile"><span>Still owed by tenants</span><b>RM {{ number_format($stats['owed'], 2) }}</b><small>this month's statements</small></div>
    <div class="ts-tile {{ $stats['nopdf'] ? 'ts-tile--warn' : '' }}"><span>Missing PDF</span><b>{{ $stats['nopdf'] }}</b><small>{{ $stats['nopdf'] ? 'open the statement and re-issue the PDF' : 'all statements have their PDF on file' }}</small></div>
</div>

<form method="get" class="ts-bar">
    <input type="search" name="q" value="{{ $q }}" placeholder="Statement no., tenant, unit, booking ref…">
    <button class="btn btn-sm" type="submit">Search</button>
    @if($q)<a href="{{ route('admin.tenancy.index') }}" class="btn btn-sm">Clear</a>@endif
</form>

<table class="ts-list">
    <thead><tr><th>Statement</th><th>Tenant</th><th>Property · units</th><th>Stay</th><th class="num">Charges</th><th class="num">Deposit</th><th>Outcome</th><th>PDF</th></tr></thead>
    <tbody>
    @forelse($rows as $r)
        <tr class="{{ $r->isVoid() ? 'ts-void' : '' }}">
            <td><a href="{{ route('admin.tenancy.show', $r) }}" class="ts-no">{{ $r->no }}</a><div class="ts-muted">{{ $r->created_at->format('j M Y H:i') }} · {{ $r->prepared_by ?: ($r->creator->name ?? '') }}</div></td>
            <td>{{ $r->tenant_name }}@if($r->booking_ref)<div class="ts-muted">{{ $r->booking_ref }}</div>@endif</td>
            <td>{{ $r->property ?: '—' }}@if($r->unit_names)<div class="ts-muted">{{ $r->unit_names }}</div>@endif</td>
            <td>@if($r->stay_from){{ $r->stay_from->format('j M') }} → {{ $r->stay_to->format('j M Y') }}<div class="ts-muted">{{ $r->stay_days }} {{ $r->inclusive ? 'days' : 'nights' }}</div>@else — @endif</td>
            <td class="num">RM {{ number_format($r->total, 2) }}</td>
            <td class="num">{{ $r->deposit > 0 ? 'RM ' . number_format($r->deposit, 2) : '—' }}</td>
            <td>
                @if($r->isVoid())<span class="ts-chip ts-chip--void">Void</span><div class="ts-muted">{{ $r->void_reason }}</div>
                @elseif($r->balance === null)<span class="ts-chip ts-chip--warn">Payable RM {{ number_format($r->total, 2) }}</span>
                @elseif($r->balance >= 0)<span class="ts-chip ts-chip--ok">Refund RM {{ number_format($r->balance, 2) }}</span>
                @else<span class="ts-chip ts-chip--bad">Owed RM {{ number_format(abs($r->balance), 2) }}</span>@endif
            </td>
            <td>@if($r->pdf_path)<a href="{{ route('admin.tenancy.pdf', $r) }}" class="ts-dl"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>PDF</a>@else<span class="ts-chip ts-chip--warn">no PDF</span>@endif</td>
        </tr>
    @empty
        <tr><td colspan="8" class="ts-empty">No statements yet. @if(admin_can('tenancy.manage'))<a href="{{ route('admin.tenancy.create') }}">Issue the first one</a>.@endif</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:10px">{{ $rows->links() }}</div>
@endsection
