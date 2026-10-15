@extends('admin.layout')
@section('title', 'Deposit refunds')
@section('page-title', 'Deposit refunds')

@push('styles')
<style>
.rf-head{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;margin-bottom:12px}.rf-head h1{margin:0 0 2px;font-size:20px}.rf-sub{margin:0;font-size:12.5px;color:var(--text-secondary);max-width:760px;line-height:1.45}
.rf-tiles{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:12px}@media (max-width:900px){.rf-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
.rf-tile{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:10px 12px;display:grid;gap:3px;text-decoration:none;color:inherit;transition:box-shadow .12s}.rf-tile:hover{box-shadow:0 2px 8px rgba(0,0,0,.08)}.rf-tile span{font-size:11.5px;color:var(--text-secondary)}.rf-tile b{font-size:20px;line-height:1.15}
.rf-tiles--6{grid-template-columns:repeat(6,minmax(0,1fr))}@media (max-width:1100px){.rf-tiles--6{grid-template-columns:repeat(3,minmax(0,1fr))}}.rf-tile small{font-size:11px;color:var(--text-secondary);line-height:1.2}
.rf-tile--main{background:#0f766e;border-color:#0f766e;color:#fff}.rf-tile--main span,.rf-tile--main small{color:rgba(255,255,255,.85)}.rf-tile--warn b{color:#b45309}.rf-tile--bad b{color:#b91c1c}.rf-tile--ok b{color:#166534}.rf-tile.on{outline:2px solid #0f766e;outline-offset:1px}
.rf-link{background:#f0fdfa;border:1px solid #99f6e4;border-radius:10px;padding:10px 12px;margin-bottom:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;font-size:12.5px}.rf-link code{background:#fff;border:1px solid #99f6e4;border-radius:6px;padding:3px 8px;font-size:12px;word-break:break-all}
.rf-tabs{display:flex;gap:2px;border-bottom:1px solid var(--border,#e5e7eb);margin-bottom:10px;flex-wrap:wrap;align-items:center}.rf-tabs a{padding:8px 12px;font-size:13px;color:var(--text-secondary);text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-1px;white-space:nowrap}.rf-tabs a.on{color:#0f766e;border-color:#0f766e;font-weight:600}.rf-tabs a i{font-style:normal;background:#f1f5f9;border-radius:999px;padding:0 6px;font-size:11px;margin-left:4px}.rf-tabs a.on i{background:#ccfbf1;color:#0f766e}
.rf-filter{margin-left:auto;display:flex;gap:6px;flex-wrap:wrap;align-items:center;padding:4px 0}.rf-filter input,.rf-filter select{padding:5px 8px;font-size:12.5px;border:1px solid var(--border,#e5e7eb);border-radius:8px;background:#fff;font-family:inherit}
.rf-table{width:100%;border-collapse:collapse;font-size:12px;line-height:1.35}.rf-table th,.rf-table td{padding:8px 6px;border-bottom:1px solid var(--border,#e5e7eb);text-align:left;vertical-align:top}.rf-table th{font-size:11px;text-transform:uppercase;letter-spacing:.03em;color:var(--text-secondary);white-space:nowrap}.rf-table tbody tr:hover{background:#fafcfb}
.rf-table tr.overdue td:first-child{box-shadow:inset 3px 0 #dc2626}.rf-table tr.verify td:first-child{box-shadow:inset 3px 0 #f59e0b}.rf-table tr.dup td:first-child{box-shadow:inset 3px 0 #ef4444}
.rf-muted{font-size:11.5px;color:var(--text-secondary)}.rf-bank{font-family:ui-monospace,Menlo,monospace;font-size:12.5px;display:flex;gap:6px;align-items:center}.rf-bank button{border:1px solid var(--border,#e5e7eb);background:#fff;border-radius:6px;font-size:10.5px;padding:1px 6px;cursor:pointer;color:var(--text-secondary)}
.rf-chip{display:inline-block;font-size:11px;padding:1px 8px;border-radius:999px;font-weight:600;white-space:nowrap}.rf-chip--ok{background:#dcfce7;color:#166534}.rf-chip--warn{background:#fef3c7;color:#92400e}.rf-chip--dup{background:#fee2e2;color:#991b1b}.rf-chip--paid{background:#dbeafe;color:#1d4ed8}.rf-chip--rej{background:#f1f5f9;color:#475569}
.rf-stars{color:#f59e0b;letter-spacing:1px;white-space:nowrap}.rf-note{font-size:11.5px;color:#92400e;margin-top:3px;line-height:1.35}.rf-due-bad{color:#b91c1c;font-weight:600}.rf-due-soon{color:#b45309;font-weight:600}
.rf-act{display:grid;gap:5px;width:190px}.rf-act form{display:flex;gap:4px}.rf-act input{flex:1;min-width:0;width:100px;padding:4px 6px;font-size:12px;border:1px solid var(--border,#e5e7eb);border-radius:6px}.rf-act .btn{padding:4px 9px;font-size:12px;white-space:nowrap}.rf-more{background:none;border:0;color:#0f766e;font-size:11.5px;font-weight:600;cursor:pointer;text-align:left;padding:0}.rf-more-box{display:grid;gap:5px}.rf-more-box[hidden]{display:none}
.rf-table td.rf-wrap{white-space:normal;max-width:220px}.rf-ell{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px}
.rf-empty{color:var(--text-secondary);padding:22px!important;text-align:center}
.rf-list{table-layout:fixed;min-width:0!important;width:100%}.rf-list th,.rf-list td{padding:8px 7px;vertical-align:middle;overflow:hidden}.rf-list td.rf-wrap,.rf-list td.num{white-space:normal}.rf-list .rf-steps{white-space:normal}.rf-detail td{overflow:visible}.rf-list .rf-tick{padding-left:10px}.rf-list tr.rf-row{cursor:pointer}.rf-list tr.rf-row:hover{background:#f8fafc}.rf-list tr.rf-row.open{background:#f0fdfa}.rf-open{border:1px solid var(--border,#e5e7eb);background:#fff;border-radius:6px;width:26px;height:26px;cursor:pointer;color:#0f766e;font-size:13px}
.rf-dupbox{margin-top:8px;padding:8px 10px;border:1px solid #fecaca;background:#fef2f2;border-radius:8px;font-size:12px;line-height:1.45}.rf-dupbox b{color:#991b1b;display:block;margin-bottom:2px}
.rf-pill{display:inline-block;font-size:11px;font-weight:700;padding:1px 8px;border-radius:999px;background:#f1f5f9;color:#475569;white-space:nowrap}.rf-pill--bad{background:#fee2e2;color:#991b1b}.rf-pill--warn{background:#fef3c7;color:#92400e}.rf-pill--soon{background:#fef9c3;color:#854d0e}.rf-pill--ok{background:#dcfce7;color:#166534}
.rf-section td{background:#f8fafc;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-secondary);padding:6px 10px!important;border-bottom:1px solid var(--border,#e5e7eb)}.rf-section--overdue td{color:#991b1b;background:#fef2f2}.rf-section--due-today td{color:#92400e;background:#fffbeb}
.rf-row.ready td.rf-tick{box-shadow:inset 3px 0 #16a34a}
.rf-quick{display:flex;gap:6px;align-items:center;flex-wrap:wrap;font-size:12px;margin:0 0 8px}.rf-qbtn{border:1px solid var(--border,#e5e7eb);background:#fff;border-radius:999px;padding:3px 10px;font-size:12px;cursor:pointer;color:#0f766e;font-weight:600}.rf-qbtn:hover{background:#f0fdfa}
.rf-steps{white-space:nowrap}.rf-step{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.02em;padding:2px 6px;border-radius:999px;background:#f1f5f9;color:#94a3b8;margin-right:2px}.rf-step.on{background:#dcfce7;color:#166534}.rf-step.off{background:#fee2e2;color:#991b1b;text-decoration:line-through}
.rf-detail td{background:#fafcfb;padding:12px 14px 14px;border-bottom:2px solid #ccfbf1}.rf-panel{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}@media (max-width:1000px){.rf-panel{grid-template-columns:1fr}}.rf-pane{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:10px 12px;font-size:12.5px;line-height:1.45;min-width:0}.rf-pane h4{margin:0 0 6px;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary)}
.rf-forms{display:flex;gap:4px;margin-top:6px;flex-wrap:wrap}.rf-forms input{flex:1;min-width:120px;padding:5px 7px;font-size:12px;border:1px solid var(--border,#e5e7eb);border-radius:6px}.rf-forms form{display:flex;gap:4px;flex:1;min-width:200px}
.rf-bulk-pay{display:inline-flex;gap:4px;align-items:center}.rf-bulk-pay input{min-width:150px}
.rf-dep{min-width:130px;white-space:normal}.rf-cut{font-size:11.5px;color:#b45309}.rf-recv{margin-top:4px;font-size:11.5px}.rf-deduct{color:#b91c1c;font-weight:600}.rf-deduct .rf-note{font-weight:400}.rf-due{font-weight:700;color:#0f766e;margin-top:2px}
.rf-ded{margin-top:4px;font-size:11.5px}.rf-ded summary{cursor:pointer;color:#0f766e;font-weight:600}.rf-ded-form{display:grid;gap:4px;margin-top:4px}.rf-ded-form input{padding:4px 6px;font-size:12px;border:1px solid var(--border,#e5e7eb);border-radius:6px;width:130px}.rf-ded-form .btn{padding:4px 9px;font-size:12px;width:max-content}
.rf-grp{display:inline-block;margin-top:3px;font-size:10.5px;font-weight:700;color:#0f766e;background:#ccfbf1;border-radius:999px;padding:0 6px;white-space:nowrap}
.rf-tick{width:28px;text-align:center}.rf-tick input{width:16px;height:16px;cursor:pointer;accent-color:#0f766e}
.rf-bulk[hidden]{display:none}.rf-bulk{display:flex;gap:8px;align-items:center;flex-wrap:wrap;background:#0f766e;color:#fff;border-radius:10px;padding:8px 12px;margin-bottom:10px;font-size:13px;position:sticky;top:8px;z-index:5}.rf-bulk input{padding:5px 8px;font-size:12.5px;border:1px solid rgba(255,255,255,.4);border-radius:8px;min-width:200px}.rf-bulk .rf-muted{color:rgba(255,255,255,.8)}
.rf-table tr.rf-gone{opacity:.35;transition:opacity .4s}.rf-flash{position:fixed;right:16px;bottom:16px;background:#0f766e;color:#fff;padding:10px 14px;border-radius:10px;font-size:13px;box-shadow:0 6px 20px rgba(0,0,0,.2);z-index:50}
.rf-sugg{border:1px dashed #99f6e4;border-radius:8px;padding:6px 8px;background:#f0fdfa;display:grid;gap:4px}.rf-sugg-h{font-size:10.5px;text-transform:uppercase;letter-spacing:.04em;color:#0f766e;font-weight:700}.rf-sugg-row{display:flex;gap:6px;align-items:center;justify-content:space-between}.rf-sugg-t{font-size:12px;line-height:1.3;white-space:normal;min-width:0}.rf-sugg-t .rf-muted{white-space:normal}
.rf-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;align-items:start}@media (max-width:900px){.rf-grid{grid-template-columns:1fr}}
.rf-h3{margin:0 0 8px;font-size:14px;display:flex;align-items:center;gap:8px}.rf-quote{border-left:3px solid #99f6e4;padding:4px 10px;margin:6px 0;font-size:12.5px;line-height:1.4}.rf-quote small{display:block;color:var(--text-secondary)}.rf-quote.unv{border-color:#fde68a}
.rf-sm{padding:4px 10px;font-size:12px}.rf-recv-btn{display:block;margin-top:6px;background:#0f766e;border-color:#0f766e}.rf-recv-btn:hover{background:#115e59;border-color:#115e59}.rf-rate{display:grid;grid-template-columns:auto 1fr;gap:2px 8px;align-items:center;margin-top:6px;font-size:12px;color:#475569}.rf-rate .rf-stars{font-size:14px;letter-spacing:1px;color:#f59e0b}.rf-rate .rf-stars i{font-style:normal;color:#e2e8f0}
.rf-mnav{display:flex;gap:6px;align-items:center}.rf-mnav a{text-decoration:none;font-size:14px;color:#0f766e;padding:0 6px}
.rf-kpi-bar{height:6px;background:#f1f5f9;border-radius:999px;overflow:hidden;width:70px;display:inline-block;vertical-align:middle;margin-left:6px}.rf-kpi-bar i{display:block;height:100%;background:#0f766e}
</style>
@endpush

@section('content')
@php
    $stars = fn ($n) => $n ? str_repeat('★', (int) $n) . str_repeat('☆', 5 - (int) $n) : '—';
    $gold = fn ($n) => $n ? '<span class="rf-stars" title="' . (int) $n . ' of 5">' . str_repeat('★', (int) $n) . '<i>' . str_repeat('★', 5 - (int) $n) . '</i></span>' : '<span class="rf-muted">not rated</span>';
    $matchChip = ['matched' => ['Matched', 'rf-chip--ok'], 'verify' => ['Verify', 'rf-chip--warn'], 'duplicate' => ['Duplicate', 'rf-chip--dup']];
    $qs = fn (array $extra) => '?' . http_build_query(array_filter(['tab' => $tab, 'q' => $q, 'property' => $prop, 'from' => $from, 'to' => $to] + $extra, fn ($v) => $v !== '' && $v !== null));
@endphp

<div class="rf-head">
    <div>
        <h1>Deposit refunds</h1>
        <p class="rf-sub"><b>Target: every deposit refunded within 3 working days of the guest's request.</b> Operations record deductions by noon the next working day; Finance confirms receipt and pays. Bank details are encrypted and shown only to Finance. Nothing is ever deleted.</p>
    </div>
</div>

@if(admin_can('refunds.link'))
<div class="rf-link">
    <b>Current refund link</b>
    @if($link)
        <code id="rf-url">{{ $link->url() }}</code>
        <button type="button" class="btn btn-secondary rf-sm" onclick="navigator.clipboard.writeText(document.getElementById('rf-url').textContent).then(()=>{this.textContent='Copied';setTimeout(()=>this.textContent='Copy link',1500)})">Copy link</button>
        <span class="rf-muted">valid until {{ $link->expires_at->format('D d M, g:ia') }} · a new one is issued every day at 9am</span>
    @else
        <span class="rf-muted">none valid right now; the dashboard issues one when opened, and the 9am job every morning</span>
    @endif
    <form method="post" action="{{ route('admin.refunds.link') }}" style="margin-left:auto">@csrf<button class="btn btn-secondary rf-sm" type="submit">New link (48 h)</button></form>
</div>
@endif

<div class="rf-tiles rf-tiles--6">
    <a class="rf-tile rf-tile--main {{ $tab === 'pending' ? 'on' : '' }}" href="?tab=pending"><span>Pending</span><b>{{ $counts['pending'] }}</b><small>target: refund in 3 working days</small></a>
    <a class="rf-tile rf-tile--bad" href="?tab=pending"><span>Overdue</span><b>{{ $counts['overdue'] }}</b><small>past the 3-day target</small></a>
    <a class="rf-tile rf-tile--warn" href="?tab=pending"><span>Due today</span><b>{{ $counts['today'] }}</b><small>pay before close of day</small></a>
    <a class="rf-tile rf-tile--ok" href="?tab=pending"><span>Ready to pay</span><b>{{ $counts['ready'] }}</b><small>Ops checked · deposit received</small></a>
    <a class="rf-tile {{ $tab === 'verify' ? 'on' : '' }}" href="?tab=verify"><span>Need verifying</span><b>{{ $counts['verify'] }}</b><small>no clean booking match</small></a>
    <a class="rf-tile {{ $tab === 'paid' ? 'on' : '' }}" href="?tab=paid"><span>Paid on time</span><b>{{ $counts['ontime30'] === null ? '—' : $counts['ontime30'] . '%' }}</b><small>{{ $counts['paid30'] }} paid in the last 30 days</small></a>
</div>

<div class="rf-tabs">
    @if(admin_can('refunds.view'))
    <a href="?tab=pending" class="{{ $tab === 'pending' ? 'on' : '' }}">Pending<i>{{ $counts['pending'] }}</i></a>
    <a href="?tab=verify" class="{{ $tab === 'verify' ? 'on' : '' }}">Verify<i>{{ $counts['verify'] }}</i></a>
    <a href="?tab=paid" class="{{ $tab === 'paid' ? 'on' : '' }}">Paid<i>{{ $counts['paid'] }}</i></a>
    <a href="?tab=rejected" class="{{ $tab === 'rejected' ? 'on' : '' }}">Rejected<i>{{ $counts['rejected'] }}</i></a>
    <a href="?tab=all" class="{{ $tab === 'all' ? 'on' : '' }}">All</a>
    @endif
    @if(admin_can('refunds.feedback'))
    <a href="?tab=feedback" class="{{ $tab === 'feedback' ? 'on' : '' }}">Guest feedback</a>
    @endif
    @if($tab !== 'feedback')
    <form method="get" class="rf-filter">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <select name="property" onchange="this.form.submit()"><option value="">All properties</option>@foreach($properties as $p)<option value="{{ $p }}" {{ $prop === $p ? 'selected' : '' }}>{{ $p }}</option>@endforeach</select>
        <input type="search" name="q" value="{{ $q }}" placeholder="Name, phone, unit, booking #" style="width:180px">
        <input type="date" name="from" value="{{ $from }}" title="{{ $tab === 'paid' ? 'Paid from' : 'Submitted from' }}">
        <span class="rf-muted">to</span>
        <input type="date" name="to" value="{{ $to }}" title="{{ $tab === 'paid' ? 'Paid to' : 'Submitted to' }}">
        <button class="btn btn-secondary rf-sm" type="submit">Apply</button>
        @if($canManage)<a class="btn btn-secondary rf-sm" href="{{ route('admin.refunds.export') }}{{ $qs([]) }}" title="Excel file of this list with bank details{{ $from || $to ? ', ' . ($tab === 'paid' ? 'paid' : 'submitted') . ' ' . ($from ?: '…') . ' to ' . ($to ?: '…') : '' }}">Export Excel{{ $from || $to ? ' (range)' : '' }}</a>@endif
        <a class="btn btn-secondary rf-sm" href="{{ route('admin.refunds.contacts') }}" title="Guests who ticked the offers box: name, email, phone. No bank details.">Contacts Excel</a>
    </form>
    @else
    <form method="get" class="rf-filter"><input type="hidden" name="tab" value="feedback"><input type="month" name="month" value="{{ $ym }}"><button class="btn btn-secondary rf-sm" type="submit">Show</button></form>
    @endif
</div>

@if($tab === 'feedback' && $feedback)
    <div class="rf-h3 rf-mnav" style="margin-bottom:10px"><a href="?tab=feedback&month={{ $feedback['prev'] }}">‹</a><b>{{ $feedback['month'] }}</b><a href="?tab=feedback&month={{ $feedback['next'] }}">›</a><span class="rf-muted">{{ $feedback['verified'] }} verified stays{{ $feedback['unverified'] ? ', ' . $feedback['unverified'] . ' unverified (not counted in the KPI)' : '' }}</span></div>
    <div class="rf-tiles">
        <div class="rf-tile"><span>Responses</span><b>{{ $feedback['total'] }}</b></div>
        <div class="rf-tile"><span>Verified stays</span><b>{{ $feedback['verified'] }}</b></div>
        <div class="rf-tile"><span>Room cleanliness</span><b>{{ $feedback['clean'] ?? '—' }}<small class="rf-muted"> / 5</small></b></div>
        <div class="rf-tile"><span>Customer service</span><b>{{ $feedback['service'] ?? '—' }}<small class="rf-muted"> / 5</small></b></div>
        <div class="rf-tile"><span>Team members named</span><b>{{ $feedback['staff']->count() }}</b></div>
    </div>
    <div class="rf-grid">
        <div class="card"><div class="card-body">
            <h3 class="rf-h3">Team KPI · {{ $feedback['month'] }}</h3>
            <table class="rf-table">
                <thead><tr><th>Team member</th><th>Named</th><th>Average</th><th>5-star</th><th>1–2 star</th></tr></thead>
                <tbody>
                @forelse($feedback['staff'] as $s)
                    <tr><td><b>{{ trim(($s['user']->name ?? '?') . ' ' . ($s['user']->last_name ?? '')) }}</b></td><td>{{ $s['count'] }}</td>
                        <td>@if($s['avg'] !== null){{ $s['avg'] }} <span class="rf-stars">{{ $stars(round($s['avg'])) }}</span><span class="rf-kpi-bar"><i style="width:{{ $s['avg'] / 5 * 100 }}%"></i></span>@else — @endif</td>
                        <td>{{ $s['fives'] }}</td><td>{{ $s['low'] }}</td></tr>
                @empty
                    <tr><td colspan="5" class="rf-empty">No verified guest has named a team member this month yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            @foreach($feedback['staff'] as $s)
                @foreach($s['comments'] as $c)
                    <div class="rf-quote">“{{ $c->staff_comment }}”<small>for {{ trim(($s['user']->name ?? '') . ' ' . ($s['user']->last_name ?? '')) }} · {{ $stars($c->staff_rating) }} · {{ $c->name }}, {{ $c->property }} {{ $c->unit_no }}, {{ $c->created_at->format('d M') }}</small></div>
                @endforeach
            @endforeach
        </div></div>
        <div class="card"><div class="card-body">
            <h3 class="rf-h3">By property</h3>
            <table class="rf-table">
                <thead><tr><th>Property</th><th>Verified</th><th>Cleanliness</th><th>Service</th></tr></thead>
                <tbody>
                @forelse($feedback['properties'] as $p => $v)
                    <tr><td>{{ $p }}</td><td>{{ $v['count'] }}</td><td>{{ $v['clean'] ?? '—' }}</td><td>{{ $v['service'] ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="4" class="rf-empty">No verified responses this month yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            <h3 class="rf-h3" style="margin-top:14px">Guest comments</h3>
            @forelse($feedback['comments'] as $c)
                <div class="rf-quote {{ $c->booking_id ? '' : 'unv' }}">“{{ $c->comments }}”<small>{{ $c->name }} · {{ $c->property }} {{ $c->unit_no }} · clean {{ $c->rating_clean }}/5, service {{ $c->rating_service }}/5 · {{ $c->created_at->format('d M') }}{{ $c->booking_id ? '' : ' · unverified' }}</small></div>
            @empty
                <p class="rf-muted">No comments this month yet.</p>
            @endforelse
        </div></div>
    </div>
@else
@php $listTab = $tab !== 'paid' && $tab !== 'rejected'; @endphp
@if(($canManage || $canDeduct) && $listTab)
<form method="post" action="{{ route('admin.refunds.bulk') }}" id="rf-bulk" class="rf-bulk" hidden>@csrf
    <input type="hidden" name="action" id="rf-bulk-action" value="">
    <b><span id="rf-bulk-n">0</span> selected</b>
    @if($canDeduct || $canManage)<button class="btn btn-secondary rf-sm" type="button" data-action="nodeduct" title="Operations: checked, nothing to deduct, full deposit to refund">✓ No deduction</button>@endif
    @if($canManage)
    <button class="btn btn-secondary rf-sm" type="button" data-action="received" title="Finance: deposit seen on the bank statement / SQL accounting">✓ Deposit received</button>
    <span class="rf-bulk-pay"><input name="reference" placeholder="Bank ref (optional)" maxlength="120"><button class="btn btn-primary rf-sm" type="button" data-action="paid" title="Pays confirmed-received rows only">Mark paid</button></span>
    @endif
    <button class="btn btn-secondary rf-sm" type="button" id="rf-bulk-clear">Clear</button>
</form>
@endif
@if(($canManage || $canDeduct) && $listTab && $rows->count())
<div class="rf-quick"><span class="rf-muted">Select:</span><button type="button" class="rf-qbtn" data-select="ready">ready to pay</button><button type="button" class="rf-qbtn" data-select="overdue">overdue</button><button type="button" class="rf-qbtn" data-select="today">due today</button><button type="button" class="rf-qbtn" data-select="unchecked">ops not checked</button><span class="rf-muted" style="margin-left:auto">sorted by due date, most urgent first</span></div>
@endif
<div class="card"><div class="card-body" style="padding:0">
    <table class="rf-table rf-list">
        <colgroup>@if(($canManage || $canDeduct) && $listTab)<col style="width:34px">@endif<col style="width:54px"><col style="width:21%"><col style="width:20%"><col style="width:15%"><col style="width:92px"><col style="width:148px"><col style="width:66px"><col style="width:40px"></colgroup>
        <thead><tr>
            @if(($canManage || $canDeduct) && $listTab)<th class="rf-tick"><input type="checkbox" id="rf-all" aria-label="Select all"></th>@endif
            <th>#</th><th>Guest</th><th>Stay</th><th>Booking</th><th class="num">Refund</th><th>Progress</th><th>Due</th><th></th>
        </tr></thead>
        <tbody>
        @forelse($rows as $r)
            @php
                [$mLabel, $mClass] = $matchChip[$r->match] ?? [$r->match, 'rf-chip--warn'];
                $b = $r->booking; $due = $r->refundDue();
                $rowClass = $r->isOverdue() ? 'overdue' : ($r->status === 'pending' ? ($r->match === 'duplicate' ? 'dup' : ($r->match !== 'matched' ? 'verify' : '')) : '');
                $daysLeft = $r->due_on ? today()->diffInDays($r->due_on, false) : null;
                $opsDone = (bool) $r->deduction_at; $opsOpen = !$r->opsCutoffPassed();
                $pending = $r->status === 'pending';
            @endphp
            @php
                $bucket = $pending ? ($daysLeft < 0 ? 'Overdue' : ($daysLeft === 0 ? 'Due today' : ($daysLeft === 1 ? 'Due tomorrow' : 'Due later'))) : null;
                $ready = $pending && $r->received_at && ($opsDone || !$opsOpen);
            @endphp
            @if($bucket && $bucket !== ($lastBucket ?? null) && in_array($tab, ['pending', 'verify'], true))
            <tr class="rf-section rf-section--{{ \Illuminate\Support\Str::slug($bucket) }}"><td colspan="{{ (($canManage || $canDeduct) && $listTab) ? 9 : 8 }}">{{ $bucket }}</td></tr>
            @php $lastBucket = $bucket; @endphp
            @endif
            <tr class="rf-row {{ $rowClass }} {{ $ready ? 'ready' : '' }}" data-id="{{ $r->id }}" data-ready="{{ $ready ? 1 : 0 }}" data-overdue="{{ $pending && $daysLeft < 0 ? 1 : 0 }}" data-today="{{ $pending && $daysLeft === 0 ? 1 : 0 }}" data-unchecked="{{ $pending && !$opsDone && $opsOpen ? 1 : 0 }}">
                @if(($canManage || $canDeduct) && $listTab)<td class="rf-tick">@if($pending)<input type="checkbox" class="rf-pick" value="{{ $r->id }}" aria-label="Select request {{ $r->id }}">@endif</td>@endif
                <td><b>{{ $r->id }}</b>@if($r->group_key)<div class="rf-grp" title="One guest, several units">{{ $r->group_index }}/{{ $r->group_size }}</div>@endif</td>
                <td class="rf-wrap"><b>{{ $r->name }}</b><div class="rf-muted">{{ $r->phone }} · {{ $r->created_at->format('d M H:i') }}</div></td>
                <td class="rf-wrap">{{ \Illuminate\Support\Str::before($r->property, ' by') }} <b>{{ $r->unit_no }}</b><div class="rf-muted">out {{ $r->check_out->format('d M') }}</div></td>
                <td>
                    @if($r->status === 'rejected')<span class="rf-chip rf-chip--rej">Rejected</span>
                    @else<span class="rf-chip {{ $mClass }}">{{ $mLabel }}{{ $r->match === 'duplicate' && $r->duplicate_of ? ' · see #' . $r->duplicate_of : '' }}</span>@endif
                    @if($b)<div class="rf-muted"><a href="/admin/book/{{ $b->id }}/edit" target="_blank" rel="noopener">#{{ $b->id }}</a> {{ \Illuminate\Support\Str::limit(trim(($b->user->name ?? '') . ' ' . ($b->user->last_name ?? '')), 18) }}</div>@endif
                </td>
                <td class="num">
                    @if($r->status === 'paid')<b>RM {{ number_format($r->amount_paid ?? 0, 2) }}</b><div class="rf-muted">paid</div>
                    @elseif($due !== null)<b>RM {{ number_format($due, 2) }}</b>@if($r->deduction_amount > 0)<div class="rf-deduct">− {{ number_format($r->deduction_amount, 2) }}</div>@endif
                    @else —@endif
                </td>
                <td class="rf-steps">
                    @if($r->status === 'rejected')<span class="rf-muted">{{ $r->note }}</span>
                    @else
                    <span class="rf-step {{ $opsDone ? 'on' : ($opsOpen ? '' : 'off') }}" title="{{ $opsDone ? 'Operations checked ' . $r->deduction_at->format('d M H:i') : ($opsOpen ? 'Operations can record a deduction until ' . $r->ops_cutoff_at?->format('D d M, g:ia') : 'Cut-off passed, nothing recorded: full deposit') }}">Ops</span>
                    <span class="rf-step {{ $r->received_at ? 'on' : '' }}" title="{{ $r->received_at ? 'Deposit receipt confirmed by ' . ($r->receiver->name ?? '') . ' ' . $r->received_at->format('d M') : 'Deposit receipt not yet confirmed by Finance' }}">Rcvd</span>
                    <span class="rf-step {{ $r->status === 'paid' ? 'on' : '' }}" title="{{ $r->status === 'paid' ? 'Paid ' . $r->paid_at?->format('d M') . ' by ' . ($r->payer->name ?? '') : 'Not paid yet' }}">Paid</span>
                    @endif
                </td>
                <td>@if($pending && $r->due_on)
                        @if($daysLeft < 0)<span class="rf-pill rf-pill--bad">{{ abs($daysLeft) }}d late</span>@elseif($daysLeft === 0)<span class="rf-pill rf-pill--warn">today</span>@elseif($daysLeft === 1)<span class="rf-pill rf-pill--soon">tomorrow</span>@else<span class="rf-pill">{{ $daysLeft }}d</span>@endif
                        <div class="rf-muted">{{ $r->due_on->format('D d M') }}</div>
                    @elseif($r->status === 'paid')
                        @php $late = $r->paid_at && $r->due_on && $r->paid_at->gt($r->due_on->copy()->endOfDay()); @endphp
                        <span class="rf-pill {{ $late ? 'rf-pill--bad' : 'rf-pill--ok' }}">{{ $late ? 'late' : 'on time' }}</span><div class="rf-muted">{{ $r->paid_at?->format('D d M') }}</div>
                    @else —@endif</td>
                <td><button type="button" class="rf-open" aria-expanded="false" title="Details and actions">▸</button></td>
            </tr>
            <tr class="rf-detail" data-for="{{ $r->id }}" hidden><td colspan="{{ (($canManage || $canDeduct) && $listTab) ? 9 : 8 }}">
                <div class="rf-panel">
                    <div class="rf-pane">
                        <h4>Booking</h4>
                        @if($b)<div><a href="/admin/book/{{ $b->id }}/edit" target="_blank" rel="noopener">Booking #{{ $b->id }}</a> · {{ $b->listing->name ?? '' }}<div class="rf-muted">{{ trim(($b->user->name ?? '') . ' ' . ($b->user->last_name ?? '')) }} · {{ \Carbon\Carbon::parse($b->check_in)->format('d M') }}–{{ \Carbon\Carbon::parse($b->check_out)->format('d M') }} · {{ $b->source }} · {{ $b->folio_no }}</div></div>
                        @else<div class="rf-muted">No booking linked yet.</div>@endif
                        @if($r->match_note)<div class="rf-note">{{ $r->match_note }}</div>@endif
                        @if($r->siblings->isNotEmpty())
                        <div class="rf-dupbox">
                            <b>{{ $r->match === 'duplicate' ? 'Duplicate claim' : 'Also claimed by' }}</b>
                            @foreach($r->siblings as $sib)
                            <div>Request #{{ $sib->id }} · {{ $sib->name }} · {{ $sib->created_at->format('d M H:i') }} · {{ $sib->status === 'paid' ? 'PAID ' . $sib->paid_at?->format('d M') : $sib->status }}</div>
                            @endforeach
                            <div class="rf-muted">One deposit per booking. Pay the guest who paid the deposit (check the eZee folio) and reject the other.</div>
                            @if($canManage && $pending)
                            <div class="rf-forms">
                                <form method="post" action="{{ route('admin.refunds.reject', $r) }}" onsubmit="return confirm('Reject request #{{ $r->id }} as the wrong claim on this booking?')">@csrf<input type="hidden" name="note" value="{{ $r->match === 'duplicate' ? 'Duplicate of request #' . $r->duplicate_of . ' (same booking)' : 'Wrong claim on booking #' . $r->booking_id . ', replaced by request #' . ($r->siblings->first()->id ?? '') }}"><button class="btn btn-secondary rf-sm" type="submit">Reject this one{{ $r->match === 'duplicate' ? ' as duplicate' : ', keep the other' }}</button></form>
                                @foreach($r->siblings->where('status', 'pending') as $sib)
                                <form method="post" action="{{ route('admin.refunds.reject', $sib) }}" onsubmit="return confirm('Reject request #{{ $sib->id }} ({{ $sib->name }}) and keep #{{ $r->id }}?')">@csrf<input type="hidden" name="note" value="Wrong claim on booking #{{ $r->booking_id }}, replaced by request #{{ $r->id }}"><button class="btn btn-secondary rf-sm" type="submit">Keep this one, reject #{{ $sib->id }}</button></form>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endif
                        @if(($canManage || $canDeduct) && $pending)
                            @if(!empty($r->suggestions))
                            <div class="rf-sugg"><div class="rf-sugg-h">Likely bookings</div>
                                @foreach($r->suggestions as $s)@php $sb = $s['booking']; @endphp
                                <form method="post" action="{{ route('admin.refunds.attach', $r) }}" class="rf-sugg-row">@csrf<input type="hidden" name="booking_id" value="{{ $sb->id }}">
                                    <div class="rf-sugg-t"><b>{{ $sb->listing->name ?? '' }}</b> · {{ trim(($sb->user->name ?? '') . ' ' . ($sb->user->last_name ?? '')) ?: '—' }}<div class="rf-muted">{{ \Carbon\Carbon::parse($sb->check_in)->format('d M') }}–{{ \Carbon\Carbon::parse($sb->check_out)->format('d M') }} · {{ $sb->source }} · {{ $sb->folio_no }} · {{ $s['why'] }}</div></div>
                                    <button class="btn btn-secondary rf-sm" type="submit">Pick</button>
                                </form>
                                @endforeach
                            </div>
                            @endif
                            <div class="rf-forms">
                                <form method="post" action="{{ route('admin.refunds.attach', $r) }}">@csrf<input name="booking_id" placeholder="Booking #, FN folio or RES" value="{{ $r->booking_id }}"><button class="btn btn-secondary rf-sm" type="submit">Link</button></form>
                                <form method="post" action="{{ route('admin.refunds.split', $r) }}" title="Guest stayed in another unit too: creates a second request with the same bank details">@csrf<input name="unit_no" placeholder="Other unit, e.g. 01-30" maxlength="40" required><button class="btn btn-secondary rf-sm" type="submit">Split</button></form>
                            </div>
                        @endif
                        <div class="rf-muted" style="margin-top:8px">{{ $r->email }}</div>
                        <div class="rf-rate"><span>Clean</span>{!! $gold($r->rating_clean) !!}<span>Service</span>{!! $gold($r->rating_service) !!}@if($r->staff)<span>{{ $r->staff->name }}</span>{!! $gold($r->staff_rating) !!}@endif</div>
                        @if($r->comments)<div class="rf-muted">“{{ $r->comments }}”</div>@endif
                    </div>
                    <div class="rf-pane">
                        <h4>Deposit &amp; deduction</h4>
                        <div>Deposit <b>RM {{ number_format($r->deposit_amount ?? 0, 2) }}</b>@if($r->deduction_amount > 0) · <span class="rf-deduct">deduct RM {{ number_format($r->deduction_amount, 2) }}</span>@endif @if($due !== null) · refund <b class="rf-due">RM {{ number_format($due, 2) }}</b>@endif</div>
                        @if($r->deduction_at)<div class="rf-muted">{{ $r->deduction_amount > 0 ? $r->deduction_reason : 'Checked, nothing to deduct' }} · {{ $r->deductor->name ?? '' }}, {{ $r->deduction_at->format('d M H:i') }}</div>
                        @elseif($pending)<div class="rf-muted">{{ $opsOpen ? 'Operations can record a deduction until ' . $r->ops_cutoff_at?->format('D d M, g:ia') : 'Cut-off passed, nothing recorded: full deposit.' }}</div>@endif
                        @if($pending && (($canDeduct && $opsOpen) || $canManage))
                        <form method="post" action="{{ route('admin.refunds.deduct', $r) }}" class="rf-ded-form">@csrf
                            <input name="deposit_amount" type="number" step="0.01" min="0" placeholder="Deposit RM" value="{{ $r->deposit_amount }}">
                            <input name="deduction_amount" type="number" step="0.01" min="0" placeholder="Deduct RM (0 = none)" value="{{ $r->deduction_amount > 0 ? $r->deduction_amount : '' }}">
                            <input name="deduction_reason" placeholder="Reason, needed if deducting" maxlength="255" value="{{ $r->deduction_reason }}">
                            <button class="btn btn-secondary rf-sm" type="submit">Save</button>
                        </form>
                        @endif
                        @if($pending)
                        <div class="rf-recv" data-id="{{ $r->id }}" style="margin-top:8px">
                            @if($r->received_at)<span class="rf-chip rf-chip--ok">Deposit received ✓</span> <span class="rf-muted">{{ $r->receiver->name ?? '' }}, {{ $r->received_at->format('d M H:i') }}</span>
                            @else<span class="rf-chip rf-chip--warn">Deposit receipt not confirmed</span>@endif
                            @if($canManage)<form method="post" action="{{ route('admin.refunds.received', $r) }}" class="rf-recv-form" style="display:inline">@csrf<button type="submit" class="{{ $r->received_at ? 'rf-more' : 'btn btn-primary rf-sm rf-recv-btn' }}">{{ $r->received_at ? 'undo' : '✓ Confirm deposit received' }}</button></form>@endif
                        </div>
                        @endif
                    </div>
                    <div class="rf-pane">
                        <h4>Bank &amp; payment</h4>
                        @if($canManage)
                            <div class="rf-bank"><span>{{ $r->bank_account }}</span><button type="button" data-copy="{{ $r->bank_account }}" onclick="navigator.clipboard.writeText(this.dataset.copy).then(()=>{this.textContent='✓';setTimeout(()=>this.textContent='copy',1200)})">copy</button></div>
                            <div>{{ $r->bank_holder }} · {{ $r->bank_name }}</div>
                        @else<div class="rf-bank">{{ $r->maskedAccount() }}</div><div>{{ $r->bank_name }}</div>@endif
                        @if($r->status === 'paid')<div style="margin-top:6px">Paid <b>RM {{ number_format($r->amount_paid ?? 0, 2) }}</b> on {{ $r->paid_at?->format('d M H:i') }} by {{ $r->payer->name ?? '' }}{{ $r->reference ? ' · ref ' . $r->reference : '' }}</div>@endif
                        @if($canManage && $pending)
                        <form method="post" action="{{ route('admin.refunds.paid', $r) }}" class="rf-paid-form rf-forms" data-received="{{ $r->received_at ? 1 : 0 }}" style="margin-top:8px">@csrf<input name="amount_paid" type="number" step="0.01" min="0" placeholder="RM" value="{{ $due !== null ? number_format($due, 2, '.', '') : '' }}" style="width:90px"><input name="reference" placeholder="Bank ref" maxlength="120"><button class="btn btn-primary rf-sm" type="submit">Mark paid</button></form>
                        <form method="post" action="{{ route('admin.refunds.reject', $r) }}" class="rf-forms" onsubmit="return confirm('Reject request #{{ $r->id }}? The record is kept.')">@csrf<input name="note" placeholder="Reject with reason" required maxlength="255"><button class="btn btn-secondary rf-sm" type="submit">Reject</button></form>
                        @elseif($canManage)
                        <form method="post" action="{{ route('admin.refunds.reopen', $r) }}" style="margin-top:8px">@csrf<button class="btn btn-secondary rf-sm" type="submit">Reopen</button></form>
                        @endif
                    </div>
                </div>
            </td></tr>
        @empty
            <tr><td colspan="9" class="rf-empty">Nothing here{{ $q !== '' ? ' for “' . $q . '”' : '' }}{{ $prop !== '' ? ' at ' . $prop : '' }}.</td></tr>
        @endforelse
        </tbody>
    </table>
    @if($rows instanceof \Illuminate\Contracts\Pagination\Paginator)<div style="padding:10px 12px">{{ $rows->links() }}</div>@endif
</div></div>
@endif
@endsection

@push('scripts')
<script>
(function(){
    var flash = function(msg){ var el = document.createElement('div'); el.className = 'rf-flash'; el.textContent = msg; document.body.appendChild(el); setTimeout(function(){ el.remove(); }, 3000); };
    var setCount = function(sel, n){ document.querySelectorAll(sel).forEach(function(e){ e.textContent = n; }); };
    var applyCounts = function(c){ if (!c) return; setCount('.rf-tile--main b, .rf-tabs a[href="?tab=pending"] i', c.pending); setCount('.rf-tile--warn b, .rf-tabs a[href="?tab=verify"] i', c.verify); setCount('.rf-tile--bad b', c.overdue); setCount('.rf-tabs a[href="?tab=paid"] i', c.paid); };
    var rowOf = function(id){ return document.querySelector('tr.rf-row[data-id="' + id + '"]'); };
    var vanish = function(id){ var tr = rowOf(id), d = document.querySelector('tr.rf-detail[data-for="' + id + '"]'); [tr, d].forEach(function(x){ if (x) { x.classList.add('rf-gone'); setTimeout(function(){ x.remove(); }, 450); } }); };
    var stepOn = function(id, name, title){ var tr = rowOf(id); if (!tr) return; tr.querySelectorAll('.rf-step').forEach(function(s){ if (s.textContent.trim() === name) { s.classList.add('on'); s.classList.remove('off'); if (title) s.title = title; } }); };
    var post = function(form, extra){ var fd = new FormData(form); if (extra) Object.keys(extra).forEach(function(k){ fd.set(k, extra[k]); }); var btn = form.querySelector('button[type=submit]'); if (btn) { btn.disabled = true; }
        return fetch(form.getAttribute('action'), { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' }).then(function(r){ return r.json(); }).finally(function(){ if (btn) btn.disabled = false; }); };
    // Expand / collapse details
    document.querySelectorAll('.rf-open').forEach(function(b){ b.addEventListener('click', function(){ var tr = b.closest('tr'), d = document.querySelector('tr.rf-detail[data-for="' + tr.dataset.id + '"]'); d.hidden = !d.hidden; b.textContent = d.hidden ? '▸' : '▾'; b.setAttribute('aria-expanded', d.hidden ? 'false' : 'true'); tr.classList.toggle('open', !d.hidden); }); });
    document.querySelectorAll('tr.rf-row').forEach(function(tr){ tr.addEventListener('click', function(e){ if (e.target.closest('a, button, input, form, label')) return; tr.querySelector('.rf-open').click(); }); });
    // Open verify rows by default on the Verify tab
    if (location.search.indexOf('tab=verify') !== -1) document.querySelectorAll('.rf-open').forEach(function(b){ b.click(); });
    // Received toggle in place
    document.querySelectorAll('form.rf-recv-form').forEach(function(form){
        form.addEventListener('submit', function(e){ e.preventDefault();
            post(form).then(function(d){ if (d && d.ok) { var box = form.closest('.rf-recv'); box.innerHTML = (d.received ? '<span class="rf-chip rf-chip--ok">Deposit received ✓</span> <span class="rf-muted">' + d.by + ', ' + d.at + '</span>' : '<span class="rf-chip rf-chip--warn">Deposit receipt not confirmed</span>') + ' '; box.appendChild(form); var rb = form.querySelector('button'); rb.textContent = d.received ? 'undo' : '✓ Confirm deposit received'; rb.className = d.received ? 'rf-more' : 'btn btn-primary rf-sm rf-recv-btn'; var pf = document.querySelector('tr.rf-detail[data-for="' + d.id + '"] form.rf-paid-form'); if (pf) pf.dataset.received = d.received ? '1' : '0'; var s = rowOf(d.id).querySelectorAll('.rf-step')[1]; if (s) s.classList.toggle('on', d.received); flash(d.message); } else { form.submit(); } }).catch(function(){ form.submit(); });
        });
    });
    // Single Mark paid in place
    document.querySelectorAll('form.rf-paid-form').forEach(function(form){
        form.addEventListener('submit', function(e){ e.preventDefault();
            if (form.dataset.received !== '1' && !confirm('Deposit receipt is not confirmed for this request. Pay anyway?')) return;
            post(form).then(function(d){ if (d && d.ok) { flash(d.message); if (location.search.indexOf('tab=paid') === -1) vanish(d.id); else stepOn(d.id, 'Paid'); applyCounts(d.counts); } else { form.submit(); } }).catch(function(){ form.submit(); });
        });
    });
    // Bulk bar
    var all = document.getElementById('rf-all'), bulk = document.getElementById('rf-bulk'), n = document.getElementById('rf-bulk-n');
    var picks = function(){ return Array.prototype.slice.call(document.querySelectorAll('.rf-pick:checked')); };
    var refresh = function(){ var k = picks().length; if (bulk) { bulk.hidden = k === 0; n.textContent = k; } };
    document.querySelectorAll('.rf-pick').forEach(function(cb){ cb.addEventListener('change', refresh); });
    if (all) all.addEventListener('change', function(){ document.querySelectorAll('.rf-pick').forEach(function(cb){ cb.checked = all.checked; }); refresh(); });
    document.querySelectorAll('.rf-qbtn').forEach(function(b){ b.addEventListener('click', function(){ var k = b.dataset.select; document.querySelectorAll('tr.rf-row').forEach(function(tr){ var cb = tr.querySelector('.rf-pick'); if (cb) cb.checked = tr.dataset[k] === '1'; }); refresh(); if (!picks().length) flash('No request matches "' + b.textContent + '" on this page.'); }); });
    var clear = document.getElementById('rf-bulk-clear'); if (clear) clear.addEventListener('click', function(){ document.querySelectorAll('.rf-pick').forEach(function(cb){ cb.checked = false; }); if (all) all.checked = false; refresh(); });
    if (bulk) bulk.querySelectorAll('button[data-action]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var ids = picks().map(function(cb){ return cb.value; }); if (!ids.length) return;
            var labels = { paid: 'Mark ' + ids.length + ' request(s) as paid? Only rows with deposit receipt confirmed will be paid.', received: 'Confirm deposit received for ' + ids.length + ' request(s)?', nodeduct: 'Record "checked, nothing to deduct" on ' + ids.length + ' request(s)?' };
            if (!confirm(labels[btn.dataset.action])) return;
            bulk.querySelectorAll('input[name="ids[]"]').forEach(function(i){ i.remove(); });
            ids.forEach(function(id){ var i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; bulk.appendChild(i); });
            document.getElementById('rf-bulk-action').value = btn.dataset.action;
            btn.disabled = true;
            post(bulk).then(function(d){ btn.disabled = false; if (d && d.ok) { flash(d.message); picks().forEach(function(cb){ cb.checked = false; }); if (all) all.checked = false; refresh();
                if (d.action === 'paid') { d.ids.forEach(vanish); } else { d.ids.forEach(function(id){ stepOn(id, d.action === 'received' ? 'Rcvd' : 'Ops', (d.action === 'received' ? 'Deposit receipt confirmed by ' : 'Operations checked, nothing to deduct, ') + d.by + ' ' + d.at); var pf = document.querySelector('tr.rf-detail[data-for="' + id + '"] form.rf-paid-form'); if (pf && d.action === 'received') pf.dataset.received = '1'; }); }
                applyCounts(d.counts); } else { flash((d && d.message) || 'Something went wrong, reload the page.'); } }).catch(function(){ btn.disabled = false; flash('Network error, reload the page.'); });
        });
    });
})();
</script>
@endpush
