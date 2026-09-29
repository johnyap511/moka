@extends('admin.layout')
@section('title', $own ? 'My Commission' : 'Sales Commission')
@section('page-title', $own ? 'My Commission' : 'Sales Commission')

@section('content')
@php
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M') : '—';
    $fmtY = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M Y') : '—';
    $rm = fn ($v) => 'RM ' . number_format($v, 2);
    $mon = fn ($m) => \Carbon\Carbon::parse($m . '-01')->format('M Y');
    $monthName = \Carbon\Carbon::parse($ym . '-01')->format('F Y');
    $q = fn ($m) => '?month=' . $m . ($own ? '' : ($person ? '&person=' . urlencode($person) : ''));
    $tot = (object) ['stays' => array_sum(array_column($data['people'], 'stays')), 'nights' => array_sum(array_column($data['people'], 'nights')), 'net' => array_sum(array_column($data['people'], 'net')), 'commission' => array_sum(array_column($data['people'], 'commission')), 'held' => array_sum(array_column($data['people'], 'held')), 'warnings' => array_sum(array_column($data['people'], 'warnings'))];
    $cross = count(array_filter($data['stays'], fn ($s) => $s->cross));
@endphp

<div class="sc-head">
    <div>
        <h1>{{ $own ? 'My Commission' : 'Sales Commission' }}</h1>
        <p class="sc-sub">{{ number_format($data['rate'] * 100, 0) }}% of room charges before SST, from eZee's Transaction Detail Report. Each night counts in the month eZee posted it. A month's commission is paid with the following month's salary.</p>
    </div>
    <div class="sc-nav">
        <a class="sc-nav__btn" href="{{ $q($prev) }}" title="{{ $mon($prev) }}">‹</a>
        <form method="get" class="sc-nav__form">
            <input type="month" name="month" value="{{ $ym }}" data-raw-dates onchange="this.form.submit()" aria-label="Month">
            @if(!$own)<select name="person" onchange="this.form.submit()" aria-label="Sales person"><option value="">Everyone</option>@foreach($people as $p)<option value="{{ $p }}" @selected($person === $p)>{{ $p }}</option>@endforeach</select>@endif
            <noscript><button type="submit" class="btn btn-primary btn-sm">Show</button></noscript>
        </form>
        <a class="sc-nav__btn" href="{{ $q($next) }}" title="{{ $mon($next) }}">›</a>
    </div>
</div>

<div class="sc-tiles">
    <div class="sc-tile sc-tile--main"><span>Commission for {{ $monthName }}</span><b>{{ $rm($tot->commission) }}</b><em class="sc-chip {{ $final ? 'sc-chip--final' : 'sc-chip--prov' }}">{{ $final ? 'Final' : 'Provisional' }} · {{ $payout }}</em></div>
    <div class="sc-tile"><span>Room charges (net of SST)</span><b>{{ $rm($tot->net) }}</b></div>
    <div class="sc-tile"><span>Nights posted</span><b>{{ number_format($tot->nights) }}</b><em>{{ $tot->stays }} stay{{ $tot->stays == 1 ? '' : 's' }}{{ $cross ? ', ' . $cross . ' cross-month' : '' }}</em></div>
    <div class="sc-tile {{ $tot->warnings ? 'sc-tile--warn' : '' }}"><span>On hold</span><b>{{ $tot->warnings ? $rm($tot->held) : '—' }}</b><em>{{ $tot->warnings ? $tot->warnings . ' stay(s) changed in eZee since the last report' : 'nothing changed since the last report' }}</em></div>
</div>

@if($own)
<details class="sc-explain" {{ count($data['stays']) ? '' : 'open' }}>
    <summary>How this is worked out, and what to do if a figure looks wrong</summary>
    <ul>
        <li><b>Only room charges count.</b> {{ number_format($data['rate'] * 100, 0) }}% of the room charge before SST. Deposits, cleaning fees, early check-in, late check-out, OTA fees and other charges are not included.</li>
        <li><b>Nights are counted by the date eZee posted them,</b> not by check-in date. A stay across two months is split: the earlier nights were paid with last month's commission, the later nights come this month. Such stays are marked <em class="sc-badge">cross-month</em> below with the split shown.</li>
        <li><b>Figures come from eZee's Transaction Detail Report,</b> uploaded by the office every week. A night posted after the last upload appears after the next one. The coverage date is shown at the bottom.</li>
        <li><b>A stay missing?</b> Check in eZee that the <b>Sales Person</b> field on that reservation is set to your name. If it was blank, ask the office to set it and re-upload.</li>
        <li><b>On hold</b> means eZee has since cancelled or shortened the stay; it is re-checked at the next upload.</li>
        <li><b>Provisional</b> months can still move until the month is closed; <b>Final</b> months do not change.</li>
        <li><b>Found a difference?</b> Tell the office the RES or folio number and the night in question.</li>
    </ul>
</details>
@endif

<div class="sc-grid {{ $own ? 'sc-grid--own' : '' }}">
    <div class="card">
        <div class="card-body">
            <h3 class="sc-h3">{{ $own ? 'Month by month' : 'By sales person, ' . $monthName }}</h3>
            @if($own)
            <table class="sc-table">
                <thead><tr><th>Month</th><th class="num">Nights</th><th class="num">Room charges</th><th class="num">Commission</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($history as $h)
                    <tr class="{{ $h->ym === $ym ? 'sc-row-current' : '' }}"><td><a href="{{ $q($h->ym) }}">{{ $mon($h->ym) }}</a></td><td class="num">{{ $h->nights }}</td><td class="num">{{ $rm($h->net) }}</td><td class="num"><b>{{ $rm($h->commission) }}</b></td><td><span class="sc-chip {{ $h->final ? 'sc-chip--final' : 'sc-chip--prov' }}">{{ $h->final ? 'Final' : 'Provisional' }}</span> <span class="sc-muted">{{ $h->payout }}</span></td></tr>
                @empty
                    <tr><td colspan="5" class="sc-empty">Nothing yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            @else
            <table class="sc-table">
                <thead><tr><th>Sales person</th><th class="num">Stays</th><th class="num">Nights</th><th class="num">Room charges</th><th class="num">Commission</th><th class="num">On hold</th></tr></thead>
                <tbody>
                @forelse($data['people'] as $p)
                    <tr><td><a href="?month={{ $ym }}&person={{ urlencode($p->name) }}">{{ $p->name }}</a></td><td class="num">{{ $p->stays }}</td><td class="num">{{ $p->nights }}</td><td class="num">{{ $rm($p->net) }}</td><td class="num"><b>{{ $rm($p->commission) }}</b></td>
                        <td class="num">@if($p->warnings)<span class="sc-warn" title="Stays changed in eZee since the last report">{{ $rm($p->held) }} ({{ $p->warnings }})</span>@else<span class="sc-muted">—</span>@endif</td></tr>
                @empty
                    <tr><td colspan="6" class="sc-empty">No posted room nights with a sales person in {{ $monthName }}. Upload the reports for that month.</td></tr>
                @endforelse
                </tbody>
                @if(count($data['people']) > 1)
                <tfoot><tr><th>Total</th><th class="num">{{ $tot->stays }}</th><th class="num">{{ $tot->nights }}</th><th class="num">{{ $rm($tot->net) }}</th><th class="num">{{ $rm($tot->commission) }}</th><th class="num">{{ $tot->warnings ? $rm($tot->held) : '—' }}</th></tr></tfoot>
                @endif
            </table>
            @endif
        </div>
    </div>

    @if(!$own)
    <div class="card">
        <div class="card-body">
            <h3 class="sc-h3">Month by month{{ $person ? ', ' . $person : '' }}</h3>
            <table class="sc-table sc-small">
                <thead><tr><th>Month</th><th class="num">Nights</th><th class="num">Room charges</th><th class="num">Commission</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($history as $h)
                    <tr class="{{ $h->ym === $ym ? 'sc-row-current' : '' }}"><td><a href="{{ $q($h->ym) }}">{{ $mon($h->ym) }}</a></td><td class="num">{{ $h->nights }}</td><td class="num">{{ $rm($h->net) }}</td><td class="num"><b>{{ $rm($h->commission) }}</b></td><td><span class="sc-chip {{ $h->final ? 'sc-chip--final' : 'sc-chip--prov' }}">{{ $h->final ? 'Final' : 'Provisional' }}</span> <span class="sc-muted">{{ $h->payout }}</span></td></tr>
                @empty
                    <tr><td colspan="5" class="sc-empty">Nothing yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<div class="card sc-stays">
    <div class="card-body">
        <div class="sc-stays__head">
            <h3 class="sc-h3">Stays in {{ $monthName }}{{ $person ? ', ' . $person : '' }}</h3>
            <span class="sc-muted">{{ count($data['stays']) }} stay(s). A <em class="sc-badge">cross-month</em> stay shows how its nights split across months.</span>
        </div>
        <div class="table-wrap">
        <table class="sc-table sc-stays__table" data-enhance="search" data-search-placeholder="Search guest, RES, folio or unit">
            <thead><tr>@if(!$own)<th>Sales person</th>@endif<th>Guest</th><th>RES / Folio</th><th>Property · Unit</th><th>Stay (check-in → out)</th><th class="num">Nights this month</th><th class="num">Room charges</th><th class="num">Commission</th><th>Note</th></tr></thead>
            <tbody>
            @forelse($data['stays'] as $s)
                <tr class="{{ $s->warn ? 'sc-row-warn' : '' }}">
                    @if(!$own)<td>{{ $s->sales_person }}</td>@endif
                    <td>{{ $s->guest }}<div class="sc-muted">{{ $s->source }}</div></td>
                    <td class="mono">{{ $s->res_no ?: 'no RES' }}<div class="sc-muted">{{ $s->folio_no }}</div></td>
                    <td>{{ $s->property }}<div class="sc-muted">{{ $s->room }}</div></td>
                    <td class="text-nowrap">{{ $fmt($s->arrival) }} → {{ $fmtY($s->departure) }}@if($s->cross)<div><em class="sc-badge">cross-month</em></div>@endif</td>
                    <td class="num">{{ $s->nights }}<div class="sc-muted text-nowrap">{{ $fmt($s->first_night) }}{{ $s->nights > 1 ? ' – ' . $fmt($s->last_night) : '' }}</div></td>
                    <td class="num">{{ $rm($s->net) }}</td>
                    <td class="num">@if($s->warn)<span class="sc-warn">on hold</span>@else<b>{{ $rm($s->commission) }}</b>@endif</td>
                    <td class="sc-note">
                        @if($s->warn)<div class="sc-warn">{{ $s->warn }}</div>@endif
                        @foreach($s->other_months as $o)
                            <div>{{ $o->before ? 'Also' : 'Then' }} <b>{{ $o->nights }}</b> night{{ $o->nights == 1 ? '' : 's' }} in {{ $mon($o->ym) }}: {{ $rm($o->commission) }} {{ $o->before ? 'paid with the ' . \Carbon\Carbon::parse($o->ym . '-01')->addMonth()->format('M Y') . ' salary' : 'comes with the ' . \Carbon\Carbon::parse($o->ym . '-01')->addMonth()->format('M Y') . ' salary' }}</div>
                        @endforeach
                        @if($s->cross && !$s->other_months && $s->departure && $s->departure > $data['to'])
                            <div>Stay continues after {{ $monthName }}; those nights count next month once eZee posts them.</div>
                        @endif
                        @if($s->cross && !$s->other_months && $s->arrival && $s->arrival < $data['from'])
                            <div>Nights before {{ $monthName }} are not in the uploaded reports.</div>
                        @endif
                        @if(!$s->warn && !$s->cross)<span class="sc-muted">{{ $s->status }}</span>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="sc-empty">Nothing to show for {{ $monthName }}.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

@if($own)
<p class="sc-foot">Reports uploaded up to: @foreach($hotels as $code => $name)@php $c = $coverage[$code] ?? null; @endphp{{ $name }} {{ $c ? $fmtY($c->last_day) : 'never' }}{{ $loop->last ? '' : ' · ' }}@endforeach</p>
@else
<details class="sc-setup" {{ count($data['stays']) ? '' : 'open' }}>
    <summary>Uploads, coverage and who can see what</summary>
    <div class="sc-grid" style="margin-top:12px">
        <div class="card">
            <div class="card-body">
                <h3 class="sc-h3">Upload eZee reports</h3>
                <p class="sc-help">In eZee: Reports → Back Office → <b>Transaction Detail Report</b>, one file per property, exported as Excel. Upload weekly. Overlapping dates are fine: the newest file replaces what it covers, so nothing is counted twice and cancelled or shortened stays correct themselves. Months already reported to owners are never changed.</p>
                @if(admin_can('sales.manage'))
                <form method="post" action="{{ route('admin.sales.upload') }}" enctype="multipart/form-data" class="sc-upload">
                    @csrf
                    <input type="hidden" name="month" value="{{ $ym }}">
                    <input type="file" name="files[]" accept=".xlsx,.xls" multiple required>
                    <select name="hotel"><option value="">Property: detect from the file</option>@foreach($hotels as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach</select>
                    <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                </form>
                @endif
                <table class="sc-table sc-small" style="margin-top:12px">
                    <thead><tr><th>Property</th><th>Covered to</th><th>Last upload</th></tr></thead>
                    <tbody>@foreach($hotels as $code => $name)@php $c = $coverage[$code] ?? null; $stale = $c && \Carbon\Carbon::parse($c->last_day)->lt(now()->subDays(8)); @endphp
                        <tr><td>{{ $name }}</td><td class="{{ !$c || $stale ? 'sc-warn' : '' }}">{{ $c ? $fmtY($c->last_day) : 'never' }}</td><td class="sc-muted">{{ $c ? \Carbon\Carbon::parse($c->last_upload)->format('j M H:i') : '—' }}</td></tr>
                    @endforeach</tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h3 class="sc-h3">Who can see what</h3>
                <p class="sc-help">Each Sales Person name from eZee is tied to one staff login, who then sees only their own commission. Operations, Manager and Sales Person roles see their own figures; Finance and Super Admin see everyone.</p>
                <table class="sc-table sc-small">
                    <thead><tr><th>Name in eZee</th><th>Login</th><th></th></tr></thead>
                    <tbody>
                    @foreach($persons as $sp)
                        <tr>
                            <td><b>{{ $sp->name }}</b></td>
                            <td>
                                @if(admin_can('sales.manage'))
                                <form method="post" action="{{ route('admin.sales.person') }}" class="sc-map">
                                    @csrf<input type="hidden" name="id" value="{{ $sp->id }}">
                                    <select name="user_id" onchange="this.form.submit()"><option value="">— not tied —</option>@foreach($staff as $u)<option value="{{ $u->id }}" @selected((int) $sp->user_id === (int) $u->id)>{{ $u->email }} ({{ $u->name }})</option>@endforeach</select>
                                </form>
                                @else{{ $sp->email ?: '—' }}@endif
                            </td>
                            <td class="sc-note">@if(!$sp->user_id)<span class="sc-warn">no login yet</span>@elseif(empty($sp->admin_role))sees everyone (super admin)@else{{ ucfirst($sp->admin_role) }} role @endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @if(count($uploads))
    <div class="card" style="margin-top:12px"><div class="card-body">
        <h3 class="sc-h3">Recent uploads</h3>
        <table class="sc-table sc-small">
            <thead><tr><th>When</th><th>Property</th><th>File</th><th>Period</th><th class="num">Rows stored</th><th class="num">Skipped (reported months)</th><th class="num">Replaced</th></tr></thead>
            <tbody>@foreach($uploads as $u)<tr><td class="text-nowrap">{{ \Carbon\Carbon::parse($u->created_at)->format('j M H:i') }}</td><td>{{ $hotels[$u->hotel_code] ?? $u->hotel_code }}</td><td class="sc-muted">{{ $u->filename }}</td><td class="text-nowrap">{{ $fmt($u->period_from) }} → {{ $fmt($u->period_to) }}</td><td class="num">{{ $u->rows_stored }}</td><td class="num">{{ $u->rows_skipped_locked }}</td><td class="num">{{ $u->rows_replaced }}</td></tr>@endforeach</tbody>
        </table>
    </div></div>
    @endif
</details>
@endif
@endsection

@push('scripts')
<style>
.sc-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;margin-bottom:14px}
.sc-head h1{margin:0 0 4px}.sc-sub{margin:0;font-size:13px;color:var(--text-secondary);max-width:640px;line-height:1.5}
.sc-nav{display:flex;align-items:center;gap:6px}.sc-nav__btn{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid var(--border,#e5e7eb);border-radius:8px;background:#fff;font-size:18px;color:inherit;text-decoration:none}.sc-nav__btn:hover{background:#f8fafc}
.sc-nav__form{display:flex;gap:6px}.sc-nav__form input,.sc-nav__form select{padding:7px 9px;font-size:13px;border:1px solid var(--border,#e5e7eb);border-radius:8px;background:#fff}
.sc-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px}
@media (max-width:900px){.sc-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
.sc-tile{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:14px 16px;display:grid;gap:4px}
.sc-tile span{font-size:12px;color:var(--text-secondary)}.sc-tile b{font-size:22px;line-height:1.2}.sc-tile em{font-style:normal;font-size:12px;color:var(--text-secondary)}
.sc-tile--main{background:#0f766e;border-color:#0f766e;color:#fff}.sc-tile--main span,.sc-tile--main em{color:rgba(255,255,255,.85)}.sc-tile--main b{font-size:26px}
.sc-tile--warn{border-color:#fcd34d;background:#fffbeb}
.sc-chip{display:inline-block;font-size:11.5px;padding:2px 9px;border-radius:999px;font-weight:600;font-style:normal}.sc-chip--final{background:#dcfce7;color:#166534}.sc-chip--prov{background:#fef3c7;color:#92400e}
.sc-tile--main .sc-chip--prov{background:rgba(255,255,255,.18);color:#fff}.sc-tile--main .sc-chip--final{background:rgba(255,255,255,.25);color:#fff}
.sc-explain{background:#f8fafc;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:10px 14px;margin-bottom:16px;font-size:13px}
.sc-explain summary{cursor:pointer;font-weight:600}.sc-explain ul{margin:8px 0 2px;padding-left:18px;line-height:1.55}.sc-explain li{margin:4px 0}
.sc-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px}.sc-grid--own{grid-template-columns:1fr}
@media (max-width:900px){.sc-grid{grid-template-columns:1fr}}
.sc-h3{margin:0 0 10px;font-size:15px}
.sc-table{width:100%;border-collapse:collapse;font-size:13px}.sc-table th,.sc-table td{padding:8px;border-bottom:1px solid var(--border,#e5e7eb);text-align:left;vertical-align:top}.sc-table th{font-size:11.5px;text-transform:uppercase;letter-spacing:.03em;color:var(--text-secondary);white-space:nowrap}
.sc-table .num{text-align:right;white-space:nowrap}.sc-table tfoot th{text-transform:none;font-size:13px;color:inherit;border-top:2px solid var(--border,#e5e7eb)}
.sc-small{font-size:12.5px}.sc-small th,.sc-small td{padding:6px}
.sc-muted{font-size:12px;color:var(--text-secondary)}.sc-empty{color:var(--text-secondary);padding:18px!important}
.sc-help{font-size:12.5px;color:var(--text-secondary);line-height:1.5;margin:0 0 10px}
.sc-upload{display:grid;gap:8px;font-size:13px}.sc-upload select,.sc-map select{padding:5px 8px;font-size:12.5px;max-width:100%}
.sc-warn{color:#b45309;font-weight:600}.sc-row-warn td{background:#fffbeb}.sc-row-current td{background:#f1f5f9}
.sc-badge{display:inline-block;font-style:normal;font-size:11px;font-weight:600;color:#1d4ed8;background:#dbeafe;border-radius:999px;padding:1px 8px}
.sc-note{font-size:12px;line-height:1.45;max-width:320px}.sc-note div{margin-bottom:2px}
.sc-stays__head{display:flex;justify-content:space-between;gap:12px;align-items:baseline;flex-wrap:wrap;margin-bottom:6px}
.sc-foot{font-size:12px;color:var(--text-secondary);margin-top:12px}
.sc-setup{margin-top:4px;font-size:13px}.sc-setup>summary{cursor:pointer;font-weight:600;padding:8px 0;color:var(--text-secondary)}
</style>
@endpush
