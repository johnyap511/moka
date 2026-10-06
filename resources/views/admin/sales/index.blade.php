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
        if (!$r->gates['min_sales']) $fails[] = 'below RM15k';
        if (!$r->gates['approved']) $fails[] = 'absence without approval';
        if (!$r->gates['attendance']) $fails[] = $r->kpi->absences . ' absences';
        if (!$r->gates['punctual']) $fails[] = $r->kpi->lateness_min . ' min late';
        if ($fails) return 'Nil: ' . implode(', ', $fails);
        return $r->kpi->absences === 1 ? '50%: one absence' : 'KPIs met';
    };
    $tierLabel = fn ($t) => $t ? 'Tier ' . $t : 'Below Tier 1';
    $sopLink = $sop ? '<a href="' . route('admin.sales.sop') . '" target="_blank">Read the Commission SOP (v2.0)</a>' : 'SOP not uploaded yet';
@endphp

@php
    $leftNames = $own ? [] : $persons->filter(fn ($sp) => $sp->left_on && substr($sp->left_on, 0, 7) < $ym)->pluck('name')->all();
    if ($leftNames) { $data['stays'] = array_values(array_filter($data['stays'], fn ($s) => !in_array($s->sales_person, $leftNames, true))); $cross = count(array_filter($data['stays'], fn ($s) => $s->cross)); }
    $mine = $own ? ($data['people'][0]->name ?? $person) : $person;
    $byPerson = $person ? null : collect($data['stays'])->groupBy('sales_person');
    $noDates = $own ? [] : array_map(fn ($r) => $r->name, array_filter($rows, fn ($r) => !$r->confirmed_on));
    $kpiMissingTop = $own ? 0 : count(array_filter($rows, fn ($r) => !$r->kpi->entered && $r->status !== 'probation'));
    $statusChip = ['probation' => ['Probation', 'sc-st--prob'], 'transition' => ['First 3 months', 'sc-st--trans'], 'full' => ['Confirmed', 'sc-st--full'], 'left' => ['Left', 'sc-st--left']];
    $tierPct = fn ($sales) => min(100, $sales / 300);
    $tierCls = fn ($t) => $t >= 3 ? 'sc-bar--t3' : ($t >= 1 ? 'sc-bar--t1' : 'sc-bar--t0');
    $teamLine = function () use ($team, $rm0) {
        if ($team['pool']) return 'Tier ' . $team['tier'] . ' pool ' . $rm0($team['pool']) . ' unlocked · ' . $team['hit'] . ' of ' . $team['counted'] . ' reached it';
        $hit15 = count(array_filter($team['rows'], fn ($r) => $r->sales >= 15000));
        $gap = max(0, ($team['needed'] ?? 0) - $hit15);
        return $hit15 . ' of ' . $team['counted'] . ' at RM15k · ' . ($gap ? $gap . ' more unlocks the RM500 pool' : 'pool needs ' . ($team['needed'] ?? 0));
    };
    $kpiChip = function ($txt) { if ($txt === '') return ''; $c = str_starts_with($txt, 'Nil') || str_starts_with($txt, 'Not') || str_starts_with($txt, 'Left') ? 'sc-kpi--nil' : (str_starts_with($txt, '50%') ? 'sc-kpi--half' : 'sc-kpi--ok'); return '<span class="sc-kpi ' . $c . '">' . e($txt) . '</span>'; };
    $hasBonus = $own ? false : (bool) count(array_filter($rows, fn ($r) => $r->bonus > 0));
    $hasAdj = $own ? false : (bool) count(array_filter($rows, fn ($r) => $r->adjustments != 0));
@endphp
<div class="sc-head">
    <div>
        <h1>{{ $own ? 'My Commission' : 'Sales Commission' }}</h1>
        <p class="sc-sub">2% on direct bookings · 70% paid next month, 30% at year end · {!! $sopLink !!}</p>
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
<div class="sc-hero">
    <div class="sc-hero__main">
        <div class="sc-hero__who">{{ $mine }} · {{ $monthName }} <span class="sc-chip {{ $final ? 'sc-chip--final' : 'sc-chip--prov' }}">{{ $final ? 'Final' : 'Provisional' }}</span></div>
        <div class="sc-hero__amt"><span>{{ $nextMonth }} payout</span><b>{{ $rm($st->pay_now) }}</b></div>
        <div class="sc-hero__math"><span>{{ $rm($st->sales) }} sales</span><i>→</i><span>2% {{ $rm($st->gross) }}</span>@if($st->pct < 1)<i>→</i><span>after KPIs {{ $rm($st->personal) }}</span>@endif @if($st->bonus > 0)<i>+</i><span>bonus {{ $rm($st->bonus) }}</span>@endif @if($st->adjustments != 0)<i>{{ $st->adjustments > 0 ? '+' : '−' }}</i><span>adj. {{ $rm(abs($st->adjustments)) }}</span>@endif<i>→</i><span><b>70% now</b> · {{ $rm($st->deferred) }} at year end</span></div>
        <p class="sc-hero__meta">{{ $st->stays }} stays · {{ $st->nights }} nights{{ $warnings ? ' · ' . $warnings . ' on hold (' . $rm($held) . ')' : '' }}{{ $st->status === 'transition' ? ' · RM15k minimum waived (first 3 months)' : ($st->status === 'probation' ? ' · probation' : '') }}</p>
    </div>
    <div class="sc-hero__side">
        <div class="sc-kv sc-kv--wide">
            <div class="sc-kv__row"><span>Direct sales</span><b>{{ $rm($st->sales) }}</b></div>
            <div class="sc-track"><i style="width:{{ $tierPct($st->sales) }}%"></i><u style="left:50%"></u><u style="left:66.7%"></u><u style="left:100%"></u></div>
            <div class="sc-track__labels"><span style="left:50%">RM15k</span><span style="left:66.7%">20k</span><span style="left:100%">30k</span></div>
            <em>{{ $st->tier_reached >= 3 ? 'Tier 3 reached' : ($nextTier ? $rm0($nextTier[1] - $st->sales) . ' more to Tier ' . $nextTier[0] : '') }}</em>
        </div>
        <div class="sc-kv"><span>KPI</span>{!! $kpiChip($gateText($st) ?: 'KPIs met') !!}</div>
        <div class="sc-kv"><span>Team bonus</span><b>{{ $rm($st->bonus) }}</b><em>{{ $teamLine() }}</em></div>
        <div class="sc-kv sc-kv--wide"><div class="sc-kv__row"><span>Held to year end {{ isset($deferred) && $deferred ? $deferred[0]->year : '' }}</span><b>{{ isset($deferred) && $deferred ? $rm($deferred[0]->outstanding) : $rm($st->deferred) }}</b></div><em>{{ isset($deferred) && $deferred ? $rm($deferred[0]->paid) . ' paid out so far' : 'paid after the year-end audit' }}</em></div>
    </div>
</div>
@else
<div class="sc-tiles {{ $warnings ? '' : 'sc-tiles--5' }}">
    <div class="sc-tile sc-tile--main"><span>{{ $nextMonth }} payout · everyone</span><b>{{ $rm($sum('pay_now')) }}</b><em class="sc-chip {{ $final ? 'sc-chip--final' : 'sc-chip--prov' }}">{{ $final ? 'Final' : 'Provisional' }}</em></div>
    <div class="sc-tile"><span>Direct sales</span><b>{{ $rm($sum('sales')) }}</b><em>{{ array_sum(array_column($data['people'], 'stays')) }} stays · {{ array_sum(array_column($data['people'], 'nights')) }} nights</em></div>
    <div class="sc-tile"><span>Commission</span><b>{{ $rm($sum('personal')) }}</b><em>{{ $sum('gross') != $sum('personal') ? $rm($sum('gross')) . ' before KPIs' : '2%, all KPIs met' }}</em></div>
    <div class="sc-tile"><span>Team bonus</span><b>{{ $rm($sum('bonus')) }}</b><em>{{ $teamLine() }}</em></div>
    <div class="sc-tile"><span>Held to year end</span><b>{{ $rm($sum('deferred')) }}</b><em>30%, after audit</em></div>
    @if($warnings)<div class="sc-tile sc-tile--warn"><span>On hold</span><b>{{ $rm($held) }}</b><em>{{ $warnings }} stays changed in eZee</em></div>@endif
</div>
@endif

@if(!$own)
    @if($approval)
    <div class="sc-approved"><span class="sc-chip sc-chip--final">Approved</span> {{ $monthName }} was approved by {{ $approver ?: 'the office' }} on {{ \Carbon\Carbon::parse($approval->approved_at)->format('j M Y') }} — figures are final.
        @if(admin_is_super())<form method="post" action="{{ route('admin.sales.unapprove') }}" class="sc-inline" onsubmit="return confirm('Remove the approval for {{ $monthName }}? Figures go back to provisional and will recompute.')">@csrf<input type="hidden" name="ym" value="{{ $ym }}"><button class="sc-link">Undo</button></form>@endif
    </div>
    @elseif($ym < date('Y-m') && admin_can('sales.manage') && count($rows))
    <div class="sc-approve"><div><b>{{ $monthName }} is provisional.</b> <span class="sc-muted">Approve once KPI inputs and uploads are complete: figures freeze, uploads can no longer change this month, and the 30% is booked as owed.</span></div>
        <form method="post" action="{{ route('admin.sales.approve') }}" onsubmit="return confirm('Approve {{ $monthName }}? Payouts: {{ $rm($sum('pay_now')) }} now, {{ $rm($sum('deferred')) }} deferred. This locks the month.')">@csrf<input type="hidden" name="ym" value="{{ $ym }}"><button class="btn btn-primary btn-sm">Approve {{ $monthName }}</button></form>
    </div>
    @endif
@endif
@if(!$own && ($kpiMissingTop || $noDates))
<div class="sc-todo">
    <b>Before payroll:</b>
    @if($kpiMissingTop)<a href="#kpi" title="Until entered, attendance and punctuality count as met">KPI inputs</a>@endif
    @if($kpiMissingTop && $noDates)<span>·</span>@endif
    @if($noDates)<a href="#setup" data-open-tab="people" title="Blank dates count as confirmed">confirmation dates ({{ implode(', ', $noDates) }})</a>@endif
</div>
@endif

@if($own)
<details class="sc-explain">
    <summary>How it works</summary>
    <ul>
        <li><b>Direct sales</b>: room charges + early check-in / late check-out, before SST, on bookings tagged to you in eZee. Not OTA, not cleaning fees or deposits, not Extra Room stays.</li>
        <li><b>Nights count in the month eZee posts them</b> — a stay over month-end is split (<em class="sc-badge">cross-month</em>).</li>
        <li><b>2%</b>, then KPIs: RM15k minimum · 1 absence = 50% · 2+ absences, unapproved absence or 120+ min late = nil.</li>
        <li><b>Team bonus</b>: enough of the team at RM15k / 20k / 30k unlocks RM500 / 1,000 / 2,000, shared by those at RM15k with KPIs met.</li>
        <li><b>70% next month, 30% after the year-end audit.</b></li>
        <li><b>Something missing?</b> Check the Sales Person tag in eZee, then tell the Operations Manager the RES or folio within 7 days.</li>
    </ul>
</details>
@endif

<div class="sc-grid {{ $own ? 'sc-grid--own' : '' }}">
    @if(!$own && !$person)
    <div class="card sc-span2">
        <div class="card-body">
            <h3 class="sc-h3">By sales person, {{ $monthName }}</h3>
            <div class="table-wrap">
            <table class="sc-table sc-people-tbl">
                <thead><tr><th>Sales person</th><th class="num">Direct sales</th><th>Tier</th><th>KPI</th><th class="num">2%</th>@if($hasBonus)<th class="num">Bonus</th>@endif @if($hasAdj)<th class="num">Adj.</th>@endif<th class="num sc-focus">Paid 70%</th><th class="num">Deferred</th></tr></thead>
                <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td><a href="?month={{ $ym }}&person={{ urlencode($r->name) }}"><b>{{ $r->name }}</b></a><div class="sc-st {{ $statusChip[$r->status][1] ?? '' }}">{{ $statusChip[$r->status][0] ?? ucfirst($r->status) }}</div></td>
                        <td class="num">{{ $rm($r->sales) }}</td>
                        <td class="sc-tier"><div class="sc-bar sc-bar--sm {{ $tierCls($r->tier_reached) }}"><i style="width:{{ $tierPct($r->sales) }}%"></i><u style="left:50%"></u><u style="left:66.7%"></u></div><span>{{ $tierLabel($r->tier_reached) }}</span></td>
                        <td>{!! $kpiChip($gateText($r)) !!}</td>
                        <td class="num">{{ $rm($r->personal) }}</td>@if($hasBonus)<td class="num">{{ $r->bonus > 0 ? $rm($r->bonus) : '—' }}</td>@endif @if($hasAdj)<td class="num">{{ $r->adjustments != 0 ? $rm($r->adjustments) : '—' }}</td>@endif<td class="num sc-focus"><b>{{ $rm($r->pay_now) }}</b></td><td class="num sc-dim">{{ $rm($r->deferred) }}</td></tr>
                @empty
                    <tr><td colspan="9" class="sc-empty">No sales persons yet. Upload the reports for a month.</td></tr>
                @endforelse
                </tbody>
                @if(count($rows) > 1)
                <tfoot><tr><th>Total</th><th class="num">{{ $rm($sum('sales')) }}</th><th></th><th></th><th class="num">{{ $rm($sum('personal')) }}</th>@if($hasBonus)<th class="num">{{ $rm($sum('bonus')) }}</th>@endif @if($hasAdj)<th class="num">{{ $rm($sum('adjustments')) }}</th>@endif<th class="num sc-focus">{{ $rm($sum('pay_now')) }}</th><th class="num sc-dim">{{ $rm($sum('deferred')) }}</th></tr></tfoot>
                @endif
            </table>
            </div>
        </div>
    </div>
    @endif

    <div class="card {{ !(isset($deferred) && (count($deferred) > 1 || array_sum(array_column($deferred, 'paid')) > 0)) && !($statement && $statement->adjustment_rows->count()) ? 'sc-span2' : '' }}">
        <div class="card-body">
            <h3 class="sc-h3">Month by month{{ $person ? ', ' . $person : '' }}</h3>
            <div class="table-wrap">
            @php $hBonus = count(array_filter($history, fn ($h) => $h->bonus > 0)); $hAdj = count(array_filter($history, fn ($h) => $h->adjustments != 0)); @endphp
            <table class="sc-table sc-small">
                <thead><tr><th>Month</th><th class="num">Direct sales</th>@if($person)<th>KPI</th>@endif<th class="num">2%</th>@if($hBonus)<th class="num">Bonus</th>@endif @if($hAdj)<th class="num">Adj.</th>@endif<th class="num sc-focus">Paid 70%</th><th class="num">Deferred</th><th></th></tr></thead>
                <tbody>
                @forelse($history as $h)
                    <tr class="{{ $h->ym === $ym ? 'sc-row-current' : '' }}"><td><a href="{{ $q($h->ym) }}">{{ $mon($h->ym) }}</a></td><td class="num">{{ $rm($h->sales) }}</td>@if($person)<td>{!! $h->pct === null ? '' : $kpiChip($h->pct == 1 ? 'Met' : ($h->pct == 0.5 ? '50%' : 'Nil')) !!}</td>@endif<td class="num">{{ $rm($h->personal) }}</td>@if($hBonus)<td class="num">{{ $h->bonus > 0 ? $rm($h->bonus) : '—' }}</td>@endif @if($hAdj)<td class="num">{{ $h->adjustments != 0 ? $rm($h->adjustments) : '—' }}</td>@endif<td class="num sc-focus"><b>{{ $rm($h->pay_now) }}</b></td><td class="num sc-dim">{{ $rm($h->deferred) }}</td><td><span class="sc-chip {{ $h->final ? 'sc-chip--final' : 'sc-chip--prov' }}">{{ $h->final ? 'Final' : 'Provisional' }}</span></td></tr>
                @empty
                    <tr><td colspan="9" class="sc-empty">Nothing yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    @if(isset($deferred) && (count($deferred) > 1 || array_sum(array_column($deferred, 'paid')) > 0))
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

@php
$stayRow = function ($s) use ($person, $rm, $range, $fmt, $data) {
    $o = '<tr class="' . ($s->warn ? 'sc-row-warn' : '') . '">';
    $o .= '<td>' . e($s->guest) . '<div class="sc-muted">' . e($s->source) . '</div></td>';
    $o .= '<td class="mono">' . e($s->res_no ?: 'no RES') . '<div class="sc-muted">' . e($s->folio_no) . '</div></td>';
    $o .= '<td>' . e($s->room) . '<div class="sc-muted">' . e($s->property) . '</div></td>';
    $o .= '<td class="sc-stay">' . e($range($s->arrival, $s->departure)) . ($s->cross ? '<div><em class="sc-badge">cross-month</em></div>' : '') . '</td>';
    $o .= '<td class="num">' . $s->nights . '<div class="sc-muted">' . e($fmt($s->first_night) . ($s->nights > 1 ? ' – ' . $fmt($s->last_night) : '')) . '</div></td>';
    $o .= '<td class="num">' . $rm($s->net) . '</td>';
    $o .= '<td class="num sc-focus">' . ($s->warn ? '<span class="sc-warn">on hold</span>' : '<b>' . $rm($s->commission) . '</b>') . '</td>';
    $n = '';
    if ($s->warn) $n .= '<div class="sc-warn">' . e($s->warn) . '</div>';
    foreach ($s->other_months as $om) $n .= '<div>' . ($om->before ? 'Also' : 'Then') . ' ' . $om->nights . ' night' . ($om->nights == 1 ? '' : 's') . ' in ' . \Carbon\Carbon::parse($om->ym . '-01')->format('M') . ': ' . $rm($om->commission) . ($om->before ? ' (paid)' : '') . '</div>';
    if ($s->cross && !$s->other_months && $s->departure && $s->departure > $data['to']) $n .= '<div>Continues into ' . \Carbon\Carbon::parse($data['to'])->addMonth()->format('M') . '</div>';
    if ($s->cross && !$s->other_months && $s->arrival && $s->arrival < $data['from']) $n .= '<div>Started before ' . \Carbon\Carbon::parse($data['from'])->format('M') . '</div>';
    return $o . '<td class="sc-note">' . $n . '</td></tr>';
};
$stayHead = '<thead><tr><th>Guest</th><th>RES / Folio</th><th>Unit</th><th>Stay</th><th class="num">Nights</th><th class="num">Room charges</th><th class="num sc-focus">2%</th><th>Note</th></tr></thead>';
$PAGE = 25;
@endphp

<div class="card sc-stays">
    <div class="card-body">
        <div class="sc-stays__head">
            <h3 class="sc-h3">Stays, {{ $monthName }}{{ $person ? ' · ' . $person : '' }}</h3>
            <div class="sc-stays__tools"><input type="search" class="sc-search" placeholder="Search guest, RES, folio or unit" data-stay-search><span class="sc-muted">{{ count($data['stays']) }} stays{{ $cross ? ' · ' . $cross . ' cross-month' : '' }}</span></div>
        </div>
        @if(!count($data['stays']))
            <p class="sc-empty">Nothing for {{ $monthName }}.</p>
        @elseif($byPerson)
            @foreach($byPerson as $name => $list)
            <details class="sc-group" {{ $loop->first ? 'open' : '' }}>
                <summary><b>{{ $name }}</b><span>{{ $list->count() }} stays · {{ $list->sum('nights') }} nights</span><span class="num">{{ $rm($list->sum('net')) }}</span></summary>
                <div class="table-wrap"><table class="sc-table sc-stays__table" data-stay-table>{!! $stayHead !!}<tbody>
                @foreach($list as $i => $s){!! str_replace('<tr class="', '<tr class="' . ($loop->index >= $PAGE ? 'sc-more ' : ''), $stayRow($s)) !!}@endforeach
                </tbody></table></div>
                @if($list->count() > $PAGE)<button type="button" class="sc-showmore" data-show-more>Show all {{ $list->count() }}</button>@endif
            </details>
            @endforeach
        @else
            <div class="table-wrap"><table class="sc-table sc-stays__table" data-stay-table>{!! $stayHead !!}<tbody>
            @foreach($data['stays'] as $i => $s){!! str_replace('<tr class="', '<tr class="' . ($loop->index >= $PAGE ? 'sc-more ' : ''), $stayRow($s)) !!}@endforeach
            </tbody></table></div>
            @if(count($data['stays']) > $PAGE)<button type="button" class="sc-showmore" data-show-more>Show all {{ count($data['stays']) }}</button>@endif
        @endif
    </div>
</div>
<script>
(function(){
  document.querySelectorAll('[data-show-more]').forEach(function(b){b.addEventListener('click',function(){var t=b.previousElementSibling.querySelector('table');t.querySelectorAll('.sc-more').forEach(function(r){r.classList.remove('sc-more')});b.remove();});});
  var q=document.querySelector('[data-stay-search]');if(q){q.addEventListener('input',function(){var v=q.value.trim().toLowerCase();document.querySelectorAll('[data-stay-table] tbody tr').forEach(function(r){var hit=!v||r.textContent.toLowerCase().indexOf(v)>-1;r.style.display=hit?'':'none';if(v&&hit)r.classList.remove('sc-more');});document.querySelectorAll('.sc-group').forEach(function(g){if(v)g.open=true;});});}
  document.querySelectorAll('[data-open-tab]').forEach(function(a){a.addEventListener('click',function(){var t=document.querySelector('.sc-tab[data-tab="'+a.dataset.openTab+'"]');if(t)t.click();});});
})();
</script>

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
                <li><b>Left on</b>: from the following month the person is off the page and out of the team count; stays still tagged to them in eZee after that date show as nil.</li>
            </ul></details>
        </div>
        @php $gone = $persons->filter(fn ($sp) => $sp->left_on && $sp->left_on <= now()->toDateString()); $here = $persons->reject(fn ($sp) => $sp->left_on && $sp->left_on <= now()->toDateString()); @endphp
        <table class="sc-table sc-people">
            <thead><tr><th>Name in eZee</th><th>Login</th><th>Confirmed on</th><th>Left on</th><th></th></tr></thead>
            <tbody>
            @foreach($here as $sp)@include('admin.sales._person_row', ['sp' => $sp])@endforeach
            @if(!$here->count())<tr><td colspan="5" class="sc-empty">No current sales persons. Names appear here automatically from the first eZee report that carries them.</td></tr>@endif
            </tbody>
        </table>
        @if($gone->count())
        <details class="sc-adv" style="margin-top:10px"><summary>Left the company ({{ $gone->count() }}) — kept for past months</summary>
        <table class="sc-table sc-people sc-small" style="margin-top:6px">
            <thead><tr><th>Name in eZee</th><th>Login</th><th>Confirmed on</th><th>Left on</th><th></th></tr></thead>
            <tbody>@foreach($gone as $sp)@include('admin.sales._person_row', ['sp' => $sp])@endforeach</tbody>
        </table>
        </details>
        @endif
        <p class="sc-help" style="margin-top:10px">New sales persons are added automatically from the first uploaded eZee report that carries their name, and tied to a login with the same name@homemoka.com. To remove someone, set their <b>Left on</b> date: from the next month they disappear from the page and the team count; their past months stay. Deactivating a login on Users → Admin sets this date for you.</p>
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
        @php $yr = (int) substr($ym, 0, 4); @endphp
        <div class="sc-pane__head">
            <div><h3 class="sc-h3">Deferred 30%, {{ $yr }}</h3><p class="sc-help">Accrues automatically from each approved month. After the year-end audit, pay the balance and record it here — the amount is filled in for you.</p></div>
        </div>
        <table class="sc-table sc-small">
            <thead><tr><th>Sales person</th><th class="num">Accrued</th><th class="num">Approved months</th><th class="num">Paid</th><th class="num sc-focus">Owed</th><th>Record payout</th></tr></thead>
            <tbody>
            @forelse($deferredYear as $d)
                <tr>
                    <td><b>{{ $d->name }}</b></td>
                    <td class="num">{{ $rm($d->accrued) }}</td>
                    <td class="num">{{ $rm($d->final) }}<div class="sc-muted">{{ $d->months }} month{{ $d->months == 1 ? '' : 's' }}</div></td>
                    <td class="num">{{ $d->paid > 0 ? $rm($d->paid) : '—' }}</td>
                    <td class="num sc-focus"><b>{{ $rm($d->outstanding) }}</b></td>
                    <td>
                        @if(admin_can('sales.manage') && $d->outstanding > 0)
                        <form method="post" action="{{ route('admin.sales.payout') }}" class="sc-inline" onsubmit="return confirm('Record a deferred payout of RM ' + this.amount.value + ' to {{ $d->name }} for {{ $yr }}?')">
                            @csrf<input type="hidden" name="sales_person_id" value="{{ $d->id }}"><input type="hidden" name="year" value="{{ $yr }}">
                            <input type="number" name="amount" step="0.01" min="0.01" max="{{ $d->outstanding }}" value="{{ $d->outstanding }}" required style="width:110px">
                            <input type="date" name="paid_on" value="{{ date('Y-m-d') }}" required data-raw-dates>
                            <input type="hidden" name="note" value="Year-end payout {{ $yr }}">
                            <button type="submit" class="btn btn-primary btn-sm">Record</button>
                        </form>
                        @elseif($d->outstanding <= 0 && $d->paid > 0)<span class="sc-chip sc-chip--final">Settled</span>
                        @else<span class="sc-muted">nothing owed yet</span>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="sc-empty">No commission in {{ $yr }} yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        <p class="sc-help" style="margin-top:8px">Owed = deferred 30% of approved months minus payouts recorded. Provisional months are shown in Accrued only. Pick a person above to see their year-by-year history.</p>
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
.sc-hero{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,1fr);gap:12px;margin-bottom:12px}@media (max-width:900px){.sc-hero{grid-template-columns:1fr}}
.sc-hero__main{background:#0f766e;color:#fff;border-radius:12px;padding:16px 18px;display:grid;gap:6px;align-content:start}
.sc-hero__who{font-size:13px;opacity:.9;display:flex;gap:8px;align-items:center}.sc-hero__who .sc-chip--prov{background:rgba(255,255,255,.18);color:#fff}.sc-hero__who .sc-chip--final{background:rgba(255,255,255,.25);color:#fff}
.sc-hero__amt span{display:block;font-size:12px;opacity:.85}.sc-hero__amt b{font-size:30px;line-height:1.1}
.sc-hero__line{margin:4px 0 0;font-size:12.5px;line-height:1.5;opacity:.95}.sc-hero__meta{margin:0;font-size:11.5px;opacity:.8}
.sc-hero__side{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}@media (max-width:560px){.sc-hero__side{grid-template-columns:1fr}}
.sc-kv{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:10px 12px;display:grid;gap:3px;align-content:start}.sc-kv span{font-size:11.5px;color:var(--text-secondary)}.sc-kv b{font-size:18px;line-height:1.15}.sc-kv b.sc-kv__sm{font-size:13px;line-height:1.35}.sc-kv em{font-style:normal;font-size:11.5px;color:var(--text-secondary);line-height:1.35}
.sc-todo{background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:8px 12px;margin-bottom:12px;font-size:12.5px;color:#78350f;display:flex;gap:8px;flex-wrap:wrap;align-items:center}.sc-todo a{font-weight:600;color:#92400e}.sc-todo__note{flex-basis:100%;font-size:11.5px;color:#92400e;opacity:.85}
.sc-st{display:inline-block;margin-top:2px;font-size:10.5px;font-weight:600;padding:0 7px;border-radius:999px}.sc-st--full{background:#dcfce7;color:#166534}.sc-st--trans{background:#dbeafe;color:#1d4ed8}.sc-st--prob{background:#f1f5f9;color:#475569}.sc-st--left{background:#fee2e2;color:#991b1b}
.sc-tier{min-width:120px}.sc-tier span{font-size:11.5px;color:var(--text-secondary)}.sc-bar--sm{height:5px;margin:4px 0 2px}
.sc-stays__tools{display:flex;gap:10px;align-items:center}.sc-search{padding:5px 10px;font-size:12.5px;border:1px solid var(--border,#e5e7eb);border-radius:8px;min-width:240px}
.sc-group{border:1px solid var(--border,#e5e7eb);border-radius:10px;margin-bottom:8px;padding:0 10px}.sc-group>summary{list-style:none;cursor:pointer;display:grid;grid-template-columns:140px 1fr auto;gap:12px;align-items:center;padding:9px 0;font-size:13px}.sc-group>summary::-webkit-details-marker{display:none}.sc-group>summary span{color:var(--text-secondary);font-size:12px}.sc-group>summary span.num{color:inherit;font-weight:600;font-size:13px}.sc-group[open]>summary{border-bottom:1px solid var(--border,#e5e7eb)}.sc-group .table-wrap{margin:0 -10px}
.sc-more{display:none}.sc-showmore{display:block;width:100%;margin:6px 0 8px;padding:7px;border:1px dashed #94a3b8;border-radius:8px;background:#f8fafc;font:inherit;font-size:12.5px;font-weight:600;color:#334155;cursor:pointer}.sc-showmore:hover{background:#f1f5f9}
.sc-table thead th{background:#f1f5f9;border-bottom:2px solid #cbd5e1}.sc-table th.sc-focus,.sc-table td.sc-focus{background:#ecfdf5}.sc-table th.sc-focus{color:#065f46}.sc-table td.sc-focus b{color:#065f46}.sc-table tfoot th.sc-focus{background:#d1fae5}
.sc-table td.sc-dim{color:var(--text-secondary)}.sc-table tbody tr:nth-child(even) td{background:#fafafa}.sc-table tbody tr:nth-child(even) td.sc-focus{background:#e6faf1}
.sc-kpi{display:inline-block;font-size:11px;font-weight:600;padding:1px 8px;border-radius:999px;white-space:normal;max-width:220px;line-height:1.35}.sc-kpi--ok{background:#dcfce7;color:#166534}.sc-kpi--half{background:#fef3c7;color:#92400e}.sc-kpi--nil{background:#fee2e2;color:#991b1b}
.sc-bar--t3 i{background:#0f766e}.sc-bar--t1 i{background:#f59e0b}.sc-bar--t0 i{background:#94a3b8}
.sc-stays__table td.sc-note{color:var(--text-secondary);font-size:11px}
.sc-group>summary span.num{color:#065f46}
.sc-tiles--5{grid-template-columns:repeat(5,minmax(0,1fr))}@media (max-width:1100px){.sc-tiles--5{grid-template-columns:repeat(3,minmax(0,1fr))}}@media (max-width:640px){.sc-tiles--5{grid-template-columns:repeat(2,minmax(0,1fr))}}
.sc-hero__amt b{font-size:34px}.sc-hero__math{display:flex;flex-wrap:wrap;gap:6px 8px;align-items:center;font-size:12.5px;margin-top:6px}.sc-hero__math span{background:rgba(255,255,255,.14);border-radius:999px;padding:3px 10px;white-space:nowrap}.sc-hero__math i{font-style:normal;opacity:.7}
.sc-kv--wide{grid-column:1/-1}.sc-kv__row{display:flex;justify-content:space-between;align-items:baseline}.sc-kv__row b{font-size:18px}
.sc-track{position:relative;height:8px;background:#e2e8f0;border-radius:999px;margin:8px 0 2px}.sc-track i{position:absolute;left:0;top:0;bottom:0;background:linear-gradient(90deg,#14b8a6,#0f766e);border-radius:999px}.sc-track u{position:absolute;top:-3px;width:2px;height:14px;background:#94a3b8}
.sc-track__labels{position:relative;height:14px;font-size:10.5px;color:var(--text-secondary)}.sc-track__labels span{position:absolute;transform:translateX(-100%);padding-right:4px}
.sc-kv .sc-kpi{width:max-content;margin-top:2px}
.sc-approved{background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;padding:8px 12px;margin-bottom:12px;font-size:12.5px;color:#065f46;display:flex;gap:8px;align-items:center;flex-wrap:wrap}.sc-approved form{margin-left:auto}
.sc-approve{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:8px 12px;margin-bottom:12px;font-size:12.5px;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap}
</style>
@endpush
