@extends('admin.layout')
@section('title', 'Owner reports')
@section('page-title', 'Owner reports')

@section('content')
@php
    $rm = fn ($v) => $v === null ? '—' : 'RM ' . number_format($v, 2);
    $chip = ['draft' => ['Not started', 'or-chip--draft'], 'queued' => ['Queued', 'or-chip--run'], 'running' => ['Generating…', 'or-chip--run'], 'done' => ['Done', 'or-chip--done'], 'error' => ['Failed', 'or-chip--err']];
@endphp

<div class="or-head">
    <div>
        <h1>Owner reports</h1>
        <p class="or-sub">Monthly landlord-payout pack (Excel + PDF per unit, groups, compilation). Upload the Master file, confirm the data tab and the month's settings, generate, download the zip. The bookings sheet is built from Homemoka.</p>
    </div>
</div>

@if(!$configured)
<div class="or-banner">The report service address is not set yet (MOKA_REPORTS_URL). Uploads work; generating will fail until it is.</div>
@endif

<div class="or-grid">
    <div class="card">
        <div class="card-body">
            <h3 class="or-h3">New report</h3>
            <form method="post" action="{{ route('admin.reports.store') }}" enctype="multipart/form-data" class="or-form">
                @csrf
                <label>Report month
                    <input type="month" name="month" value="{{ old('month', $defaultMonth) }}" required>
                </label>
                <label>Master file <small>Master_Utilities.xlsx</small>
                    <input type="file" name="master" accept=".xlsx" required>
                </label>
                <label>Bookings sheet <small>optional: leave empty and Homemoka builds it from the month's bookings (the “Moka” sheet); or upload a Reconciled_FINAL.xlsx</small>
                    <input type="file" name="recon" accept=".xlsx">
                </label>
                <details class="or-adv">
                    <summary>Previous month's master (optional)</summary>
                    <p class="or-help">Used only to flag units that are new or gone. Leave empty to reuse the master from last month's run.</p>
                    <input type="file" name="prev" accept=".xlsx">
                </details>
                @php $pmsGate = \App\Support\PmsReconcile::gate(old('month', $defaultMonth)); @endphp
                <div style="font-size:12.5px;padding:8px 10px;border-radius:8px;margin:6px 0;{{ $pmsGate['ok'] ? 'background:#dcfce7;color:#166534' : 'background:#fef3c7;color:#92400e' }}">
                    <b>PMS check for {{ \Carbon\Carbon::createFromFormat('Y-m', old('month', $defaultMonth))->format('F Y') }}:</b>
                    {{ $pmsGate['ok'] ? 'clear, checked ' . $pmsGate['run']->ran_at->format('j M H:i') . ' with no open difference.' : $pmsGate['why'] }}
                    <a href="/admin/pms-check?month={{ old('month', $defaultMonth) }}">Open the PMS check</a>
                    @if(!$pmsGate['ok'] && admin_is_super())
                    <label style="display:block;margin-top:6px">Override (super admin, reason required) <input name="gate_override" maxlength="200" placeholder="Why this run may go ahead" style="width:100%;margin-top:3px"></label>
                    @endif
                </div>
                <button class="btn btn-primary" type="submit">Upload and continue</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3 class="or-h3">Past runs @if($active)<span class="or-chip or-chip--run">{{ $active }} in progress</span>@endif</h3>
            <table class="or-table">
                <thead><tr><th>Month</th><th>Status</th><th>By</th><th class="num">Payable</th><th class="num">Moka NP</th><th>Flags</th><th></th></tr></thead>
                <tbody>
                @forelse($runs as $r)
                    <tr>
                        <td><a href="{{ route('admin.reports.show', $r) }}"><b>{{ $r->monthLabel() }}</b></a><div class="or-muted">{{ $r->created_at->format('j M Y H:i') }}</div></td>
                        <td><span class="or-chip {{ $chip[$r->status][1] }}">{{ $chip[$r->status][0] }}</span></td>
                        <td>{{ $r->user->name ?? '—' }}</td>
                        <td class="num">{{ $r->status === 'done' ? $rm($r->total_payable) : '' }}</td>
                        <td class="num">{{ $r->status === 'done' ? $rm($r->total_moka_np) : '' }}</td>
                        <td>{{ $r->status === 'done' ? (count($r->flags ?? []) ?: 'none') : '' }}</td>
                        <td class="num">@if($r->status === 'done')<a class="btn btn-secondary or-btn-sm" href="{{ route('admin.reports.download', $r) }}">Download</a>@else<a class="or-link" href="{{ route('admin.reports.show', $r) }}">Open</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="or-empty">No reports generated yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $runs->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('admin.reports._style')
@endpush
