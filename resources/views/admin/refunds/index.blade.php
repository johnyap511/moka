@extends('admin.layout')
@section('title', 'Deposit refunds')
@section('page-title', 'Deposit refunds')

@push('styles')
<style>
.rf-head{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;margin-bottom:12px}.rf-head h1{margin:0 0 2px;font-size:20px}.rf-sub{margin:0;font-size:12.5px;color:var(--text-secondary);max-width:760px;line-height:1.45}
.rf-tiles{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:12px}@media (max-width:900px){.rf-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
.rf-tile{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:10px 12px;display:grid;gap:3px;text-decoration:none;color:inherit;transition:box-shadow .12s}.rf-tile:hover{box-shadow:0 2px 8px rgba(0,0,0,.08)}.rf-tile span{font-size:11.5px;color:var(--text-secondary)}.rf-tile b{font-size:20px;line-height:1.15}
.rf-tile--main{background:#0f766e;border-color:#0f766e;color:#fff}.rf-tile--main span{color:rgba(255,255,255,.85)}.rf-tile--warn b{color:#b45309}.rf-tile--bad b{color:#b91c1c}.rf-tile.on{outline:2px solid #0f766e;outline-offset:1px}
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
.rf-grp{display:inline-block;margin-top:3px;font-size:10.5px;font-weight:700;color:#0f766e;background:#ccfbf1;border-radius:999px;padding:0 6px;white-space:nowrap}
.rf-tick{width:28px;text-align:center}.rf-tick input{width:16px;height:16px;cursor:pointer;accent-color:#0f766e}
.rf-bulk[hidden]{display:none}.rf-bulk{display:flex;gap:8px;align-items:center;flex-wrap:wrap;background:#0f766e;color:#fff;border-radius:10px;padding:8px 12px;margin-bottom:10px;font-size:13px;position:sticky;top:8px;z-index:5}.rf-bulk input{padding:5px 8px;font-size:12.5px;border:1px solid rgba(255,255,255,.4);border-radius:8px;min-width:200px}.rf-bulk .rf-muted{color:rgba(255,255,255,.8)}
.rf-table tr.rf-gone{opacity:.35;transition:opacity .4s}.rf-flash{position:fixed;right:16px;bottom:16px;background:#0f766e;color:#fff;padding:10px 14px;border-radius:10px;font-size:13px;box-shadow:0 6px 20px rgba(0,0,0,.2);z-index:50}
.rf-sugg{border:1px dashed #99f6e4;border-radius:8px;padding:6px 8px;background:#f0fdfa;display:grid;gap:4px}.rf-sugg-h{font-size:10.5px;text-transform:uppercase;letter-spacing:.04em;color:#0f766e;font-weight:700}.rf-sugg-row{display:flex;gap:6px;align-items:center;justify-content:space-between}.rf-sugg-t{font-size:12px;line-height:1.3;white-space:normal;min-width:0}.rf-sugg-t .rf-muted{white-space:normal}
.rf-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;align-items:start}@media (max-width:900px){.rf-grid{grid-template-columns:1fr}}
.rf-h3{margin:0 0 8px;font-size:14px;display:flex;align-items:center;gap:8px}.rf-quote{border-left:3px solid #99f6e4;padding:4px 10px;margin:6px 0;font-size:12.5px;line-height:1.4}.rf-quote small{display:block;color:var(--text-secondary)}.rf-quote.unv{border-color:#fde68a}
.rf-sm{padding:4px 10px;font-size:12px}
.rf-mnav{display:flex;gap:6px;align-items:center}.rf-mnav a{text-decoration:none;font-size:14px;color:#0f766e;padding:0 6px}
.rf-kpi-bar{height:6px;background:#f1f5f9;border-radius:999px;overflow:hidden;width:70px;display:inline-block;vertical-align:middle;margin-left:6px}.rf-kpi-bar i{display:block;height:100%;background:#0f766e}
</style>
@endpush

@section('content')
@php
    $stars = fn ($n) => $n ? str_repeat('★', (int) $n) . str_repeat('☆', 5 - (int) $n) : '—';
    $matchChip = ['matched' => ['Matched', 'rf-chip--ok'], 'verify' => ['Verify', 'rf-chip--warn'], 'duplicate' => ['Duplicate', 'rf-chip--dup']];
    $qs = fn (array $extra) => '?' . http_build_query(array_filter(['tab' => $tab, 'q' => $q, 'property' => $prop, 'from' => $from, 'to' => $to] + $extra, fn ($v) => $v !== '' && $v !== null));
@endphp

<div class="rf-head">
    <div>
        <h1>Deposit refunds</h1>
        <p class="rf-sub">Guests fill in the refund form from the link in the check-out message. Each request is matched to its stay; Finance checks the deposit in eZee and marks it paid within 3 working days. Bank details are encrypted and shown only to Finance. Nothing is ever deleted.</p>
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

<div class="rf-tiles">
    <a class="rf-tile rf-tile--main {{ $tab === 'pending' ? 'on' : '' }}" href="?tab=pending"><span>Pending</span><b>{{ $counts['pending'] }}</b></a>
    <a class="rf-tile rf-tile--warn {{ $tab === 'verify' ? 'on' : '' }}" href="?tab=verify"><span>Need verifying</span><b>{{ $counts['verify'] }}</b></a>
    <a class="rf-tile rf-tile--bad" href="?tab=pending"><span>Overdue (past 3 working days)</span><b>{{ $counts['overdue'] }}</b></a>
    <a class="rf-tile" href="?tab=all"><span>Received today</span><b>{{ $counts['today'] }}</b></a>
    <a class="rf-tile {{ $tab === 'paid' ? 'on' : '' }}" href="?tab=paid"><span>Paid</span><b>{{ $counts['paid'] }}</b></a>
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
        @if($canManage)<a class="btn btn-secondary rf-sm" href="{{ route('admin.refunds.export') }}{{ $qs([]) }}" title="CSV of this list with bank details{{ $from || $to ? ', ' . ($tab === 'paid' ? 'paid' : 'submitted') . ' ' . ($from ?: '…') . ' to ' . ($to ?: '…') : '' }}">Export CSV{{ $from || $to ? ' (range)' : '' }}</a>@endif
        <a class="btn btn-secondary rf-sm" href="{{ route('admin.refunds.contacts') }}" title="Guests who ticked the offers box: name, email, phone. No bank details.">Contacts CSV</a>
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
@if($canManage && $tab !== 'paid' && $tab !== 'rejected')
<form method="post" action="{{ route('admin.refunds.bulk-paid') }}" id="rf-bulk" class="rf-bulk" hidden>@csrf
    <b><span id="rf-bulk-n">0</span> selected</b>
    <input name="reference" placeholder="Bank ref for all (optional)" maxlength="120">
    <button class="btn btn-primary rf-sm" type="submit">Mark selected as paid</button>
    <button class="btn btn-secondary rf-sm" type="button" id="rf-bulk-clear">Clear</button>
    <span class="rf-muted">Tick the boxes on the left. Only pending requests can be ticked.</span>
</form>
@endif
<div class="card"><div class="card-body" style="overflow:auto">
    <table class="rf-table">
        <thead><tr>
            @if($canManage && $tab !== 'paid' && $tab !== 'rejected')<th class="rf-tick"><input type="checkbox" id="rf-all" title="Select all pending on this page" aria-label="Select all"></th>@endif
            <th>#</th><th>Guest</th><th>Stay &amp; booking</th>
            <th>{{ $canManage ? 'Refund to' : 'Bank' }}</th><th>Ratings</th><th>Due</th>
            @if($canManage)<th>Action</th>@endif
        </tr></thead>
        <tbody>
        @forelse($rows as $r)
            @php
                [$mLabel, $mClass] = $matchChip[$r->match] ?? [$r->match, 'rf-chip--warn'];
                $b = $r->booking;
                $rowClass = $r->isOverdue() ? 'overdue' : ($r->status === 'pending' ? ($r->match === 'duplicate' ? 'dup' : ($r->match !== 'matched' ? 'verify' : '')) : '');
                $daysLeft = $r->due_on ? today()->diffInDays($r->due_on, false) : null;
            @endphp
            <tr class="{{ $rowClass }}" data-id="{{ $r->id }}">
                @if($canManage && $tab !== 'paid' && $tab !== 'rejected')<td class="rf-tick">@if($r->status === 'pending')<input type="checkbox" class="rf-pick" value="{{ $r->id }}" aria-label="Select request {{ $r->id }}">@endif</td>@endif
                <td>{{ $r->id }}@if($r->group_key)<div class="rf-grp" title="One guest, several units: paid together, one deposit each">{{ $r->group_index }} of {{ $r->group_size }}</div>@endif</td>
                <td class="rf-wrap"><b>{{ $r->name }}</b><div class="rf-muted">{{ $r->phone }}</div><div class="rf-muted rf-ell" title="{{ $r->email }}">{{ $r->email }}</div><div class="rf-muted">{{ $r->created_at->format('d M, H:i') }}</div></td>
                <td class="rf-wrap">
                    <div>{{ \Illuminate\Support\Str::before($r->property, ' by') }} <b>{{ $r->unit_no }}</b> <span class="rf-muted">· out {{ $r->check_out->format('d M') }}</span></div>
                    @if($r->status === 'paid')<span class="rf-chip rf-chip--paid">Paid {{ $r->paid_at?->format('d M') }}</span> <div class="rf-muted">{{ $r->payer->name ?? '' }}{{ $r->reference ? ' · ' . $r->reference : '' }}</div>
                    @elseif($r->status === 'rejected')<span class="rf-chip rf-chip--rej">Rejected</span><div class="rf-note">{{ $r->note }}</div>
                    @else<span class="rf-chip {{ $mClass }}">{{ $mLabel }}</span>@endif
                    @if($b)
                        <div class="rf-muted"><a href="/admin/book/{{ $b->id }}/edit" target="_blank" rel="noopener">#{{ $b->id }}</a> {{ $b->listing->name ?? '' }} · {{ \Illuminate\Support\Str::limit(trim(($b->user->name ?? '') . ' ' . ($b->user->last_name ?? '')), 24) }} · {{ \Carbon\Carbon::parse($b->check_in)->format('d M') }}–{{ \Carbon\Carbon::parse($b->check_out)->format('d M') }} · {{ $b->source }}</div>
                    @endif
                    @if($r->match_note && $r->status !== 'rejected')<div class="rf-note">{{ $r->match_note }}</div>@endif
                </td>
                <td class="rf-wrap">
                    @if($canManage)
                        <div class="rf-bank"><span>{{ $r->bank_account }}</span><button type="button" data-copy="{{ $r->bank_account }}" onclick="navigator.clipboard.writeText(this.dataset.copy).then(()=>{this.textContent='✓';setTimeout(()=>this.textContent='copy',1200)})">copy</button></div>
                        <div class="rf-muted">{{ $r->bank_holder }}</div><div class="rf-muted">{{ $r->bank_name }}</div>
                    @else<div class="rf-bank">{{ $r->maskedAccount() }}</div><div class="rf-muted">{{ $r->bank_name }}</div>@endif
                </td>
                <td><div class="rf-stars" title="Cleanliness {{ $r->rating_clean }}/5, service {{ $r->rating_service }}/5">★{{ $r->rating_clean ?? '–' }} <span class="rf-muted">clean</span><br>★{{ $r->rating_service ?? '–' }} <span class="rf-muted">service</span></div>
                    @if($r->staff)<div class="rf-muted" title="{{ $r->staff->name }}">{{ \Illuminate\Support\Str::limit($r->staff->name, 10) }} ★{{ $r->staff_rating ?? '–' }}</div>@endif
                    @if($r->comments)<div class="rf-muted rf-ell" title="{{ $r->comments }}">“{{ \Illuminate\Support\Str::limit($r->comments, 30) }}”</div>@endif
                </td>
                <td>@if($r->status === 'pending' && $r->due_on)
                        {{ $r->due_on->format('d M') }}
                        @if($daysLeft < 0)<div class="rf-due-bad">{{ abs($daysLeft) }} day{{ abs($daysLeft) === 1 ? '' : 's' }} overdue</div>
                        @elseif($daysLeft === 0)<div class="rf-due-soon">due today</div>
                        @else<div class="rf-muted">{{ $daysLeft }} day{{ $daysLeft === 1 ? '' : 's' }} left</div>@endif
                    @else — @endif</td>
                @if($canManage)
                <td class="rf-act">
                    @if($r->status === 'pending')
                        <form method="post" action="{{ route('admin.refunds.paid', $r) }}" class="rf-paid-form">@csrf<input name="reference" placeholder="Bank ref" maxlength="120"><button class="btn btn-primary" type="submit">Paid</button></form>
                        <button type="button" class="rf-more" onclick="var d=this.nextElementSibling;d.hidden=!d.hidden;this.textContent=d.hidden?'More ▾':'Less ▴'">More ▾</button>
                        <div class="rf-more-box" {{ $r->match !== 'matched' ? '' : 'hidden' }}>
                        <form method="post" action="{{ route('admin.refunds.attach', $r) }}">@csrf<input name="booking_id" placeholder="Booking #, FN or RES" value="{{ $r->booking_id }}" title="Booking number, eZee folio (FN4525) or reservation (RES4465)"><button class="btn btn-secondary" type="submit">Link</button></form>
                        @if(!empty($r->suggestions))
                        <div class="rf-sugg"><div class="rf-sugg-h">Likely bookings</div>
                            @foreach($r->suggestions as $s)
                            @php $sb = $s['booking']; @endphp
                            <form method="post" action="{{ route('admin.refunds.attach', $r) }}" class="rf-sugg-row">@csrf<input type="hidden" name="booking_id" value="{{ $sb->id }}">
                                <div class="rf-sugg-t"><b>{{ $sb->listing->name ?? '' }}</b> · {{ trim(($sb->user->name ?? '') . ' ' . ($sb->user->last_name ?? '')) ?: '—' }}<div class="rf-muted">{{ \Carbon\Carbon::parse($sb->check_in)->format('d M') }}–{{ \Carbon\Carbon::parse($sb->check_out)->format('d M') }} · {{ $sb->source }} · {{ $sb->folio_no }} · {{ $s['why'] }}</div></div>
                                <button class="btn btn-secondary" type="submit">Pick</button>
                            </form>
                            @endforeach
                        </div>
                        @endif
                        <form method="post" action="{{ route('admin.refunds.reject', $r) }}" onsubmit="return confirm('Reject request #{{ $r->id }}? The record is kept.')">@csrf<input name="note" placeholder="Reason" required maxlength="255"><button class="btn btn-secondary" type="submit">Reject</button></form>
                        <form method="post" action="{{ route('admin.refunds.split', $r) }}" title="Guest stayed in another unit too: creates a second request for it with the same bank details">@csrf<input name="unit_no" placeholder="Other unit" maxlength="40" required><button class="btn btn-secondary" type="submit">Split</button></form>
                        </div>
                    @else
                        <form method="post" action="{{ route('admin.refunds.reopen', $r) }}">@csrf<button class="btn btn-secondary" type="submit">Reopen</button></form>
                    @endif
                </td>
                @endif
            </tr>
        @empty
            <tr><td colspan="{{ $canManage ? 9 : 7 }}" class="rf-empty">Nothing here{{ $q !== '' ? ' for “' . $q . '”' : '' }}{{ $prop !== '' ? ' at ' . $prop : '' }}.</td></tr>
        @endforelse
        </tbody>
    </table>
    @if($rows instanceof \Illuminate\Contracts\Pagination\Paginator)<div style="margin-top:10px">{{ $rows->links() }}</div>@endif
</div></div>
@endif
@endsection

@push('scripts')
<script>
(function(){
    var flash = function(msg){ var el = document.createElement('div'); el.className = 'rf-flash'; el.textContent = msg; document.body.appendChild(el); setTimeout(function(){ el.remove(); }, 2600); };
    var setCount = function(sel, n){ document.querySelectorAll(sel).forEach(function(e){ e.textContent = n; }); };
    var applyCounts = function(c){ if (!c) return; setCount('.rf-tile--main b, .rf-tabs a[href="?tab=pending"] i', c.pending); setCount('.rf-tile--warn b, .rf-tabs a[href="?tab=verify"] i', c.verify); setCount('.rf-tile--bad b', c.overdue); setCount('.rf-tabs a[href="?tab=paid"] i', c.paid); };
    var vanish = function(id){ var tr = document.querySelector('tr[data-id="' + id + '"]'); if (!tr) return; tr.classList.add('rf-gone'); setTimeout(function(){ tr.remove(); }, 450); };
    var post = function(form){ var btn = form.querySelector('button[type=submit]'); if (btn) { btn.disabled = true; btn.textContent = '…'; }
        return fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function(r){ return r.json(); }); };
    // Single Paid: update the row in place, no page reload.
    document.querySelectorAll('form.rf-paid-form').forEach(function(form){
        form.addEventListener('submit', function(e){ e.preventDefault();
            post(form).then(function(d){ if (d && d.ok) { flash(d.message); vanish(d.id); applyCounts(d.counts); } else { form.submit(); } }).catch(function(){ form.submit(); });
        });
    });
    // Bulk: tick boxes, one request for all.
    var all = document.getElementById('rf-all'), bulk = document.getElementById('rf-bulk'), n = document.getElementById('rf-bulk-n');
    var picks = function(){ return Array.prototype.slice.call(document.querySelectorAll('.rf-pick:checked')); };
    var refresh = function(){ var k = picks().length; if (bulk) { bulk.hidden = k === 0; n.textContent = k; } };
    document.querySelectorAll('.rf-pick').forEach(function(cb){ cb.addEventListener('change', refresh); });
    if (all) all.addEventListener('change', function(){ document.querySelectorAll('.rf-pick').forEach(function(cb){ cb.checked = all.checked; }); refresh(); });
    var clear = document.getElementById('rf-bulk-clear'); if (clear) clear.addEventListener('click', function(){ document.querySelectorAll('.rf-pick').forEach(function(cb){ cb.checked = false; }); if (all) all.checked = false; refresh(); });
    if (bulk) bulk.addEventListener('submit', function(e){ e.preventDefault(); var ids = picks().map(function(cb){ return cb.value; }); if (!ids.length) return;
        if (!confirm('Mark ' + ids.length + ' request' + (ids.length === 1 ? '' : 's') + ' as paid?')) return;
        bulk.querySelectorAll('input[name="ids[]"]').forEach(function(i){ i.remove(); });
        ids.forEach(function(id){ var i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; bulk.appendChild(i); });
        post(bulk).then(function(d){ if (d && d.ok) { flash(d.message); picks().forEach(function(cb){ cb.checked = false; }); d.ids.forEach(vanish); applyCounts(d.counts); if (all) all.checked = false; refresh(); var b = bulk.querySelector('button[type=submit]'); b.disabled = false; b.textContent = 'Mark selected as paid'; } else { bulk.submit(); } }).catch(function(){ bulk.submit(); });
    });
})();
</script>
@endpush
