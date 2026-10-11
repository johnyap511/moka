@extends('admin.layout')
@section('title', 'Deposit refunds')
@section('page-title', 'Deposit refunds')

@push('styles')
<style>
.rf-head{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;margin-bottom:12px}.rf-head h1{margin:0 0 2px;font-size:20px}.rf-sub{margin:0;font-size:12.5px;color:var(--text-secondary);max-width:720px;line-height:1.45}
.rf-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:12px}@media (max-width:900px){.rf-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
.rf-tile{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:10px 12px;display:grid;gap:3px;text-decoration:none;color:inherit}.rf-tile span{font-size:11.5px;color:var(--text-secondary)}.rf-tile b{font-size:20px;line-height:1.15}
.rf-tile--main{background:#0f766e;border-color:#0f766e;color:#fff}.rf-tile--main span{color:rgba(255,255,255,.85)}.rf-tile--warn b{color:#b45309}.rf-tile--bad b{color:#b91c1c}
.rf-link{background:#f0fdfa;border:1px solid #99f6e4;border-radius:10px;padding:10px 12px;margin-bottom:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;font-size:12.5px}.rf-link code{background:#fff;border:1px solid #99f6e4;border-radius:6px;padding:3px 8px;font-size:12px}
.rf-tabs{display:flex;gap:4px;border-bottom:1px solid var(--border,#e5e7eb);margin-bottom:10px;flex-wrap:wrap;align-items:center}.rf-tabs a{padding:8px 12px;font-size:13px;color:var(--text-secondary);text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-1px}.rf-tabs a.on{color:#0f766e;border-color:#0f766e;font-weight:600}.rf-tabs form{margin-left:auto;display:flex;gap:6px}.rf-tabs input{padding:5px 8px;font-size:12.5px;border:1px solid var(--border,#e5e7eb);border-radius:8px}
.rf-table{width:100%;border-collapse:collapse;font-size:12.5px;line-height:1.35}.rf-table th,.rf-table td{padding:8px;border-bottom:1px solid var(--border,#e5e7eb);text-align:left;vertical-align:top}.rf-table th{font-size:11px;text-transform:uppercase;letter-spacing:.03em;color:var(--text-secondary);white-space:nowrap}
.rf-table tr.overdue td:first-child{box-shadow:inset 3px 0 #dc2626}.rf-table tr.verify td:first-child{box-shadow:inset 3px 0 #f59e0b}
.rf-muted{font-size:11.5px;color:var(--text-secondary)}.rf-bank{font-family:ui-monospace,Menlo,monospace;font-size:12px}
.rf-chip{display:inline-block;font-size:11px;padding:1px 8px;border-radius:999px;font-weight:600;white-space:nowrap}.rf-chip--ok{background:#dcfce7;color:#166534}.rf-chip--warn{background:#fef3c7;color:#92400e}.rf-chip--dup{background:#fee2e2;color:#991b1b}.rf-chip--paid{background:#dbeafe;color:#1d4ed8}.rf-chip--rej{background:#f1f5f9;color:#475569}
.rf-stars{color:#f59e0b;letter-spacing:1px}.rf-note{font-size:11.5px;color:#92400e;margin-top:3px}
.rf-act{display:grid;gap:5px;min-width:210px}.rf-act form{display:flex;gap:4px}.rf-act input{flex:1;min-width:0;padding:4px 6px;font-size:12px;border:1px solid var(--border,#e5e7eb);border-radius:6px}.rf-act button{padding:4px 9px;font-size:12px;white-space:nowrap}
.rf-empty{color:var(--text-secondary);padding:18px!important;text-align:center}
.rf-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;align-items:start}@media (max-width:900px){.rf-grid{grid-template-columns:1fr}}
.rf-h3{margin:0 0 8px;font-size:14px}.rf-quote{border-left:3px solid #99f6e4;padding:4px 10px;margin:6px 0;font-size:12.5px}.rf-quote small{display:block;color:var(--text-secondary)}
.rf-copy{padding:4px 10px;font-size:12px}
</style>
@endpush

@section('content')
@php
    $stars = fn ($n) => $n ? str_repeat('★', (int) $n) . str_repeat('☆', 5 - (int) $n) : '—';
    $matchChip = ['matched' => ['Matched', 'rf-chip--ok'], 'verify' => ['Verify', 'rf-chip--warn'], 'duplicate' => ['Duplicate', 'rf-chip--dup']];
@endphp

<div class="rf-head">
    <div>
        <h1>Deposit refunds</h1>
        <p class="rf-sub">Guests fill in the refund form from the link in the check-out message. Each request is matched to its booking; Finance checks the deposit and marks it paid within 3 working days. Bank details are encrypted and shown only to Finance. Records are never deleted.</p>
    </div>
</div>

@if(admin_can('refunds.link'))
<div class="rf-link">
    <b>Current refund link</b>
    @if($link)
        <code id="rf-url">{{ $link->url() }}</code>
        <button type="button" class="btn btn-secondary rf-copy" onclick="navigator.clipboard.writeText(document.getElementById('rf-url').textContent).then(()=>{this.textContent='Copied';setTimeout(()=>this.textContent='Copy link',1500)})">Copy link</button>
        <span class="rf-muted">valid until {{ $link->expires_at->format('D d M, H:i') }}</span>
    @else
        <span class="rf-muted">none valid right now</span>
    @endif
    <form method="post" action="{{ route('admin.refunds.link') }}" style="margin-left:auto">@csrf<button class="btn btn-secondary rf-copy" type="submit">New link (48 h)</button></form>
</div>
@endif

<div class="rf-tiles">
    <a class="rf-tile rf-tile--main" href="?tab=pending"><span>Pending</span><b>{{ $counts['pending'] }}</b></a>
    <a class="rf-tile rf-tile--warn" href="?tab=pending"><span>Need verifying</span><b>{{ $counts['verify'] }}</b></a>
    <a class="rf-tile rf-tile--bad" href="?tab=pending"><span>Overdue (past 3 working days)</span><b>{{ $counts['overdue'] }}</b></a>
    <a class="rf-tile" href="?tab=paid"><span>Paid</span><b>{{ $counts['paid'] }}</b></a>
</div>

<div class="rf-tabs">
    @if(admin_can('refunds.view'))
    <a href="?tab=pending" class="{{ $tab === 'pending' ? 'on' : '' }}">Pending</a>
    <a href="?tab=paid" class="{{ $tab === 'paid' ? 'on' : '' }}">Paid</a>
    <a href="?tab=rejected" class="{{ $tab === 'rejected' ? 'on' : '' }}">Rejected</a>
    <a href="?tab=all" class="{{ $tab === 'all' ? 'on' : '' }}">All</a>
    @endif
    @if(admin_can('refunds.feedback'))
    <a href="?tab=feedback" class="{{ $tab === 'feedback' ? 'on' : '' }}">Guest feedback</a>
    @endif
    @if($tab !== 'feedback')
    <form method="get"><input type="hidden" name="tab" value="{{ $tab }}"><input type="search" name="q" value="{{ $q }}" placeholder="Name, phone, unit, booking #"><button class="btn btn-secondary rf-copy" type="submit">Search</button></form>
    @else
    <form method="get"><input type="hidden" name="tab" value="feedback"><input type="month" name="month" value="{{ $ym }}"><button class="btn btn-secondary rf-copy" type="submit">Show</button></form>
    @endif
</div>

@if($tab === 'feedback' && $feedback)
    <div class="rf-tiles">
        <div class="rf-tile"><span>{{ $feedback['month'] }} responses</span><b>{{ $feedback['total'] }}</b></div>
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
                    <tr><td><b>{{ trim(($s['user']->name ?? '?') . ' ' . ($s['user']->last_name ?? '')) }}</b></td><td>{{ $s['count'] }}</td><td>@if($s['avg'] !== null){{ $s['avg'] }} <span class="rf-stars">{{ $stars(round($s['avg'])) }}</span>@else — @endif</td><td>{{ $s['fives'] }}</td><td>{{ $s['low'] }}</td></tr>
                @empty
                    <tr><td colspan="5" class="rf-empty">No team member named this month yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            @foreach($feedback['staff'] as $s)
                @foreach($s['comments'] as $c)
                    <div class="rf-quote">“{{ $c->staff_comment }}”<small>{{ trim(($s['user']->name ?? '') . ' ' . ($s['user']->last_name ?? '')) }} · {{ $stars($c->staff_rating) }} · {{ $c->name }}, {{ $c->property }} {{ $c->unit_no }}, {{ $c->created_at->format('d M') }}</small></div>
                @endforeach
            @endforeach
        </div></div>
        <div class="card"><div class="card-body">
            <h3 class="rf-h3">By property</h3>
            <table class="rf-table">
                <thead><tr><th>Property</th><th>Responses</th><th>Cleanliness</th><th>Service</th></tr></thead>
                <tbody>
                @forelse($feedback['properties'] as $p => $v)
                    <tr><td>{{ $p }}</td><td>{{ $v['count'] }}</td><td>{{ $v['clean'] ?? '—' }}</td><td>{{ $v['service'] ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="4" class="rf-empty">No responses this month yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            <h3 class="rf-h3" style="margin-top:14px">Guest comments</h3>
            @forelse($feedback['comments'] as $c)
                <div class="rf-quote">“{{ $c->comments }}”<small>{{ $c->name }} · {{ $c->property }} {{ $c->unit_no }} · clean {{ $c->rating_clean }}/5, service {{ $c->rating_service }}/5 · {{ $c->created_at->format('d M') }}</small></div>
            @empty
                <p class="rf-muted">No comments this month yet.</p>
            @endforelse
        </div></div>
    </div>
@else
<div class="card"><div class="card-body" style="overflow:auto">
    <table class="rf-table">
        <thead><tr>
            <th>#</th><th>Submitted</th><th>Guest</th><th>Stay</th><th>Booking</th>
            <th>{{ $canManage ? 'Refund to' : 'Bank' }}</th><th>Ratings</th><th>Due</th>
            @if($canManage)<th>Action</th>@endif
        </tr></thead>
        <tbody>
        @forelse($rows as $r)
            @php [$mLabel, $mClass] = $matchChip[$r->match] ?? [$r->match, 'rf-chip--warn']; $b = $r->booking; @endphp
            <tr class="{{ $r->isOverdue() ? 'overdue' : ($r->status === 'pending' && $r->match !== 'matched' ? 'verify' : '') }}">
                <td>{{ $r->id }}</td>
                <td>{{ $r->created_at->format('d M') }}<div class="rf-muted">{{ $r->created_at->format('H:i') }}</div></td>
                <td><b>{{ $r->name }}</b><div class="rf-muted">{{ $r->phone }}</div><div class="rf-muted">{{ $r->email }}</div></td>
                <td>{{ $r->property }}<div><b>{{ $r->unit_no }}</b> · out {{ $r->check_out->format('d M Y') }}</div></td>
                <td>
                    @if($r->status === 'paid')<span class="rf-chip rf-chip--paid">Paid {{ $r->paid_at?->format('d M') }}</span> <div class="rf-muted">{{ $r->payer->name ?? '' }}{{ $r->reference ? ' · ' . $r->reference : '' }}</div>
                    @elseif($r->status === 'rejected')<span class="rf-chip rf-chip--rej">Rejected</span><div class="rf-note">{{ $r->note }}</div>
                    @else<span class="rf-chip {{ $mClass }}">{{ $mLabel }}</span>@endif
                    @if($b)
                        <div><a href="/admin/book/{{ $b->id }}/edit" target="_blank">#{{ $b->id }}</a> {{ $b->listing->name ?? '' }}<div class="rf-muted">{{ $b->user->name ?? '' }} {{ $b->user->last_name ?? '' }} · {{ \Carbon\Carbon::parse($b->check_in)->format('d M') }}–{{ \Carbon\Carbon::parse($b->check_out)->format('d M') }} · {{ $b->source }}</div></div>
                    @endif
                    @if($r->match_note && $r->status !== 'rejected')<div class="rf-note">{{ $r->match_note }}</div>@endif
                </td>
                <td>{{ $r->bank_name }}
                    @if($canManage)<div class="rf-bank">{{ $r->bank_account }}</div><div class="rf-muted">{{ $r->bank_holder }}</div>
                    @else<div class="rf-bank">{{ $r->maskedAccount() }}</div>@endif
                </td>
                <td><span class="rf-stars" title="Cleanliness">{{ $stars($r->rating_clean) }}</span><div class="rf-stars" title="Service">{{ $stars($r->rating_service) }}</div>
                    @if($r->staff)<div class="rf-muted">{{ $r->staff->name }}: {{ $stars($r->staff_rating) }}</div>@endif
                    @if($r->comments)<div class="rf-muted" title="{{ $r->comments }}">“{{ \Illuminate\Support\Str::limit($r->comments, 60) }}”</div>@endif
                </td>
                <td>@if($r->status === 'pending'){{ $r->due_on?->format('d M') }}@if($r->isOverdue())<div class="rf-note" style="color:#b91c1c">overdue</div>@endif @else — @endif</td>
                @if($canManage)
                <td class="rf-act">
                    @if($r->status === 'pending')
                        <form method="post" action="{{ route('admin.refunds.paid', $r) }}">@csrf<input name="reference" placeholder="Bank ref (optional)"><button class="btn btn-primary" type="submit">Paid</button></form>
                        <form method="post" action="{{ route('admin.refunds.attach', $r) }}">@csrf<input name="booking_id" placeholder="Booking #" value="{{ $r->booking_id }}"><button class="btn btn-secondary" type="submit">Link</button></form>
                        <form method="post" action="{{ route('admin.refunds.reject', $r) }}" onsubmit="return confirm('Reject request #{{ $r->id }}? The record is kept.')">@csrf<input name="note" placeholder="Reason" required><button class="btn btn-secondary" type="submit">Reject</button></form>
                    @else
                        <form method="post" action="{{ route('admin.refunds.reopen', $r) }}">@csrf<button class="btn btn-secondary" type="submit">Reopen</button></form>
                    @endif
                </td>
                @endif
            </tr>
        @empty
            <tr><td colspan="{{ $canManage ? 9 : 8 }}" class="rf-empty">Nothing here{{ $q !== '' ? ' for “' . $q . '”' : '' }}.</td></tr>
        @endforelse
        </tbody>
    </table>
    @if($rows instanceof \Illuminate\Contracts\Pagination\Paginator)<div style="margin-top:10px">{{ $rows->links() }}</div>@endif
</div></div>
@endif
@endsection
