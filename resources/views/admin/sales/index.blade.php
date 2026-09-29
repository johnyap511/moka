@extends('admin.layout')
@section('title', $own ? 'My Commission' : 'Sales Commission')
@section('page-title', $own ? 'My Commission' : 'Sales Commission')

@section('content')
@php
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M') : '—';
    $fmtY = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M Y') : '—';
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
        <p class="sc-sub">Front Desk Commission, Bonus &amp; KPI SOP v2.0: 2% of recognised direct-booking room charges (before SST), KPI gates, team bonus, 70% paid with the following month's salary and 30% deferred to after the year's audit. {!! $sopLink !!}</p>
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
    <div class="sc-tile sc-tile--main"><span>Payable with the {{ $nextMonth }} salary</span><b>{{ $rm($st->pay_now) }}</b><em class="sc-chip {{ $final ? 'sc-chip--final' : 'sc-chip--prov' }}">{{ $final ? 'Final' : 'Provisional' }}</em><em>70% of commission and bonus{{ $st->adjustments != 0 ? ', ' . ($st->adjustments > 0 ? 'plus' : 'less') . ' adjustments ' . $rm(abs($st->adjustments)) : '' }}</em></div>
    <div class="sc-tile"><span>Recognised direct sales, {{ $monthName }}</span><b>{{ $rm($st->sales) }}</b>
        <div class="sc-bar"><i style="width:{{ min(100, $st->sales / 300) }}%"></i><u style="left:50%"></u><u style="left:66.7%"></u></div>
        <em>{{ $tierLabel($st->tier_reached) }}{{ $nextTier ? ' · ' . $rm0($nextTier[1] - $st->sales) . ' more to Tier ' . $nextTier[0] : ' · top tier' }}</em></div>
    <div class="sc-tile"><span>Personal commission (2%)</span><b>{{ $rm($st->personal) }}</b><em>{{ $gateText($st) }}{{ $st->pct < 1 && $st->gross > 0 ? ' · before KPI: ' . $rm($st->gross) : '' }}</em></div>
    <div class="sc-tile"><span>Team bonus share</span><b>{{ $rm($st->bonus) }}</b><em>@if($team['pool'])Tier {{ $team['tier'] }} pool {{ $rm0($team['pool']) }}: {{ $team['hit'] }} of {{ $team['counted'] }} counted staff reached it, shared by {{ $team['eligible'] }}@else No pool this month: under 80% of counted staff reached RM15,000 @endif </em></div>
    <div class="sc-tile"><span>Deferred this month (30%)</span><b>{{ $rm($st->deferred) }}</b><em>paid after the year's audited accounts</em></div>
    @if(isset($deferred) && $deferred)
    <div class="sc-tile"><span>Deferred outstanding, {{ $deferred[0]->year }}</span><b>{{ $rm($deferred[0]->outstanding) }}</b><em>{{ $rm($deferred[0]->accrued) }} accrued so far ({{ $rm($deferred[0]->final) }} from final months), {{ $rm($deferred[0]->paid) }} paid</em></div>
    @endif
</div>
<p class="sc-muted" style="margin:-8px 0 14px">{{ $statusText[$st->status] ?? '' }}{{ $st->confirmed_on ? ' · confirmed ' . $fmtY($st->confirmed_on) : ($st->status === 'probation' ? ' · confirmation date not recorded' : '') }} · {{ $st->stays }} stay(s), {{ $st->nights }} night(s){{ $warnings ? ' · ' . $warnings . ' stay(s) on hold, ' . $rm($held) . ' not counted until the next report' : '' }}{{ $cross ? ' · ' . $cross . ' cross-month' : '' }}</p>
@else
<div class="sc-tiles">
    <div class="sc-tile sc-tile--main"><span>Payable with the {{ $nextMonth }} salary, everyone</span><b>{{ $rm($sum('pay_now')) }}</b><em class="sc-chip {{ $final ? 'sc-chip--final' : 'sc-chip--prov' }}">{{ $final ? 'Final' : 'Provisional' }} · {{ $payout }}</em></div>
    <div class="sc-tile"><span>Recognised direct sales</span><b>{{ $rm($sum('sales')) }}</b><em>{{ array_sum(array_column($data['people'], 'nights')) }} nights, {{ array_sum(array_column($data['people'], 'stays')) }} stays{{ $cross ? ', ' . $cross . ' cross-month' : '' }}</em></div>
    <div class="sc-tile"><span>Personal commission after KPIs</span><b>{{ $rm($sum('personal')) }}</b><em>before KPIs: {{ $rm($sum('gross')) }}</em></div>
    <div class="sc-tile"><span>Team bonus</span><b>{{ $rm($sum('bonus')) }}</b><em>@if($team['pool'])Tier {{ $team['tier'] }}, pool {{ $rm0($team['pool']) }}: {{ $team['hit'] }} of {{ $team['counted'] }} reached it, {{ $team['eligible'] }} eligible @else No pool: under 80% of {{ $team['counted'] }} counted staff reached RM15,000 @endif </em></div>
    <div class="sc-tile"><span>Deferred (30%)</span><b>{{ $rm($sum('deferred')) }}</b><em>paid after the year's audited accounts</em></div>
    <div class="sc-tile {{ $warnings ? 'sc-tile--warn' : '' }}"><span>On hold</span><b>{{ $warnings ? $rm($held) : '—' }}</b><em>{{ $warnings ? $warnings . ' stay(s) changed in eZee since the last report' : 'nothing changed since the last report' }}</em></div>
</div>
@endif

@if($own)
<details class="sc-explain" {{ count($data['stays']) ? '' : 'open' }}>
    <summary>How this is worked out, and what to do if a figure looks wrong</summary>
    <ul>
        <li><b>Recognised sales</b> are the room charges before SST of your direct bookings (Walk In, WhatsApp, phone, monthly and long-term rentals) that carry your name as Sales Person in eZee. OTA and agent bookings never count (SOP §2). Deposits, cleaning fees, early check-in, late check-out and other charges are not room charges.</li>
        <li><b>Nights are counted by the date eZee posted them.</b> A stay across two months is split: the earlier nights were in last month's figures, the later ones are here. Such stays are marked <em class="sc-badge">cross-month</em> with the split shown.</li>
        <li><b>Commission is 2%</b> of recognised sales (SOP §6), then the KPI gates apply in order (SOP §8.5): at least RM15,000 of recognised sales from your fourth month after confirmation, one recorded absence keeps 50%, two or more absences, an absence without approval, or more than 120 minutes of lateness in the month means nil.</li>
        <li><b>Team bonus</b> (SOP §9): when at least 80% of counted staff reach a tier (RM15k, 20k, 30k) a pool of RM500, 1,000 or 2,000 is shared equally by everyone who reached RM15,000 and passed the KPIs.</li>
        <li><b>70% is paid with the following month's salary; 30% is deferred</b> (SOP §11), accumulates through the calendar year and is paid after the year's audited accounts. Your deferred balance restarts each January; past years stay on record below.</li>
        <li><b>A stay cancelled after it was paid</b> is clawed back from your next payout (SOP §12). It shows as an adjustment with the reason.</li>
        <li><b>Figures come from eZee's Transaction Detail Report,</b> uploaded weekly. A stay missing? Check in eZee that the <b>Sales Person</b> on that reservation is your name, then ask the office to re-upload. Attendance and lateness come from HR's records.</li>
        <li><b>Provisional</b> months can still move until the month is closed; <b>Final</b> months do not change. <b>Found a difference?</b> Tell the Operations Manager the RES or folio number and the night, within 7 days of your statement (SOP §14).</li>
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
                <thead><tr><th>Sales person</th><th>Status</th><th class="num">Recognised sales</th><th>Tier</th><th>KPI result</th><th class="num">Personal 2%</th><th class="num">Team bonus</th><th class="num">Adjustments</th><th class="num">Pay now 70%</th><th class="num">Deferred 30%</th></tr></thead>
                <tbody>
                @forelse($rows as $r)
                    <tr class="{{ $person === $r->name ? 'sc-row-current' : '' }}"><td><a href="?month={{ $ym }}&person={{ urlencode($r->name) }}">{{ $r->name }}</a></td><td class="sc-muted">{{ ucfirst($r->status) }}{{ !$r->kpi->entered && $r->status !== 'probation' ? ' · KPI not entered' : '' }}</td><td class="num">{{ $rm($r->sales) }}</td><td>{{ $tierLabel($r->tier_reached) }}</td><td class="sc-note">{{ $gateText($r) }}</td>
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
                <thead><tr><th>Month</th><th class="num">Recognised sales</th><th>Tier</th>@if($person)<th>KPI</th>@endif<th class="num">Personal</th><th class="num">Team bonus</th><th class="num">Adjust.</th><th class="num">Paid 70%</th><th class="num">Deferred 30%</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($history as $h)
                    <tr class="{{ $h->ym === $ym ? 'sc-row-current' : '' }}"><td><a href="{{ $q($h->ym) }}">{{ $mon($h->ym) }}</a></td><td class="num">{{ $rm($h->sales) }}</td><td>{{ $tierLabel($h->tier) }}</td>@if($person)<td class="sc-muted">{{ $h->pct === null ? '' : ($h->pct == 1 ? 'met' : ($h->pct == 0.5 ? '50%' : 'nil')) }}</td>@endif<td class="num">{{ $rm($h->personal) }}</td><td class="num">{{ $rm($h->bonus) }}</td><td class="num">{{ $h->adjustments != 0 ? $rm($h->adjustments) : '—' }}</td><td class="num"><b>{{ $rm($h->pay_now) }}</b></td><td class="num">{{ $rm($h->deferred) }}</td><td><span class="sc-chip {{ $h->final ? 'sc-chip--final' : 'sc-chip--prov' }}">{{ $h->final ? 'Final' : 'Provisional' }}</span> <span class="sc-muted">{{ $h->payout }}</span></td></tr>
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
            <h3 class="sc-h3">Deferred commission (30%), by year</h3>
            <p class="sc-help">Accumulates through the calendar year and is paid within 60 days of the audited accounts, subject to SOP §11.2. Only final months count as owed; provisional months are shown for reference.</p>
            <table class="sc-table sc-small">
                <thead><tr><th>Year</th><th class="num">Accrued</th><th class="num">From final months</th><th class="num">Paid out</th><th class="num">Outstanding</th><th>Payouts</th></tr></thead>
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
            <h3 class="sc-h3">Stays in {{ $monthName }}{{ $person ? ', ' . $person : '' }}</h3>
            <span class="sc-muted">{{ count($data['stays']) }} stay(s). A <em class="sc-badge">cross-month</em> stay shows how its nights split across months.</span>
        </div>
        <div class="table-wrap">
        <table class="sc-table sc-stays__table" data-enhance="search" data-search-placeholder="Search guest, RES, folio or unit">
            <thead><tr>@if(!$own)<th>Sales person</th>@endif<th>Guest</th><th>RES / Folio</th><th>Property · Unit</th><th>Stay (check-in → out)</th><th class="num">Nights this month</th><th class="num">Room charges</th><th class="num">2%</th><th>Note</th></tr></thead>
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
                    <td class="num">@if($s->warn)<span class="sc-warn">on hold</span>@else{{ $rm($s->commission) }}@endif</td>
                    <td class="sc-note">
                        @if($s->warn)<div class="sc-warn">{{ $s->warn }}</div>@endif
                        @foreach($s->other_months as $o)
                            <div>{{ $o->before ? 'Also' : 'Then' }} <b>{{ $o->nights }}</b> night{{ $o->nights == 1 ? '' : 's' }} in {{ $mon($o->ym) }}: {{ $rm($o->commission) }} {{ $o->before ? 'was in the ' . $mon($o->ym) . ' figures' : 'goes into the ' . $mon($o->ym) . ' figures' }}</div>
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
<p class="sc-foot">Reports uploaded up to: @foreach($hotels as $code => $name){{ $name }} {{ isset($coverage[$code]) ? $fmtY($coverage[$code]->last_day) : 'never' }}{{ $loop->last ? '' : ' · ' }}@endforeach · {!! $sopLink !!}</p>
@else
<details class="sc-setup" {{ !$final && count(array_filter($rows, fn ($r) => !$r->kpi->entered && $r->status !== 'probation')) ? 'open' : '' }}>
    <summary>KPI inputs for {{ $monthName }} (attendance, lateness, employment)</summary>
    <div class="card" style="margin-top:12px"><div class="card-body">
        <p class="sc-help">From HR's records, per SOP §8: recorded absences after any approved Medical Exception (§8.3), whether any absence was without approval, accumulated lateness in minutes, and whether the person is under a disciplinary sanction affecting bonus. "Employed full month" decides who is counted in the 80% team threshold (§3.3). Confirmation dates are set under "Who can see what".{{ $final ? ' This month is final; inputs are read-only.' : '' }}</p>
        @if(admin_can('sales.manage') && !$final)
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
            <thead><tr><th>When</th><th>Property</th><th>File</th><th>Period</th><th class="num">Rows stored</th><th class="num">Already final (kept as paid)</th><th class="num">Replaced</th><th class="num">Clawbacks</th></tr></thead>
            <tbody>@foreach($uploads as $u)<tr><td class="text-nowrap">{{ \Carbon\Carbon::parse($u->created_at)->format('j M H:i') }}</td><td>{{ $hotels[$u->hotel_code] ?? $u->hotel_code }}</td><td class="sc-muted">{{ $u->filename }}</td><td class="text-nowrap">{{ $fmt($u->period_from) }} → {{ $fmt($u->period_to) }}</td><td class="num">{{ $u->rows_stored }}</td><td class="num">{{ $u->rows_skipped_locked }}</td><td class="num">{{ $u->rows_replaced }}</td><td class="num">{{ $u->clawbacks ?? 0 }}</td></tr>@endforeach</tbody>
        </table>
    </div></div>
    @endif
</details>
@endif
@endsection

@push('scripts')
<style>
.sc-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;margin-bottom:14px}
.sc-head h1{margin:0 0 4px}.sc-sub{margin:0;font-size:13px;color:var(--text-secondary);max-width:720px;line-height:1.5}
.sc-nav{display:flex;align-items:center;gap:6px}.sc-nav__btn{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid var(--border,#e5e7eb);border-radius:8px;background:#fff;font-size:18px;color:inherit;text-decoration:none}.sc-nav__btn:hover{background:#f8fafc}
.sc-nav__form{display:flex;gap:6px}.sc-nav__form input,.sc-nav__form select{padding:7px 9px;font-size:13px;border:1px solid var(--border,#e5e7eb);border-radius:8px;background:#fff}
.sc-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:16px}
@media (max-width:900px){.sc-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}@media (max-width:560px){.sc-tiles{grid-template-columns:1fr}}
.sc-tile{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:14px 16px;display:grid;gap:4px;align-content:start}
.sc-tile span{font-size:12px;color:var(--text-secondary)}.sc-tile b{font-size:22px;line-height:1.2}.sc-tile em{font-style:normal;font-size:12px;color:var(--text-secondary);line-height:1.4}
.sc-tile--main{background:#0f766e;border-color:#0f766e;color:#fff}.sc-tile--main span,.sc-tile--main em{color:rgba(255,255,255,.85)}.sc-tile--main b{font-size:26px}
.sc-tile--warn{border-color:#fcd34d;background:#fffbeb}
.sc-bar{position:relative;height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden;margin:4px 0}.sc-bar i{position:absolute;left:0;top:0;bottom:0;background:#0f766e;border-radius:999px}.sc-bar u{position:absolute;top:0;bottom:0;width:2px;background:#fff}
.sc-chip{display:inline-block;font-size:11.5px;padding:2px 9px;border-radius:999px;font-weight:600;font-style:normal;width:max-content}.sc-chip--final{background:#dcfce7;color:#166534}.sc-chip--prov{background:#fef3c7;color:#92400e}
.sc-tile--main .sc-chip--prov{background:rgba(255,255,255,.18);color:#fff}.sc-tile--main .sc-chip--final{background:rgba(255,255,255,.25);color:#fff}
.sc-explain{background:#f8fafc;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:10px 14px;margin-bottom:16px;font-size:13px}
.sc-explain summary{cursor:pointer;font-weight:600}.sc-explain ul{margin:8px 0 2px;padding-left:18px;line-height:1.55}.sc-explain li{margin:4px 0}
.sc-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px}.sc-grid--own{grid-template-columns:1fr}.sc-span2{grid-column:1/-1}
@media (max-width:900px){.sc-grid{grid-template-columns:1fr}}
.sc-h3{margin:0 0 10px;font-size:15px}
.sc-table{width:100%;border-collapse:collapse;font-size:13px}.sc-table th,.sc-table td{padding:8px;border-bottom:1px solid var(--border,#e5e7eb);text-align:left;vertical-align:top}.sc-table th{font-size:11.5px;text-transform:uppercase;letter-spacing:.03em;color:var(--text-secondary);white-space:nowrap}
.sc-table .num{text-align:right;white-space:nowrap}.sc-table tfoot th{text-transform:none;font-size:13px;color:inherit;border-top:2px solid var(--border,#e5e7eb)}
.sc-small{font-size:12.5px}.sc-small th,.sc-small td{padding:6px}
.sc-kpi input[type=number],.sc-kpi input[type=text]{padding:4px 6px;font-size:12.5px;border:1px solid var(--border,#e5e7eb);border-radius:6px}
.sc-muted{font-size:12px;color:var(--text-secondary)}.sc-empty{color:var(--text-secondary);padding:18px!important}
.sc-help{font-size:12.5px;color:var(--text-secondary);line-height:1.5;margin:0 0 10px}
.sc-upload{display:grid;gap:8px;font-size:13px}.sc-upload select,.sc-inline select,.sc-inline input{padding:5px 8px;font-size:12.5px;max-width:100%;border:1px solid var(--border,#e5e7eb);border-radius:6px}
.sc-inline{display:flex;gap:6px;flex-wrap:wrap;align-items:center;font-size:12.5px}
.sc-warn{color:#b45309;font-weight:600}.sc-row-warn td{background:#fffbeb}.sc-row-current td{background:#f1f5f9}
.sc-badge{display:inline-block;font-style:normal;font-size:11px;font-weight:600;color:#1d4ed8;background:#dbeafe;border-radius:999px;padding:1px 8px}
.sc-note{font-size:12px;line-height:1.45;max-width:320px}.sc-note div{margin-bottom:2px}
.sc-stays__head{display:flex;justify-content:space-between;gap:12px;align-items:baseline;flex-wrap:wrap;margin-bottom:6px}
.sc-foot{font-size:12px;color:var(--text-secondary);margin-top:12px}
.sc-setup{margin-top:4px;font-size:13px}.sc-setup>summary{cursor:pointer;font-weight:600;padding:8px 0;color:var(--text-secondary)}
</style>
@endpush
