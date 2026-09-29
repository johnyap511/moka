@extends('admin.layout')
@section('title', $own ? 'My Commission' : 'Sales Commission')
@section('page-title', $own ? 'My Commission' : 'Sales Commission')

@section('content')
@php
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M') : '—';
    $fmtY = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M Y') : '—';
    $range = fn ($a, $b) => (!$a || !$b) ? $fmtY($a) . ' → ' . $fmtY($b) : (substr($a, 0, 4) === substr($b, 0, 4) ? $fmt($a) . ' → ' . $fmtY($b) : $fmtY($a) . ' → ' . $fmtY($b));
    $rm = fn ($v) => 'RM ' . number_format($v, 2);
    $rm0 = fn ($v) => 'RM ' . number_format($v, 0);
    $mon = fn ($m) => \Carbon\Carbon::parse($m . '-01')->format('M Y');
    $monthName = \Carbon\Carbon::parse($ym . '-01')->format('F Y');
    $nextMonth = \Carbon\Carbon::parse($ym . '-01')->addMonth()->format('M Y');
    $q = fn ($m) => '?month=' . $m . ($own ? '' : ($person ? '&person=' . urlencode($person) : ''));
    $rows = $team['rows'];
    $sum = fn ($f) => round(array_sum(array_map(fn ($r) => $r->$f, $rows)), 2);
    $cross = count(array_filter($data['stays'], fn ($s) => $s->cross));
    $held = array_sum(array_column($data['people'], 'held'));
    $warnings = array_sum(array_column($data['people'], 'warnings'));
    $statusText = ['probation' => 'On probation: not in the scheme yet', 'transition' => 'First 3 months after confirmation: RM15,000 minimum waived', 'full' => 'Confirmed: RM15,000 minimum applies', 'left' => 'Left the company'];
    $gateText = function ($r) {
        if (!$r->gates) return '';
        if (!$r->gates['eligible']) return $r->status === 'left' ? 'Left the company' : 'Not eligible (probation)';
        $fails = [];
        if (!$r->gates['min_sales']) $fails[] = 'below RM15,000 minimum';
        if (!$r->gates['approved']) $fails[] = 'absence without approval';
        if (!$r->gates['attendance']) $fails[] = $r->kpi->absences . ' recorded absences';
        if (!$r->gates['punctual']) $fails[] = 'lateness over 120 min (' . $r->kpi->lateness_min . ')';
        if ($fails) return 'Nil: ' . implode(', ', $fails);
        return $r->kpi->absences === 1 ? '50%: one recorded absence' : 'All KPIs met';
    };
    $tierLabel = fn ($t) => $t ? 'Tier ' . $t : 'Below Tier 1';
    $sopLink = $sop ? '<a href="' . route('admin.sales.sop') . '" target="_blank">Read the Commission SOP (v2.0)</a>' : 'SOP not uploaded yet';
@endphp

<div class="sc-head">
    <div>
        <h1>{{ $own ? 'My Commission' : 'Sales Commission' }}</h1>
        <p class="sc-sub">2% of direct-booking room charges before SST · KPI gates · team bonus · 70% paid next month, 30% deferred to year end. {!! $sopLink !!}</p>
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

@if($statement)
@php $st = $statement; $nextTier = null; foreach ([1 => 15000, 2 => 20000, 3 => 30000] as $t => $min) { if ($st->sales < $min) { $nextTier = [$t, $min]; break; } } @endphp
<div class="sc-tiles">
    <div class="sc-tile sc-tile--main"><span>Paid with {{ $nextMonth }} salary</span><b>{{ $rm($st->pay_now) }}</b><em class="sc-chip {{ $final ? 'sc-chip--final' : 'sc-chip--prov' }}">{{ $final ? 'Final' : 'Provisional' }}</em><em>70% of commission + bonus{{ $st->adjustments != 0 ? ($st->adjustments > 0 ? ' + ' : ' − ') . $rm(abs($st->adjustments)) . ' adj.' : '' }}</em></div>
    <div class="sc-tile"><span>Direct sales</span><b>{{ $rm($st->sales) }}</b>
        <div class="sc-bar"><i style="width:{{ min(100, $st->sales / 300) }}%"></i><u style="left:50%"></u><u style="left:66.7%"></u></div>
        <em>{{ $tierLabel($st->tier_reached) }}{{ $nextTier ? ' · ' . $rm0($nextTier[1] - $st->sales) . ' to Tier ' . $nextTier[0] : '' }}</em></div>
    <div class="sc-tile"><span>Commission 2%</span><b>{{ $rm($st->personal) }}</b><em>{{ $gateText($st) }}{{ $st->pct < 1 && $st->gross > 0 ? ' · before KPI ' . $rm($st->gross) : '' }}</em></div>
    <div class="sc-tile"><span>Team bonus share</span><b>{{ $rm($st->bonus) }}</b><em>@if($team['pool'])Tier {{ $team['tier'] }} pool {{ $rm0($team['pool']) }}: {{ $team['hit'] }} of {{ $team['counted'] }} counted staff reached it, shared by {{ $team['eligible'] }}@else No pool this month: under 80% of counted staff reached RM15,000 @endif </em></div>
    <div class="sc-tile"><span>Deferred 30%</span><b>{{ $rm($st->deferred) }}</b><em>paid after year-end audit</em></div>
    @if(isset($deferred) && $deferred)
    <div class="sc-tile"><span>Deferred balance {{ $deferred[0]->year }}</span><b>{{ $rm($deferred[0]->outstanding) }}</b><em>{{ $rm($deferred[0]->accrued) }} accrued · {{ $rm($deferred[0]->paid) }} paid</em></div>
    @endif
</div>
<p class="sc-muted" style="margin:-6px 0 12px">{{ $statusText[$st->status] ?? '' }}{{ $st->confirmed_on ? ' · confirmed ' . $fmtY($st->confirmed_on) : ' · confirmation date not set' }} · {{ $st->stays }} stays, {{ $st->nights }} nights{{ $warnings ? ' · ' . $warnings . ' on hold (' . $rm($held) . ')' : '' }}</p>
@else
<div class="sc-tiles">
    <div class="sc-tile sc-tile--main"><span>Paid with {{ $nextMonth }} salary, everyone</span><b>{{ $rm($sum('pay_now')) }}</b><em class="sc-chip {{ $final ? 'sc-chip--final' : 'sc-chip--prov' }}">{{ $final ? 'Final' : 'Provisional' }} · {{ $payout }}</em></div>
    <div class="sc-tile"><span>Direct sales</span><b>{{ $rm($sum('sales')) }}</b><em>{{ array_sum(array_column($data['people'], 'stays')) }} stays, {{ array_sum(array_column($data['people'], 'nights')) }} nights</em></div>
    <div class="sc-tile"><span>Commission 2% after KPIs</span><b>{{ $rm($sum('personal')) }}</b><em>before KPIs {{ $rm($sum('gross')) }}</em></div>
    <div class="sc-tile"><span>Team bonus</span><b>{{ $rm($sum('bonus')) }}</b><em>@if($team['pool'])Tier {{ $team['tier'] }}, pool {{ $rm0($team['pool']) }}: {{ $team['hit'] }} of {{ $team['counted'] }} reached it, {{ $team['eligible'] }} eligible @else No pool: under 80% of {{ $team['counted'] }} counted staff reached RM15,000 @endif </em></div>
    <div class="sc-tile"><span>Deferred 30%</span><b>{{ $rm($sum('deferred')) }}</b><em>paid after year-end audit</em></div>
    <div class="sc-tile {{ $warnings ? 'sc-tile--warn' : '' }}"><span>On hold</span><b>{{ $warnings ? $rm($held) : '—' }}</b><em>{{ $warnings ? $warnings . ' stays changed in eZee since last upload' : 'no changes since last upload' }}</em></div>
</div>
@endif

@if($own)
<details class="sc-explain" {{ count($data['stays']) ? '' : 'open' }}>
    <summary>How it is calculated · what to do if a figure looks wrong</summary>
    <ul>
        <li><b>Direct sales</b> = room charges before SST of bookings with your name as Sales Person in eZee. OTA and agent bookings never count. Deposits, cleaning, early/late check-in, other charges are not room charges.</li>
        <li><b>Nights count in the month eZee posted them.</b> A stay across two months is split; look for <em class="sc-badge">cross-month</em>.</li>
        <li><b>2%</b> of direct sales, then the KPI gates: RM15,000 minimum (from your 4th month after confirmation), 1 recorded absence = 50%, 2+ absences, an unapproved absence or over 120 min lateness = nil.</li>
        <li><b>Team bonus</b>: if 80% of the team reach RM15k / 20k / 30k, a pool of RM500 / 1,000 / 2,000 is shared by those who reached RM15k and passed the KPIs.</li>
        <li><b>70% paid with next month's salary, 30% deferred</b> to after the year-end audit. The deferred balance restarts each January; past years stay on record.</li>
        <li><b>Cancelled after payment</b> = clawed back from the next payout, shown as an adjustment.</li>
        <li><b>A stay missing?</b> Check the Sales Person field on it in eZee, then ask the office to re-upload. <b>A figure wrong?</b> Tell the Operations Manager the RES or folio within 7 days.</li>
    </ul>
</details>
@endif

<div class="sc-grid {{ $own ? 'sc-grid--own' : '' }}">
    @if(!$own)
    <div class="card sc-span2">
        <div class="card-body">
            <h3 class="sc-h3">By sales person, {{ $monthName }}</h3>
            <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>Sales person</th><th>Status</th><th class="num">Direct sales</th><th>Tier</th><th>KPI</th><th class="num">2%</th><th class="num">Bonus</th><th class="num">Adj.</th><th class="num">Paid 70%</th><th class="num">Deferred</th></tr></thead>
                <tbody>
                @forelse($rows as $r)
                    <tr class="{{ $person === $r->name ? 'sc-row-current' : '' }}"><td><a href="?month={{ $ym }}&person={{ urlencode($r->name) }}">{{ $r->name }}</a></td><td class="sc-muted">{{ ucfirst($r->status) }}{!! !$r->confirmed_on ? '<br><span class="sc-warn">no confirmation date</span>' : '' !!}{!! !$r->kpi->entered && $r->status !== 'probation' ? '<br>KPI not entered' : '' !!}</td><td class="num">{{ $rm($r->sales) }}</td><td>{{ $tierLabel($r->tier_reached) }}</td><td class="sc-note">{{ $gateText($r) }}</td>
                        <td class="num">{{ $rm($r->personal) }}</td><td class="num">{{ $rm($r->bonus) }}</td><td class="num">{{ $r->adjustments != 0 ? $rm($r->adjustments) : '—' }}</td><td class="num"><b>{{ $rm($r->pay_now) }}</b></td><td class="num">{{ $rm($r->deferred) }}</td></tr>
                @empty
                    <tr><td colspan="10" class="sc-empty">No sales persons yet. Upload the reports for a month.</td></tr>
                @endforelse
                </tbody>
                @if(count($rows) > 1)
                <tfoot><tr><th colspan="2">Total</th><th class="num">{{ $rm($sum('sales')) }}</th><th></th><th></th><th class="num">{{ $rm($sum('personal')) }}</th><th class="num">{{ $rm($sum('bonus')) }}</th><th class="num">{{ $rm($sum('adjustments')) }}</th><th class="num">{{ $rm($sum('pay_now')) }}</th><th class="num">{{ $rm($sum('deferred')) }}</th></tr></tfoot>
                @endif
            </table>
            </div>
        </div>
    </div>
    @endif

    <div class="card {{ $own ? '' : '' }}">
        <div class="card-body">
            <h3 class="sc-h3">Month by month{{ $person ? ', ' . $person : '' }}</h3>
            <div class="table-wrap">
            <table class="sc-table sc-small">
                <thead><tr><th>Month</th><th class="num">Direct sales</th><th>Tier</th>@if($person)<th>KPI</th>@endif<th class="num">2%</th><th class="num">Bonus</th><th class="num">Adj.</th><th class="num">Paid 70%</th><th class="num">Deferred</th><th></th></tr></thead>
                <tbody>
                @forelse($history as $h)
                    <tr class="{{ $h->ym === $ym ? 'sc-row-current' : '' }}"><td><a href="{{ $q($h->ym) }}">{{ $mon($h->ym) }}</a></td><td class="num">{{ $rm($h->sales) }}</td><td>{{ $tierLabel($h->tier) }}</td>@if($person)<td class="sc-muted">{{ $h->pct === null ? '' : ($h->pct == 1 ? 'met' : ($h->pct == 0.5 ? '50%' : 'nil')) }}</td>@endif<td class="num">{{ $rm($h->personal) }}</td><td class="num">{{ $rm($h->bonus) }}</td><td class="num">{{ $h->adjustments != 0 ? $rm($h->adjustments) : '—' }}</td><td class="num"><b>{{ $rm($h->pay_now) }}</b></td><td class="num">{{ $rm($h->deferred) }}</td><td><span class="sc-chip {{ $h->final ? 'sc-chip--final' : 'sc-chip--prov' }}">{{ $h->final ? 'Final' : 'Provisional' }}</span></td></tr>
                @empty
                    <tr><td colspan="10" class="sc-empty">Nothing yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    @if(isset($deferred))
    <div class="card">
        <div class="card-body">
            <h3 class="sc-h3">Deferred 30% by year</h3>
            <p class="sc-help">Paid within 60 days of the year-end audit (SOP §11.2). Only final months count as owed.</p>
            <table class="sc-table sc-small">
                <thead><tr><th>Year</th><th class="num">Accrued</th><th class="num">Final</th><th class="num">Paid</th><th class="num">Balance</th><th>Payouts</th></tr></thead>
                <tbody>
                @foreach($deferred as $d)
                    <tr><td>{{ $d->year }}</td><td class="num">{{ $rm($d->accrued) }}</td><td class="num">{{ $rm($d->final) }}</td><td class="num">{{ $rm($d->paid) }}</td><td class="num"><b>{{ $rm($d->outstanding) }}</b></td><td class="sc-note">@foreach($d->payouts as $po){{ $fmtY($po->paid_on) }}: {{ $rm($po->amount) }}{{ $po->note ? ' (' . $po->note . ')' : '' }}<br>@endforeach</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($statement && $statement->adjustment_rows->count())
    <div class="card">
        <div class="card-body">
            <h3 class="sc-h3">Adjustments in {{ $monthName }}</h3>
            <table class="sc-table sc-small"><tbody>
            @foreach($statement->adjustment_rows as $a)
                <tr><td class="num" style="width:110px">{{ $rm($a->amount) }}</td><td>{{ $a->reason }}</td></tr>
            @endforeach
            </tbody></table>
        </div>
    </div>
    @endif
</div>

<div class="card sc-stays">
    <div class="card-body">
        <div class="sc-stays__head">
            <h3 class="sc-h3">Stays, {{ $monthName }}{{ $person ? ' · ' . $person : '' }}</h3>
            <span class="sc-muted">{{ count($data['stays']) }} stays{{ $cross ? ' · ' . $cross . ' cross-month' : '' }}</span>
        </div>
        <div class="table-wrap">
        <table class="sc-table sc-stays__table" data-enhance="search" data-search-placeholder="Search guest, RES, folio or unit">
            <thead><tr>@if(!$person)<th>Sales person</th>@endif<th>Guest</th><th>RES / Folio</th><th>Unit</th><th>Stay</th><th class="num">Nights</th><th class="num">Room charges</th><th class="num">2%</th><th>Note</th></tr></thead>
            <tbody>
            @forelse($data['stays'] as $s)
                <tr class="{{ $s->warn ? 'sc-row-warn' : '' }}">
                    @if(!$person)<td>{{ $s->sales_person }}</td>@endif
                    <td>{{ $s->guest }}<div class="sc-muted">{{ $s->source }}</div></td>
                    <td class="mono">{{ $s->res_no ?: 'no RES' }}<div class="sc-muted">{{ $s->folio_no }}</div></td>
                    <td>{{ $s->room }}<div class="sc-muted">{{ $s->property }}</div></td>
                    <td class="text-nowrap">{{ $range($s->arrival, $s->departure) }}@if($s->cross)<div><em class="sc-badge">cross-month</em></div>@endif</td>
                    <td class="num">{{ $s->nights }}<div class="sc-muted">{{ $fmt($s->first_night) }}{{ $s->nights > 1 ? ' – ' . $fmt($s->last_night) : '' }}</div></td>
                    <td class="num">{{ $rm($s->net) }}</td>
                    <td class="num">@if($s->warn)<span class="sc-warn">on hold</span>@else{{ $rm($s->commission) }}@endif</td>
                    <td class="sc-note">
                        @if($s->warn)<div class="sc-warn">{{ $s->warn }}</div>@endif
                        @foreach($s->other_months as $o)
                            <div>{{ $o->before ? 'Also' : 'Then' }} {{ $o->nights }} night{{ $o->nights == 1 ? '' : 's' }} in {{ \Carbon\Carbon::parse($o->ym . '-01')->format('M') }}: {{ $rm($o->commission) }}{{ $o->before ? ' (paid)' : '' }}</div>
                        @endforeach
                        @if($s->cross && !$s->other_months && $s->departure && $s->departure > $data['to'])<div>Continues into {{ \Carbon\Carbon::parse($data['to'])->addMonth()->format('M') }}</div>@endif
                        @if($s->cross && !$s->other_months && $s->arrival && $s->arrival < $data['from'])<div>Started before {{ \Carbon\Carbon::parse($data['from'])->format('M') }}</div>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="sc-empty">Nothing for {{ $monthName }}.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

@if($own)
<p class="sc-foot">Reports uploaded to: @foreach($hotels as $code => $name){{ $name }} {{ isset($coverage[$code]) ? $fmtY($coverage[$code]->last_day) : 'never' }}{{ $loop->last ? '' : ' · ' }}@endforeach · {!! $sopLink !!}</p>
@else
<details class="sc-setup" {{ count(array_filter($rows, fn ($r) => !$r->kpi->entered && $r->status !== 'probation')) ? 'open' : '' }}>
    <summary>KPI inputs for {{ $monthName }} (attendance, lateness, employment)</summary>
    <div class="card" style="margin-top:12px"><div class="card-body">
        <p class="sc-help">From HR's records, per SOP §8: recorded absences after any approved Medical Exception (§8.3), whether any absence was without approval, accumulated lateness in minutes, and whether the person is under a disciplinary sanction affecting bonus. "Employed full month" decides who is counted in the 80% team threshold (§3.3). Confirmation dates are set under "Who can see what".{{ $final ? ' This month is final: change these only to correct the record of what was paid.' : '' }}</p>
        @if(admin_can('sales.manage'))
        <form method="post" action="{{ route('admin.sales.kpi') }}">
            @csrf<input type="hidden" name="ym" value="{{ $ym }}">
            <div class="table-wrap"><table class="sc-table sc-small sc-kpi">
                <thead><tr><th>Sales person</th><th>Status</th><th>Employed full month</th><th>Recorded absences</th><th>Absence without approval</th><th>Lateness (min)</th><th>Disciplinary</th><th>Note</th></tr></thead>
                <tbody>
                @foreach($rows as $r)
                    <tr><td><b>{{ $r->name }}</b></td><td class="sc-muted">{{ ucfirst($r->status) }}</td>
                        <td><input type="checkbox" name="kpi[{{ $r->id }}][employed_full_month]" value="1" @checked($r->kpi->employed_full_month)></td>
                        <td><input type="number" name="kpi[{{ $r->id }}][absences]" value="{{ $r->kpi->absences }}" min="0" max="31" style="width:64px"></td>
                        <td><input type="checkbox" name="kpi[{{ $r->id }}][unapproved_absence]" value="1" @checked($r->kpi->unapproved_absence)></td>
                        <td><input type="number" name="kpi[{{ $r->id }}][lateness_min]" value="{{ $r->kpi->lateness_min }}" min="0" style="width:72px"></td>
                        <td><input type="checkbox" name="kpi[{{ $r->id }}][disciplinary]" value="1" @checked($r->kpi->disciplinary)></td>
                        <td><input type="text" name="kpi[{{ $r->id }}][note]" value="{{ $r->kpi->note }}" maxlength="255" placeholder="e.g. 1 MC exempted (§8.3)" style="width:100%"></td></tr>
                @endforeach
                </tbody>
            </table></div>
            <button type="submit" class="btn btn-primary btn-sm" style="margin-top:8px">Save KPI inputs for {{ $monthName }}</button>
        </form>
        @endif
    </div></div>
</details>

<details class="sc-setup">
    <summary>Adjustments and deferred payouts</summary>
    <div class="sc-grid" style="margin-top:12px">
        <div class="card"><div class="card-body">
            <h3 class="sc-h3">Adjustments in {{ $monthName }}</h3>
            <p class="sc-help">Clawbacks (SOP §12) are raised automatically when an upload shows a paid night cancelled or voided. Add manual ones here for refunds, management-approved bookings, or corrections; they change the 70% payout of the month.</p>
            <table class="sc-table sc-small"><tbody>
            @forelse($adjustments as $a)
                <tr><td>{{ $a->name }}</td><td class="num">{{ $rm($a->amount) }}</td><td class="sc-note">{{ $a->reason }}</td><td>@if(admin_can('sales.manage') && !$final)<form method="post" action="{{ route('admin.sales.adjustment.delete', $a->id) }}" onsubmit="return confirm('Remove this adjustment?')">@csrf<button class="btn btn-secondary btn-sm">Remove</button></form>@endif</td></tr>
            @empty
                <tr><td colspan="4" class="sc-empty">None this month.</td></tr>
            @endforelse
            </tbody></table>
            @if(admin_can('sales.manage') && !$final)
            <form method="post" action="{{ route('admin.sales.adjustment') }}" class="sc-inline" style="margin-top:10px">
                @csrf<input type="hidden" name="ym" value="{{ $ym }}">
                <select name="sales_person_id" required>@foreach($persons as $sp)<option value="{{ $sp->id }}">{{ $sp->name }}</option>@endforeach</select>
                <input type="number" name="amount" step="0.01" placeholder="Amount (negative = deduct)" required style="width:190px">
                <input type="text" name="reason" placeholder="Reason" maxlength="255" required style="flex:1;min-width:200px">
                <button type="submit" class="btn btn-primary btn-sm">Add</button>
            </form>
            @endif
        </div></div>
        <div class="card"><div class="card-body">
            <h3 class="sc-h3">Deferred payouts (30%)</h3>
            <p class="sc-help">Record each payout of the deferred 30% after the audited accounts (SOP §11.2). Pick a person on the page to see their year-by-year balance.</p>
            @if(admin_can('sales.manage'))
            <form method="post" action="{{ route('admin.sales.payout') }}" class="sc-inline">
                @csrf
                <select name="sales_person_id" required>@foreach($persons as $sp)<option value="{{ $sp->id }}" @selected($person === $sp->name)>{{ $sp->name }}</option>@endforeach</select>
                <input type="number" name="year" value="{{ (int) substr($ym, 0, 4) }}" min="2020" max="2100" required style="width:84px">
                <input type="number" name="amount" step="0.01" min="0.01" placeholder="Amount" required style="width:120px">
                <input type="date" name="paid_on" value="{{ date('Y-m-d') }}" required data-raw-dates>
                <input type="text" name="note" placeholder="Note" maxlength="255" style="flex:1;min-width:140px">
                <button type="submit" class="btn btn-primary btn-sm">Record</button>
            </form>
            @endif
        </div></div>
    </div>
</details>

<details class="sc-setup" {{ count($data['stays']) ? '' : 'open' }}>
    <summary>Uploads, coverage, who can see what, and the SOP</summary>
    <div class="sc-grid" style="margin-top:12px">
        <div class="card">
            <div class="card-body">
                <h3 class="sc-h3">Upload eZee reports</h3>
                <p class="sc-help">In eZee: Reports → Back Office → <b>Transaction Detail Report</b>, one file per property, exported as Excel. Upload weekly. Overlapping dates are fine: the newest file replaces what it covers, so nothing is counted twice and cancelled or shortened stays correct themselves. Months already reported to owners are kept as paid; a paid night that later shows cancelled raises a clawback instead.</p>
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
                @if(admin_can('sales.manage'))
                <form method="post" action="{{ route('admin.sales.sop.upload') }}" enctype="multipart/form-data" class="sc-inline" style="margin-top:14px">
                    @csrf<span class="sc-muted">SOP shown to staff: {!! $sopLink !!}.</span><input type="file" name="sop" accept="application/pdf" required><button type="submit" class="btn btn-secondary btn-sm">Replace SOP</button>
                </form>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h3 class="sc-h3">Who can see what</h3>
                <p class="sc-help">Each Sales Person name from eZee is tied to one staff login, who then sees only their own commission. The confirmation date drives eligibility (SOP §3): blank = probation, first three complete months after it = minimum waived.</p>
                <table class="sc-table sc-small">
                    <thead><tr><th>Name in eZee</th><th>Login</th><th>Confirmed on</th><th>Left on</th><th></th></tr></thead>
                    <tbody>
                    @foreach($persons as $sp)
                        <tr>
                            <td><b>{{ $sp->name }}</b></td>
                            @if(admin_can('sales.manage'))
                            <td colspan="3">
                                <form method="post" action="{{ route('admin.sales.person') }}" class="sc-inline">
                                    @csrf<input type="hidden" name="id" value="{{ $sp->id }}">
                                    <select name="user_id"><option value="">— not tied —</option>@foreach($staff as $u)<option value="{{ $u->id }}" @selected((int) $sp->user_id === (int) $u->id)>{{ $u->email }} ({{ $u->name }})</option>@endforeach</select>
                                    <input type="date" name="confirmed_on" value="{{ $sp->confirmed_on }}" data-raw-dates title="Confirmed on">
                                    <input type="date" name="left_on" value="{{ $sp->left_on }}" data-raw-dates title="Left on">
                                    <button type="submit" class="btn btn-secondary btn-sm">Save</button>
                                </form>
                            </td>
                            @else
                            <td>{{ $sp->email ?: '—' }}</td><td>{{ $fmtY($sp->confirmed_on) }}</td><td>{{ $fmtY($sp->left_on) }}</td>
                            @endif
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
            <thead><tr><th>When</th><th>Property</th><th>File</th><th>Period</th><th class="num">Rows stored</th><th class="num">Already final (kept as paid)</th><th class="num">Replaced</th><th class="num">Clawbacks</th><th></th></tr></thead>
            <tbody>@foreach($uploads as $u)<tr><td class="text-nowrap">{{ \Carbon\Carbon::parse($u->created_at)->format('j M H:i') }}</td><td>{{ $hotels[$u->hotel_code] ?? $u->hotel_code }}</td><td class="sc-muted">{{ $u->filename }}</td><td class="text-nowrap">{{ $fmt($u->period_from) }} → {{ $fmt($u->period_to) }}</td><td class="num">{{ $u->rows_stored }}</td><td class="num">{{ $u->rows_skipped_locked }}</td><td class="num">{{ $u->rows_replaced }}</td><td class="num">{{ $u->clawbacks ?? 0 }}</td><td>@if(admin_can('sales.manage') && DB::table('sales_transactions')->where('upload_id', $u->id)->exists())<form method="post" action="{{ route('admin.sales.upload.delete', $u->id) }}" onsubmit="return confirm('Remove this upload and its rows? Use this to replace a wrong file, then upload the corrected one.')">@csrf<button class="btn btn-secondary btn-sm">Remove</button></form>@endif</td></tr>@endforeach</tbody>
        </table>
    </div></div>
    @endif
</details>
@endif
@endsection

@push('scripts')
<style>
.sc-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;margin-bottom:12px}
.sc-head h1{margin:0 0 2px;font-size:20px}.sc-sub{margin:0;font-size:12.5px;color:var(--text-secondary);max-width:720px;line-height:1.45}
.sc-nav{display:flex;align-items:center;gap:6px}.sc-nav__btn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid var(--border,#e5e7eb);border-radius:8px;background:#fff;font-size:17px;color:inherit;text-decoration:none}.sc-nav__btn:hover{background:#f8fafc}
.sc-nav__form{display:flex;gap:6px}.sc-nav__form input,.sc-nav__form select{padding:6px 8px;font-size:13px;border:1px solid var(--border,#e5e7eb);border-radius:8px;background:#fff}
.sc-tiles{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;margin-bottom:12px}
@media (max-width:1100px){.sc-tiles{grid-template-columns:repeat(3,minmax(0,1fr))}}@media (max-width:640px){.sc-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
.sc-tile{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:10px 12px;display:grid;gap:3px;align-content:start}
.sc-tile span{font-size:11.5px;color:var(--text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.sc-tile b{font-size:19px;line-height:1.15}.sc-tile em{font-style:normal;font-size:11.5px;color:var(--text-secondary);line-height:1.35}
.sc-tile--main{background:#0f766e;border-color:#0f766e;color:#fff}.sc-tile--main span,.sc-tile--main em{color:rgba(255,255,255,.85)}.sc-tile--main b{font-size:22px}
.sc-tile--warn{border-color:#fcd34d;background:#fffbeb}
.sc-bar{position:relative;height:6px;background:#e2e8f0;border-radius:999px;overflow:hidden;margin:3px 0}.sc-bar i{position:absolute;left:0;top:0;bottom:0;background:#0f766e;border-radius:999px}.sc-bar u{position:absolute;top:0;bottom:0;width:2px;background:#fff}
.sc-chip{display:inline-block;font-size:11px;padding:1px 8px;border-radius:999px;font-weight:600;font-style:normal;width:max-content}.sc-chip--final{background:#dcfce7;color:#166534}.sc-chip--prov{background:#fef3c7;color:#92400e}
.sc-tile--main .sc-chip--prov{background:rgba(255,255,255,.18);color:#fff}.sc-tile--main .sc-chip--final{background:rgba(255,255,255,.25);color:#fff}
.sc-explain{background:#f8fafc;border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:8px 12px;margin-bottom:12px;font-size:12.5px}
.sc-explain summary{cursor:pointer;font-weight:600}.sc-explain ul{margin:6px 0 2px;padding-left:18px;line-height:1.5}.sc-explain li{margin:3px 0}
.sc-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:12px}.sc-grid--own{grid-template-columns:minmax(0,3fr) minmax(0,2fr)}.sc-span2{grid-column:1/-1}
@media (max-width:900px){.sc-grid,.sc-grid--own{grid-template-columns:1fr}}
.sc-h3{margin:0 0 8px;font-size:14px}
.card .card-body{padding:12px 14px}
.sc-table{width:100%;border-collapse:collapse;font-size:12.5px;line-height:1.35}.sc-table th,.sc-table td{padding:5px 8px;border-bottom:1px solid var(--border,#e5e7eb);text-align:left;vertical-align:top}.sc-table th{font-size:11px;text-transform:uppercase;letter-spacing:.03em;color:var(--text-secondary);white-space:nowrap}
.sc-table .num{text-align:right;white-space:nowrap}.sc-table tfoot th{text-transform:none;font-size:12.5px;color:inherit;border-top:2px solid var(--border,#e5e7eb)}
.sc-small{font-size:12px}.sc-small th,.sc-small td{padding:4px 6px}
.sc-stays__table{min-width:960px}.sc-stays__table td{white-space:nowrap}.sc-stays__table td.sc-note{white-space:normal;min-width:200px}
.sc-kpi input[type=number],.sc-kpi input[type=text]{padding:3px 6px;font-size:12px;border:1px solid var(--border,#e5e7eb);border-radius:6px}
.sc-muted{font-size:11.5px;color:var(--text-secondary)}.sc-empty{color:var(--text-secondary);padding:14px!important}
.sc-help{font-size:12px;color:var(--text-secondary);line-height:1.45;margin:0 0 8px}
.sc-upload{display:grid;gap:6px;font-size:12.5px}.sc-upload select,.sc-inline select,.sc-inline input{padding:4px 8px;font-size:12px;max-width:100%;border:1px solid var(--border,#e5e7eb);border-radius:6px}
.sc-inline{display:flex;gap:6px;flex-wrap:wrap;align-items:center;font-size:12px}
.sc-warn{color:#b45309;font-weight:600}.sc-row-warn td{background:#fffbeb}.sc-row-current td{background:#f1f5f9}
.sc-badge{display:inline-block;font-style:normal;font-size:10.5px;font-weight:600;color:#1d4ed8;background:#dbeafe;border-radius:999px;padding:0 7px}
.sc-note{font-size:11.5px;line-height:1.4}.sc-note div{margin-bottom:1px}
.sc-stays__head{display:flex;justify-content:space-between;gap:12px;align-items:baseline;flex-wrap:wrap;margin-bottom:4px}
.sc-foot{font-size:11.5px;color:var(--text-secondary);margin-top:10px}
.sc-setup{margin-top:2px;font-size:12.5px}.sc-setup>summary{cursor:pointer;font-weight:600;padding:6px 0;color:var(--text-secondary)}
</style>
@endpush
