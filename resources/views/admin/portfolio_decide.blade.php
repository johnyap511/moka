{{-- Portfolio: "What to decide" for the month we are in (Sam, 25 Nov 2026). Super admin only, like the page. --}}
@php
    $o = $outlook; $rmk = fn ($v) => 'RM ' . number_format((float) $v, 0);
    $trend = $d['trend']; $last = count($trend) >= 2 ? $trend[count($trend) - 2] : null; $ly = (float) ($d['ly']['revenue'] ?? 0);
    $vsLast = $last && $last['revenue'] > 0 ? (int) round(($o['likely'] - $last['revenue']) / $last['revenue'] * 100) : null;
    $vsLy   = $ly > 0 ? (int) round(($o['likely'] - $ly) / $ly * 100) : null;
    $sign   = fn ($v) => ($v > 0 ? '+' : ($v < 0 ? '−' : '')) . abs($v) . '%';
    $scale  = max($o['likely'], $last['revenue'] ?? 0, 1) * 1.08;
    $ch     = $n['channels']; $top = $ch->first(); $direct = $ch->firstWhere('channel', 'Website');
    $feePct = fn ($c) => $c && $c['revenue'] > 0 ? round($c['fee'] / $c['revenue'] * 100, 1) : null;
    $allFee = $ch->sum('revenue') > 0 ? round($ch->sum('fee') / $ch->sum('revenue') * 100, 1) : null;
    $mix    = collect($o['mix']); $toLong = $mix->where('verdict', 'long'); $toShort = $mix->where('verdict', 'short');
    $cnt    = $o['counts']; $where = $property ?: 'the portfolio';

    // Each line: what the figures show, then what to decide.
    $say = [];
    $say[] = ['Month end', $o['month'] . ' has ' . $rmk($o['onBooks']) . ' on the books with ' . $o['daysLeft'] . ' night' . ($o['daysLeft'] > 1 ? 's' : '') . ' left. At the usual late bookings it should finish near ' . $rmk($o['likely'])
        . ($vsLast !== null ? ', ' . $sign($vsLast) . ' against ' . $last['label'] : '') . ($vsLy !== null ? ' and ' . $sign($vsLy) . ' against ' . $d['lastYear'] : '') . '.',
        $vsLast !== null && $vsLast < -5 ? 'Behind last month: push the empty nights now, while there are still nights to sell.' : ($vsLast !== null && $vsLast > 5 ? 'Ahead of last month: protect the rate on the nights left instead of discounting.' : 'In line with last month.')];
    if ($o['pace'] !== null) {
        $say[] = ['Pace', 'From the 1st to yesterday, units earned ' . $sign($o['pace']) . ' against their own usual for the same days (' . $rmk($o['soFar']) . ' against ' . $rmk($o['usual']) . ').',
            $o['pace'] <= -10 ? 'A broad slowdown, not one weak unit: look at rates and channel visibility across the board.' : ($o['pace'] >= 10 ? 'Demand is stronger than usual: there is room to raise rates.' : 'Normal month so far.')];
    }
    if ($top) {
        // Website is our own direct business (walk-in, website, monthly rental); the others are outside channels.
        $ota = $ch->filter(fn ($c) => !in_array($c['channel'], ['Website', 'Long Term Rental'], true))->first();
        $say[] = ['Channels', ($direct ? 'Direct bookings (walk-in, website, monthly) bring ' . number_format($direct['share'], 0) . '% of room revenue. ' : '')
            . ($ota ? $ota['channel'] . ' is the largest outside channel at ' . number_format($ota['share'], 0) . '%, at RM ' . number_format($ota['adr'], 0) . ' a night' . ($direct ? ' against RM ' . number_format($direct['adr'], 0) . ' direct' : '') . '.' : ''),
            $ota && $ota['share'] >= 40 ? 'More than 40% rests on one outside channel: a rule or ranking change there would hurt. Grow a second channel and direct bookings.' : 'No outside channel holds more than 40%, so no single one can hurt you badly. Every booking moved to direct also earns the 8% direct fee.'];
    }
    if ($toLong->count() || $toShort->count()) {
        $say[] = ['Short or long stay', trim(($toLong->count() ? 'A long-stay night earns more than a short-stay night for sale in ' . $toLong->pluck('property')->implode(', ') . '. ' : '')
            . ($toShort->count() ? 'A short-stay night earns more in ' . $toShort->pluck('property')->implode(', ') . '.' : '')),
            trim(($toLong->count() ? 'Offer more monthly stays in ' . $toLong->pluck('property')->implode(', ') . ', starting with the weakest units. ' : '') . ($toShort->count() ? 'Keep ' . $toShort->pluck('property')->implode(', ') . ' on short stay as long stays end.' : ''))];
    }
    $say[] = ['Units', ($cnt['push'] ?? 0) . ' unit' . (($cnt['push'] ?? 0) === 1 ? '' : 's') . ' in ' . $where . ' need a push now and ' . ($cnt['watch'] ?? 0) . ' are a little behind.' . (($cnt['idle'] ?? 0) ? ' ' . $cnt['idle'] . ' active listing' . ($cnt['idle'] > 1 ? 's have' : ' has') . ' had no booking in four months.' : ''),
        ($cnt['idle'] ?? 0) ? 'Decide for each idle listing: open it for sale, or archive it so it stops counting as stock.' : 'See Revenue desk, Act now, for the action on each unit.'];
    if ($desk && !$property) {
        $say[] = ['Ahead', $desk['demand']['hot'] . ' date' . ($desk['demand']['hot'] === 1 ? ' is' : 's are') . ' spiking and ' . $desk['demand']['warm'] . ' are filling early in the next 90 nights.'
            . ($desk['lead']['sameDay'] !== null ? ' ' . $desk['lead']['sameDay'] . '% of short stays are booked on the day or the day before.' : ''),
            'Rates for busy dates must be raised before the last two days, when most bookings arrive.'];
    }
    $verdict = ['long' => ['Long stay earns more', '#075985', '#e0f2fe'], 'short' => ['Short stay earns more', '#166534', '#dcfce7'], 'even' => ['About the same', '#475569', '#f1f5f9'], 'na' => ['Not enough to compare', '#475569', '#f1f5f9']];
@endphp
<style>
.pd{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:14px;min-width:0}
.pd-top{display:flex;justify-content:space-between;align-items:baseline;gap:10px;flex-wrap:wrap;padding:14px 16px 10px}.pd-top h2{font-size:16px;margin:0}.pd-top a{font-size:12.5px;color:#0f766e;font-weight:600;text-decoration:none;white-space:nowrap}
.pd-out{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1px;background:var(--border,#e5e7eb);border-top:1px solid var(--border,#e5e7eb);border-bottom:1px solid var(--border,#e5e7eb)}
.pd-out>div{background:#fff;padding:12px 16px;min-width:0}.pd-out .l{font-size:11.5px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:.03em}.pd-out .v{font-size:22px;font-weight:700;letter-spacing:-.3px;margin-top:2px}.pd-out .s{font-size:12px;color:var(--text-secondary);margin-top:1px}
.pd-out .hero .v{color:#0f766e}
.pd-bar{padding:14px 16px 4px}.pd-track{position:relative;height:12px;border-radius:999px;background:#f1f5f9;overflow:visible}.pd-track i{position:absolute;inset:0 auto 0 0;border-radius:999px}.pd-track i.like{background:#99f6e4}.pd-track i.book{background:#0f766e}.pd-track u{position:absolute;top:-4px;bottom:-4px;width:2px;background:#1e293b;border-radius:1px}
.pd-key{display:flex;gap:14px;flex-wrap:wrap;font-size:11.5px;color:var(--text-secondary);margin-top:8px}.pd-key span{display:inline-flex;align-items:center;gap:5px}.pd-key b{display:inline-block;width:10px;height:10px;border-radius:3px}
.pd-list{list-style:none;margin:0;padding:6px 16px 14px;display:grid;gap:0}.pd-list li{display:grid;grid-template-columns:128px minmax(0,1fr);gap:4px 14px;padding:11px 0;border-top:1px solid var(--border,#e5e7eb)}.pd-list li:first-child{border-top:0}
.pd-list .t{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.03em;color:var(--text-secondary);padding-top:2px}.pd-list p{margin:0;font-size:13.5px;line-height:1.45}.pd-list .do{margin-top:4px;color:#0f766e;font-weight:600;font-size:13px}
.pd-two{display:grid;grid-template-columns:minmax(0,1fr);gap:12px;margin-bottom:14px}
.pd-card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);min-width:0;container-type:inline-size}.pd-card h3{font-size:13.5px;margin:0;padding:14px 16px 2px}.pd-card .sub{font-size:12px;color:var(--text-secondary);padding:0 16px 10px;line-height:1.4;margin:0}
.pd-t{width:100%;border-collapse:collapse;font-size:12.5px}.pd-t th,.pd-t td{padding:8px 8px;border-top:1px solid var(--border,#e5e7eb);text-align:left;white-space:nowrap}.pd-t th:first-child,.pd-t td:first-child{padding-left:16px}.pd-t th:last-child,.pd-t td:last-child{padding-right:16px}.pd-t th{font-size:10.5px;text-transform:uppercase;letter-spacing:.03em;color:var(--text-secondary);font-weight:600;background:#fafafa}.pd-t .num{text-align:right;font-variant-numeric:tabular-nums}.pd-t td b{font-weight:600}
.pd-scroll{overflow-x:auto}
.pd-v{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11.5px;font-weight:600}
.pd-note{font-size:11.5px;color:var(--text-secondary);padding:10px 16px 14px;line-height:1.45;border-top:1px solid var(--border,#e5e7eb);margin:0}
@container (max-width:860px){.pd-t thead{display:none}.pd-t,.pd-t tbody{display:block}.pd-t tr{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 10px;padding:12px 16px;border-top:1px solid var(--border,#e5e7eb)}.pd-t td{display:block;padding:0!important;border:0;text-align:left!important;white-space:normal}.pd-t td.wide{grid-column:1 / -1}.pd-t td[data-l]::before{content:attr(data-l);display:block;font-size:10.5px;text-transform:uppercase;letter-spacing:.03em;color:var(--text-secondary);font-weight:600}}
@media (max-width:640px){.pd-out{grid-template-columns:1fr}.pd-list li{grid-template-columns:1fr}}
</style>

<div class="pd">
    <div class="pd-top"><h2>What to decide in {{ $o['month'] }}</h2><a href="/admin/units-to-push">Open the Revenue desk →</a></div>
    <div class="pd-out">
        <div><div class="l">On the books</div><div class="v">{{ $rmk($o['onBooks']) }}</div><div class="s">room charges, {{ $o['month'] }} so far</div></div>
        <div class="hero"><div class="l">Likely finish</div><div class="v">about {{ $rmk($o['likely']) }}</div><div class="s">{{ $rmk($o['pickup']) }} more from about {{ number_format($o['pickupNights']) }} late short-stay nights</div></div>
        <div><div class="l">Against</div><div class="v" style="font-size:16px;line-height:1.5">@if($last){{ $last['label'] }} {{ $rmk($last['revenue']) }}@if($vsLast !== null) <span style="color:{{ $vsLast < 0 ? '#b91c1c' : '#166534' }}">{{ $sign($vsLast) }}</span>@endif @endif</div><div class="s">@if($ly > 0){{ $d['lastYear'] }} {{ $rmk($ly) }}@if($vsLy !== null), {{ $sign($vsLy) }}@endif @else no figure for {{ $d['lastYear'] }} @endif</div></div>
    </div>
    <div class="pd-bar">
        <div class="pd-track"><i class="like" style="width:{{ min(100, $o['likely'] / $scale * 100) }}%"></i><i class="book" style="width:{{ min(100, $o['onBooks'] / $scale * 100) }}%"></i>@if($last && $last['revenue'] > 0)<u style="left:calc({{ min(100, $last['revenue'] / $scale * 100) }}% - 1px)" title="{{ $last['label'] }}: {{ $rmk($last['revenue']) }}"></u>@endif</div>
        <div class="pd-key"><span><b style="background:#0f766e"></b> on the books</span><span><b style="background:#99f6e4"></b> likely late bookings</span>@if($last)<span><b style="background:#1e293b;width:2px;height:12px;border-radius:1px"></b> {{ $last['label'] }} actual</span>@endif</div>
    </div>
    <ul class="pd-list">
        @foreach($say as [$t, $text, $do])
        <li><div class="t">{{ $t }}</div><div><p>{{ $text }}</p><p class="do">{{ $do }}</p></div></li>
        @endforeach
    </ul>
</div>

<div class="pd-two">
    <div class="pd-card">
        <h3>Short stay or long stay</h3>
        <p class="sub">What one night earns, last 8 weeks. A short-stay night counts its empty nights; a long-stay night is paid every night.</p>
        <div class="pd-scroll"><table class="pd-t" style="min-width:0">
            <thead><tr><th>Building</th><th class="num">Short stay sold</th><th class="num">Short, per night for sale</th><th class="num">Long, per night</th><th>Verdict</th></tr></thead>
            <tbody>
            @foreach($o['mix'] as $m)
            <tr><td class="wide"><b>{{ $m['property'] }}</b> <span style="color:var(--text-secondary)">· {{ $m['longShare'] }}% on long stays</span></td>
                <td class="num" data-l="Short stay sold">{{ $m['shortPct'] !== null ? $m['shortPct'] . '%' : '–' }}@if($m['shortRate']) <span style="color:var(--text-secondary)">at RM {{ $m['shortRate'] }}</span>@endif</td>
                <td class="num" data-l="Short, per night for sale"><b>{{ $m['shortNight'] !== null ? 'RM ' . $m['shortNight'] : '–' }}</b></td>
                <td class="num" data-l="Long, per night"><b>{{ $m['longRate'] !== null ? 'RM ' . $m['longRate'] : '–' }}</b></td>
                <td class="wide" data-l="Verdict"><span class="pd-v" style="color:{{ $verdict[$m['verdict']][1] }};background:{{ $verdict[$m['verdict']][2] }}">{{ $verdict[$m['verdict']][0] }}@if($m['gap'] !== null && in_array($m['verdict'], ['long', 'short'])) by {{ abs($m['gap']) }}%@endif</span></td></tr>
            @endforeach
            </tbody>
        </table></div>
        <p class="pd-note">Room charges only, before SST and channel fees. Short stays also bring cleaning fees and cost more to run (cleaning, check-ins, channel fees), so a small gap either way is not a reason to switch.</p>
    </div>
    <div class="pd-card">
        <h3>Where bookings come from</h3>
        <p class="sub">{{ $d['month'] }}, room charges. Website is your own direct business: walk-in, website and monthly rental.</p>
        <div class="pd-scroll"><table class="pd-t" style="min-width:0">
            <thead><tr><th>Channel</th><th class="num">Share</th><th class="num">Revenue</th><th class="num">Nights</th><th class="num">Rate a night</th><th class="num">Fee recorded</th></tr></thead>
            <tbody>
            @foreach($ch->take(8) as $c)
            <tr><td class="wide"><span class="pf-dot" style="background:{{ $d['colours'][$c['channel']] ?? '#94a3b8' }}"></span><b>{{ $c['channel'] }}</b></td>
                <td class="num" data-l="Share"><b>{{ number_format($c['share'], 0) }}%</b></td>
                <td class="num" data-l="Revenue">{{ $rmk($c['revenue']) }}</td>
                <td class="num" data-l="Nights">{{ number_format($c['nights']) }}</td>
                <td class="num" data-l="Rate a night">RM {{ number_format($c['adr'], 0) }}</td>
                <td class="num" data-l="Fee recorded">@if($c['fee'] > 0){{ $rmk($c['fee']) }} <span style="color:var(--text-secondary)">{{ $feePct($c) }}%</span>@else<span style="color:var(--text-secondary)">none</span>@endif</td></tr>
            @endforeach
            </tbody>
        </table></div>
        <p class="pd-note">Read "Fee recorded" with care. For Website it is the 8% direct fee, which is income to MOKA, not a cost. Agoda, Trip.com and Tiket.com show none because their amounts arrive after commission. A stay that began last month shows revenue here but its fee there.</p>
    </div>
</div>
