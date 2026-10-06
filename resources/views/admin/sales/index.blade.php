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
    <div class="sc-tile"><span>Team bonus share</span><b>{{ $rm($st->bonus) }}</b><em>@if($team['pool'])Tier {{ $team['tier'] }} pool {{ $rm0($team['pool']) }}: {{ $team['hit'] }} of {{ $team['counted'] }} counted staff reached it, shared by {{ $team['eligible'] }}@else No pool: {{ count(array_filter($rows, fn ($r) => $r->sales >= 15000)) }} of {{ $team['counted'] }} reached RM15k (need 80%) @endif </em></div>
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

@if(!$own && $rows && count(array_filter($rows, fn ($r) => !$r->kpi->entered && $r->status !== 'probation')))
<div class="sc-banner"><b>KPI inputs for {{ $monthName }} not entered yet.</b> Sales figures are automatic; attendance, lateness and employment come from HR. Until they are entered everyone is treated as having met the attendance and punctuality KPIs. <a href="#kpi">Enter them now</a></div>
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
                    <tr class="{{ $person === $r->name ? 'sc-row-current' : '' }}"><td><a href="?month={{ $ym }}&person={{ urlencode($r->name) }}">{{ $r->name }}</a></td><td class="sc-muted text-nowrap">{{ ucfirst($r->status) }}{!! !$r->confirmed_on ? ' · <span class="sc-warn">no confirmation date</span>' : '' !!}{{ !$r->kpi->entered && $r->status !== 'probation' ? ' · KPI not entered' : '' }}</td><td class="num">{{ $rm($r->sales) }}</td><td>{{ $tierLabel($r->tier_reached) }}</td><td class="sc-note">{{ $gateText($r) }}</td>
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
                    <td class="sc-stay">{{ $range($s->arrival, $s->departure) }}@if($s->cross)<div><em class="sc-badge">cross-month</em></div>@endif</td>
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
@php
    $kpiMissing = count(array_filter($rows, fn ($r) => !$r->kpi->entered && $r->status !== 'probation'));
    $untied = count(array_filter($persons->all(), fn ($sp) => !$sp->user_id));
    $staleHotels = count(array_filter(array_keys($hotels), fn ($code) => !isset($coverage[$code]) || \Carbon\Carbon::parse($coverage[$code]->last_day)->lt(now()->subDays(8))));
    $tab = $kpiMissing ? 'kpi' : (count($data['stays']) ? 'uploads' : 'uploads');
@endphp
<div class="sc-setup" id="setup" data-tab="{{ $tab }}">
    <div class="sc-tabs" role="tablist">
        <button type="button" class="sc-tab" data-tab="kpi" id="kpi">KPI inputs @if($kpiMissing)<i class="sc-dot"></i>@endif</button>
        <button type="button" class="sc-tab" data-tab="uploads">Uploads @if($staleHotels)<i class="sc-dot"></i>@endif</button>
        <button type="button" class="sc-tab" data-tab="people">Sales persons @if($untied)<i class="sc-dot"></i>@endif</button>
        <button type="button" class="sc-tab" data-tab="adjust">Adjustments</button>
        <button type="button" class="sc-tab" data-tab="deferred">Deferred payouts</button>
    </div>

    {{-- KPI inputs --}}
    <section class="sc-pane card" data-pane="kpi"><div class="card-body">
        <div class="sc-pane__head">
            <div><h3 class="sc-h3">KPI inputs, {{ $monthName }}</h3><p class="sc-help">From HR's clock-in and leave records. Sales is checked automatically.{{ $final ? ' Month is final: edit only to correct the record.' : '' }}</p></div>
            <details class="sc-why"><summary>Rules</summary><ul>
                <li><b>Full month</b>: employed the whole month, counted in the 80% team threshold (§3.3).</li>
                <li><b>Absences</b>: MC, unpaid leave, no-shows, minus approved Medical Exceptions (§8.3). 1 = 50%, 2+ = nil.</li>
                <li><b>Unapproved</b>: one absence without approval = nil.</li>
                <li><b>Late (min)</b>: minutes over the month against shift start; over 120 = nil.</li>
                <li><b>Disciplinary</b>: live sanction affecting bonus (§9.4).</li>
            </ul></details>
        </div>
        @if(admin_can('sales.manage'))
        <form method="post" action="{{ route('admin.sales.kpi') }}">
            @csrf<input type="hidden" name="ym" value="{{ $ym }}">
            <div class="table-wrap"><table class="sc-table sc-kpi">
                <thead><tr><th>Sales person</th><th class="ctr">Full month</th><th class="ctr">Absences</th><th class="ctr">Unapproved</th><th class="ctr">Late (min)</th><th class="ctr">Disciplinary</th><th>Note</th></tr></thead>
                <tbody>
                @foreach($rows as $r)
                    <tr><td><b>{{ $r->name }}</b><div class="sc-muted">{{ ucfirst($r->status) }}</div></td>
                        <td class="ctr"><input type="checkbox" name="kpi[{{ $r->id }}][employed_full_month]" value="1" @checked($r->kpi->employed_full_month)></td>
                        <td class="ctr"><input type="number" name="kpi[{{ $r->id }}][absences]" value="{{ $r->kpi->absences }}" min="0" max="31"></td>
                        <td class="ctr"><input type="checkbox" name="kpi[{{ $r->id }}][unapproved_absence]" value="1" @checked($r->kpi->unapproved_absence)></td>
                        <td class="ctr"><input type="number" name="kpi[{{ $r->id }}][lateness_min]" value="{{ $r->kpi->lateness_min }}" min="0"></td>
                        <td class="ctr"><input type="checkbox" name="kpi[{{ $r->id }}][disciplinary]" value="1" @checked($r->kpi->disciplinary)></td>
                        <td><input type="text" name="kpi[{{ $r->id }}][note]" value="{{ $r->kpi->note }}" maxlength="255" placeholder="Optional" class="sc-wide"></td></tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="sc-pane__foot"><button type="submit" class="btn btn-primary btn-sm">Save {{ $monthName }}</button><span class="sc-muted">Once a month, before payroll.</span></div>
        </form>
        @endif
    </div></section>

    {{-- Uploads --}}
    <section class="sc-pane card" data-pane="uploads"><div class="card-body">
        <div class="sc-pane__head">
            <div><h3 class="sc-h3">eZee Transaction Detail Reports</h3><p class="sc-help">Reports → Back Office → Transaction Detail Report, one Excel per property, weekly.</p></div>
            <details class="sc-why"><summary>How uploads work</summary><ul>
                <li>Overlapping dates are fine: the newest file replaces what it covers, so nothing counts twice and cancelled or shortened stays correct themselves.</li>
                <li>Months already reported to owners stay as paid; a paid night that later shows cancelled raises a clawback.</li>
                <li>Property is read from the file; pick it only if detection fails.</li>
            </ul></details>
        </div>
        @if(admin_can('sales.manage'))
        <form method="post" action="{{ route('admin.sales.upload') }}" enctype="multipart/form-data" class="sc-uploadrow">
            @csrf<input type="hidden" name="month" value="{{ $ym }}">
            <label class="sc-file"><input type="file" name="files[]" accept=".xlsx,.xls" multiple required><span>Choose Excel files</span></label>
            <select name="hotel"><option value="">Detect property</option>@foreach($hotels as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach</select>
            <button type="submit" class="btn btn-primary btn-sm" onclick="this.disabled=true;this.textContent='Uploading…';this.form.submit()">Upload</button>
        </form>
        @endif
        <div class="sc-cover">
            @foreach($hotels as $code => $name)@php $c = $coverage[$code] ?? null; $stale = !$c || \Carbon\Carbon::parse($c->last_day)->lt(now()->subDays(8)); @endphp
            <div class="sc-cover__item {{ $stale ? 'is-stale' : '' }}"><span>{{ $name }}</span><b>{{ $c ? 'to ' . $fmtY($c->last_day) : 'never uploaded' }}</b></div>
            @endforeach
        </div>
        @if(count($uploads))
        <table class="sc-table sc-small sc-uploads">
            <thead><tr><th>When</th><th>Property</th><th>Period</th><th class="num">Rows</th><th class="num">Kept paid</th><th class="num">Replaced</th><th class="num">Clawbacks</th><th></th></tr></thead>
            <tbody>@foreach($uploads as $u)<tr><td class="text-nowrap">{{ \Carbon\Carbon::parse($u->created_at)->format('j M H:i') }}</td><td>{{ $hotels[$u->hotel_code] ?? $u->hotel_code }}<div class="sc-muted sc-file-name">{{ $u->filename }}</div></td><td class="text-nowrap">{{ $fmt($u->period_from) }} → {{ $fmt($u->period_to) }}</td><td class="num">{{ $u->rows_stored }}</td><td class="num">{{ $u->rows_skipped_locked ?: '—' }}</td><td class="num">{{ $u->rows_replaced ?: '—' }}</td><td class="num">{{ $u->clawbacks ?: '—' }}</td><td class="num">@if(admin_can('sales.manage') && DB::table('sales_transactions')->where('upload_id', $u->id)->exists())<form method="post" action="{{ route('admin.sales.upload.delete', $u->id) }}" onsubmit="return confirm('This deletes the {{ number_format($u->rows_stored) }} stored rows from this file ({{ $hotels[$u->hotel_code] ?? $u->hotel_code }}, {{ $fmt($u->period_from) }} to {{ $fmt($u->period_to) }}). The month will show nothing until the file is uploaded again. Continue?')">@csrf<button class="sc-link">Remove</button></form>@endif</td></tr>@endforeach</tbody>
        </table>
        @endif
        <div class="sc-pane__foot sc-sop">
            <span>{!! $sopLink !!}</span>
            @if(admin_can('sales.manage'))
            <form method="post" action="{{ route('admin.sales.sop.upload') }}" enctype="multipart/form-data" class="sc-inline">
                @csrf<label class="sc-file sc-file--sm"><input type="file" name="sop" accept="application/pdf" required onchange="this.form.submit()"><span>Replace PDF</span></label>
            </form>
            @endif
        </div>
    </div></section>

    {{-- Sales persons --}}
    <section class="sc-pane card" data-pane="people"><div class="card-body">
        <div class="sc-pane__head">
            <div><h3 class="sc-h3">Sales persons</h3><p class="sc-help">Tie each eZee name to a login; that person then sees only their own commission.</p></div>
            <details class="sc-why"><summary>Dates</summary><ul>
                <li><b>Confirmed on</b> drives eligibility (§3): blank = probation, nothing paid; first three complete months after it = RM15,000 minimum waived.</li>
                <li><b>Left on</b> stops the scheme from that date.</li>
            </ul></details>
        </div>
        <table class="sc-table sc-people">
            <thead><tr><th>Name in eZee</th><th>Login</th><th>Confirmed on</th><th>Left on</th><th></th></tr></thead>
            <tbody>
            @foreach($persons as $sp)
                <tr>
                    <td><b>{{ $sp->name }}</b><div class="sc-muted">@if(!$sp->user_id)<span class="sc-warn">no login</span>@elseif(empty($sp->admin_role))super admin, sees everyone@else{{ ucfirst($sp->admin_role) }}@endif</div></td>
                    @if(admin_can('sales.manage'))
                    <form method="post" action="{{ route('admin.sales.person') }}" id="sp{{ $sp->id }}">@csrf<input type="hidden" name="id" value="{{ $sp->id }}"></form>
                    <td><select name="user_id" form="sp{{ $sp->id }}"><option value="">— not tied —</option>@foreach($staff as $u)<option value="{{ $u->id }}" @selected((int) $sp->user_id === (int) $u->id)>{{ $u->name }} · {{ $u->email }}</option>@endforeach</select></td>
                    <td><input type="date" name="confirmed_on" form="sp{{ $sp->id }}" value="{{ $sp->confirmed_on }}" data-raw-dates></td>
                    <td><input type="date" name="left_on" form="sp{{ $sp->id }}" value="{{ $sp->left_on }}" data-raw-dates></td>
                    <td class="num"><button type="submit" form="sp{{ $sp->id }}" class="btn btn-secondary btn-sm">Save</button></td>
                    @else
                    <td>{{ $sp->email ?: '—' }}</td><td>{{ $fmtY($sp->confirmed_on) }}</td><td>{{ $fmtY($sp->left_on) }}</td><td></td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    </div></section>

    {{-- Adjustments --}}
    <section class="sc-pane card" data-pane="adjust"><div class="card-body">
        <div class="sc-pane__head">
            <div><h3 class="sc-h3">Adjustments, {{ $monthName }}</h3><p class="sc-help">Change this month's 70% payout. Clawbacks (§12) appear here automatically.</p></div>
        </div>
        <table class="sc-table sc-small"><tbody>
        @forelse($adjustments as $a)
            <tr><td>{{ $a->name }}</td><td class="num">{{ $rm($a->amount) }}</td><td class="sc-note">{{ $a->reason }}</td><td class="num">@if(admin_can('sales.manage') && !$final)<form method="post" action="{{ route('admin.sales.adjustment.delete', $a->id) }}" onsubmit="return confirm('Remove this adjustment?')">@csrf<button class="sc-link">Remove</button></form>@endif</td></tr>
        @empty
            <tr><td colspan="4" class="sc-empty">None this month.</td></tr>
        @endforelse
        </tbody></table>
        @if(admin_can('sales.manage') && !$final)
        <form method="post" action="{{ route('admin.sales.adjustment') }}" class="sc-inline sc-pane__foot">
            @csrf<input type="hidden" name="ym" value="{{ $ym }}">
            <select name="sales_person_id" required>@foreach($persons as $sp)<option value="{{ $sp->id }}">{{ $sp->name }}</option>@endforeach</select>
            <input type="number" name="amount" step="0.01" placeholder="Amount, − to deduct" required style="width:170px">
            <input type="text" name="reason" placeholder="Reason" maxlength="255" required style="flex:1;min-width:200px">
            <button type="submit" class="btn btn-primary btn-sm">Add</button>
        </form>
        @endif
    </div></section>

    {{-- Deferred payouts --}}
    <section class="sc-pane card" data-pane="deferred"><div class="card-body">
        <div class="sc-pane__head">
            <div><h3 class="sc-h3">Deferred 30% payouts</h3><p class="sc-help">Record each payout made after the year-end audit (§11.2). Pick a person above to see their balance by year.</p></div>
        </div>
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
    </div></section>
</div>
<script>
(function(){var w=document.getElementById('setup');if(!w)return;var tabs=w.querySelectorAll('.sc-tab'),panes=w.querySelectorAll('.sc-pane');function show(n){tabs.forEach(function(t){t.classList.toggle('is-on',t.dataset.tab===n)});panes.forEach(function(p){p.hidden=p.dataset.pane!==n});try{localStorage.setItem('sc-tab',n)}catch(e){}}
tabs.forEach(function(t){t.addEventListener('click',function(){show(t.dataset.tab)})});var h=location.hash.replace('#','');var saved=null;try{saved=localStorage.getItem('sc-tab')}catch(e){}show(h==='kpi'?'kpi':(w.dataset.tab==='kpi'?'kpi':(saved||w.dataset.tab)));document.querySelectorAll('a[href="#kpi"]').forEach(function(a){a.addEventListener('click',function(){show('kpi')})});})();
</script>
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
.sc-banner{background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:8px 12px;margin-bottom:12px;font-size:12.5px;color:#78350f}.sc-banner a{font-weight:600;color:#92400e}
.sc-explain{background:#f8fafc;border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:8px 12px;margin-bottom:12px;font-size:12.5px}
.sc-explain summary{cursor:pointer;font-weight:600}.sc-explain ul{margin:6px 0 2px;padding-left:18px;line-height:1.5}.sc-explain li{margin:3px 0}
.sc-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:12px}.sc-grid--own{grid-template-columns:1fr}.sc-span2{grid-column:1/-1}
@media (max-width:900px){.sc-grid,.sc-grid--own{grid-template-columns:1fr}}
.sc-h3{margin:0 0 8px;font-size:14px}
.card .card-body{padding:12px 14px}
.sc-table{width:100%;border-collapse:collapse;font-size:12.5px;line-height:1.35}.sc-table th,.sc-table td{padding:5px 8px;border-bottom:1px solid var(--border,#e5e7eb);text-align:left;vertical-align:top}.sc-table th{font-size:11px;text-transform:uppercase;letter-spacing:.03em;color:var(--text-secondary);white-space:nowrap}
.sc-table .num{text-align:right;white-space:nowrap}.sc-table tfoot th{text-transform:none;font-size:12.5px;color:inherit;border-top:2px solid var(--border,#e5e7eb)}
.sc-small{font-size:12px}.sc-small th,.sc-small td{padding:4px 6px}
.sc-stays__table{min-width:900px}.sc-stays__table td.mono,.sc-stays__table td.num,.sc-stays__table td.sc-stay{white-space:nowrap}.sc-stays__table td.sc-note{min-width:180px}
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
.sc-setup{margin-top:14px;font-size:12.5px}
.sc-tabs{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:10px;border-bottom:1px solid var(--border,#e5e7eb)}
.sc-tab{position:relative;background:none;border:0;border-bottom:2px solid transparent;margin-bottom:-1px;padding:8px 12px;font:inherit;font-size:13px;font-weight:500;color:var(--text-secondary);cursor:pointer}.sc-tab:hover{color:inherit}.sc-tab.is-on{color:#0f766e;border-bottom-color:#0f766e;font-weight:600}
.sc-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#f59e0b;margin-left:5px;vertical-align:middle}
.sc-pane[hidden]{display:none}
.sc-pane__head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:10px}.sc-pane__head .sc-h3{margin-bottom:2px}.sc-pane__head .sc-help{margin:0}
.sc-why{flex:none;font-size:12px;max-width:360px}.sc-why>summary{cursor:pointer;color:#0f766e;font-weight:600;list-style:none;text-align:right}.sc-why>summary::-webkit-details-marker{display:none}.sc-why>summary::before{content:'? ';font-weight:700}.sc-why[open]>summary{margin-bottom:4px}.sc-why ul{margin:0;padding-left:16px;line-height:1.45;color:var(--text-secondary)}.sc-why li{margin:2px 0}
.sc-pane__foot{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:10px}
.sc-kpi .ctr{text-align:center}.sc-kpi input[type=number]{width:64px;text-align:center}.sc-kpi .sc-wide{width:100%;min-width:160px}.sc-kpi input[type=checkbox]{width:15px;height:15px}
.sc-uploadrow{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px}.sc-uploadrow select{padding:6px 8px;font-size:12.5px;border:1px solid var(--border,#e5e7eb);border-radius:8px;background:#fff}
.sc-file{position:relative;display:inline-flex;align-items:center;padding:6px 12px;border:1px dashed #94a3b8;border-radius:8px;font-size:12.5px;font-weight:600;color:#334155;background:#f8fafc;cursor:pointer}.sc-file:hover{background:#f1f5f9}.sc-file input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%}.sc-file--sm{padding:3px 10px;font-weight:500;border-style:solid}
.sc-cover{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;margin-bottom:12px}.sc-cover__item{border:1px solid var(--border,#e5e7eb);border-radius:8px;padding:6px 10px;display:grid;gap:1px}.sc-cover__item span{font-size:11.5px;color:var(--text-secondary)}.sc-cover__item b{font-size:12.5px;font-weight:600}.sc-cover__item.is-stale{border-color:#fcd34d;background:#fffbeb}.sc-cover__item.is-stale b{color:#b45309}
.sc-uploads .sc-file-name{font-size:11px;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.sc-link{background:none;border:0;padding:0;font:inherit;font-size:12px;color:#0f766e;cursor:pointer;text-decoration:underline}
.sc-sop{justify-content:space-between;border-top:1px solid var(--border,#e5e7eb);padding-top:10px}
.sc-people select,.sc-people input[type=date]{padding:5px 8px;font-size:12.5px;border:1px solid var(--border,#e5e7eb);border-radius:8px;background:#fff;max-width:100%}.sc-people select{min-width:220px}
@media (max-width:760px){.sc-pane__head{flex-direction:column}.sc-why>summary{text-align:left}}
</style>
@endpush
