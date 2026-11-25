@extends('admin.layout')
@section('title', 'Revenue desk')
@section('page-title', 'Revenue desk')

@push('styles')
@include('admin.revenue._style')
<style>
.mk-blds{display:flex;gap:8px;overflow-x:auto;padding:2px 2px 10px;margin-bottom:4px;scrollbar-width:thin}
.mk-bld{flex:0 0 auto;background:#fff;border:1px solid var(--line);border-radius:12px;padding:9px 13px;display:grid;gap:2px;min-width:118px;text-decoration:none}.mk-bld b{font-size:13px;font-weight:600;color:var(--text,#1d1d1f)}.mk-bld span{font-size:11.5px;color:var(--mute)}.mk-bld span.due{color:var(--amber);font-weight:600}
.mk-bld:hover{border-color:#94a3b8}.mk-bld.on{border-color:var(--teal);box-shadow:inset 0 0 0 1px var(--teal);background:#f0fdfa}
.mk-dates{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;padding:0 16px 14px}.mk-dates label{display:grid;gap:4px;font-size:11.5px;color:var(--mute);font-weight:600;text-transform:uppercase;letter-spacing:.03em}.mk-dates input{padding:8px 10px;border:1px solid var(--line);border-radius:8px;font:inherit;font-size:13px;background:#fff}
.mk-type td{background:#fafafa;font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.03em;color:var(--mute);padding-top:8px!important;padding-bottom:8px!important}
.mk-own td{background:#f0fdfa}
.mk-in{display:flex;align-items:center;gap:6px}.mk-in span{font-size:12.5px;color:var(--mute)}.mk-in input{width:92px;padding:8px 9px;border:1px solid var(--line);border-radius:8px;font:inherit;font-size:14px;font-variant-numeric:tabular-nums;background:#fff}.mk-in input:focus{outline:2px solid rgba(15,118,110,.25);border-color:var(--teal)}
.mk-rm{background:none;border:0;color:var(--mute);font:inherit;font-size:12px;cursor:pointer;padding:0;text-decoration:underline}.mk-rm:hover{color:var(--red)}
.mk-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 16px;border-top:1px solid var(--line)}.mk-foot p{margin:0;font-size:12.5px;color:var(--mute);max-width:560px;line-height:1.45}
.mk-add{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1fr) minmax(0,1.4fr) auto;gap:10px;align-items:end;padding:0 16px 16px}.mk-add label{display:grid;gap:4px;font-size:11.5px;color:var(--mute);font-weight:600;text-transform:uppercase;letter-spacing:.03em;min-width:0}.mk-add input,.mk-add select{padding:9px 10px;border:1px solid var(--line);border-radius:8px;font:inherit;font-size:13.5px;background:#fff;min-width:0;width:100%}
.mk-say{min-width:230px}.mk-say p{margin:0;font-size:13px;line-height:1.4}
.mk-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:7px;background:#94a3b8}.mk-dot.good{background:#16a34a}.mk-dot.warn{background:#d97706}.mk-dot.bad{background:#dc2626}
.mk-vs{display:flex;align-items:baseline;gap:6px;white-space:nowrap}
@media (max-width:760px){.mk-add{grid-template-columns:1fr}.mk-in input{width:100%}.mk-in{width:100%}}
</style>
@endpush

@section('content')
@php
    $rm = fn ($v) => $v === null ? '–' : 'RM ' . number_format((float) $v, 0);
    $day = fn ($d) => \Carbon\Carbon::parse($d);
    $ago = function ($d) use ($today) { if (!$d) return null; $n = (int) \Carbon\Carbon::parse($d)->diffInDays($today); return $n === 0 ? 'today' : ($n === 1 ? 'yesterday' : $n . ' days ago'); };
    $tags = function ($date) use ($dateInfo) {
        $i = $dateInfo[$date] ?? null; if (!$i) return [];
        return array_values(array_unique(array_merge(collect($i['hol'])->whereIn('kind', ['public', 'eve'])->pluck('name')->all(), $i['ev'] ?? [])));
    };
    $types = $rows->groupBy('room_type'); $own = $rows->where('is_own', true)->count(); $them = $rows->where('is_own', false)->count();
    $lastHere = $last[$property] ?? null; $due = !$lastHere || \Carbon\Carbon::parse($lastHere)->diffInDays($today) >= 7;
    $q = http_build_query(['d1' => $dates[0] ?? null, 'd2' => $dates[1] ?? null, 'd3' => $dates[2] ?? null]);
@endphp

<div class="rd">
@include('admin.revenue._tabs', ['tab' => 'market'])

<div class="rd-head">
    <h1>Market</h1>
    <p>What nearby competitors charge, set beside our own rate and how the building is selling. Keyed in by hand, about once a week.</p>
</div>

<div class="mk-blds">
    @foreach($buildings as $b => $t)
    @php $l = $last[$b] ?? null; @endphp
    <a class="mk-bld {{ $b === $property ? 'on' : '' }}" href="/admin/revenue-desk/market?property={{ urlencode($b) }}&{{ $q }}"><b>{{ $b }}</b><span class="{{ $l && \Carbon\Carbon::parse($l)->diffInDays($today) >= 7 ? 'due' : '' }}">{{ $l ? 'checked ' . $ago($l) : 'not set up' }}</span></a>
    @endforeach
</div>

@if($them === 0)
<div class="rd-insight"><b>Start here for {{ $property }}.</b> Add three to five nearby places that a guest would compare with yours: the same kind of room, a similar standard, within a short walk or drive. Other operators' units in the same building are the best comparison. Then key in their prices for the three nights below.</div>
@elseif($due)
<div class="rd-insight" style="background:#fffbeb;border-color:#fde68a;color:#92400e"><b>{{ $property }} is due for a check.</b> {{ $lastHere ? 'Rates were last keyed in ' . $ago($lastHere) . '.' : 'No rates have been keyed in yet.' }} Look up each competitor for the three nights below and type in what you see.</div>
@endif

<div class="rd-card">
    <h2>{{ $property }}: rates for three nights</h2>
    <p class="sub">The price for one night, as a guest would see it before fees. Use the same channel each week so the figures compare. Leave a box empty if you could not find a price.</p>
    <form method="GET" action="/admin/revenue-desk/market" class="mk-dates">
        <input type="hidden" name="property" value="{{ $property }}">
        @foreach([0, 1, 2] as $i)
        <label>Night {{ $i + 1 }}<input type="date" name="d{{ $i + 1 }}" value="{{ $dates[$i] ?? '' }}" min="{{ $today->toDateString() }}"></label>
        @endforeach
        <button type="submit" class="btn" style="background:#fff;border:1px solid var(--line)">Change nights</button>
    </form>

    @if($rows->count())
    <form method="POST" action="/admin/revenue-desk/market/rates">
        @csrf
        <input type="hidden" name="property" value="{{ $property }}">
        @foreach([0, 1, 2] as $i)@if(isset($dates[$i]))<input type="hidden" name="d{{ $i + 1 }}" value="{{ $dates[$i] }}">@endif @endforeach
        <div class="rd-wrap">
            <table class="rd-table cards" style="min-width:0">
                <thead><tr><th>Who</th>@foreach($dates as $date)<th>{{ $day($date)->format('D j M') }}@foreach($tags($date) as $t)<br><span class="rd-chip red" style="margin-top:4px;text-transform:none;letter-spacing:0">{{ $t }}</span>@endforeach</th>@endforeach<th>Last checked</th></tr></thead>
                <tbody>
                @foreach($types as $type => $set)
                <tr class="mk-type"><td class="wide" colspan="{{ count($dates) + 2 }}">{{ $type }}</td></tr>
                @foreach($set as $c)
                @php $checked = collect($dates)->map(fn ($d) => $latest[$c->id][$d]->checked_on ?? null)->filter()->max(); @endphp
                <tr class="{{ $c->is_own ? 'mk-own' : '' }}">
                    <td class="wide"><span class="rd-b">{{ $c->name }}</span>
                        <div class="rd-sub">@if($c->is_own)the rate on sale in eZee today @else @if($c->link)<a href="{{ $c->link }}" target="_blank" rel="noopener" style="color:var(--teal)">Open listing</a> · @endif<button type="submit" class="mk-rm" formaction="/admin/revenue-desk/market/competitor/{{ $c->id }}/remove" formnovalidate onclick="return confirm('Remove {{ addslashes($c->name) }} from the list? Its past rates are kept.')">Remove</button>@endif</div></td>
                    @foreach($dates as $date)
                    <td data-l="{{ $day($date)->format('D j M') }}"><div class="mk-in"><span>RM</span><input type="number" inputmode="decimal" step="1" min="1" name="rate[{{ $c->id }}][{{ $date }}]" value="{{ isset($latest[$c->id][$date]) ? rtrim(rtrim(number_format((float) $latest[$c->id][$date]->rate, 2, '.', ''), '0'), '.') : '' }}" aria-label="{{ $c->name }}, {{ $day($date)->format('j M') }}"></div></td>
                    @endforeach
                    <td data-l="Last checked"><span class="rd-mute">{{ $checked ? $ago($checked) : 'never' }}</span></td>
                </tr>
                @endforeach
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mk-foot"><p>Saving keeps every check, so you can see later how a competitor's price moved. Nothing here changes a rate in eZee.</p><button type="submit" class="btn btn-primary">Save rates</button></div>
    </form>
    @else
    <div class="rd-empty">No competitors yet for {{ $property }}. Add the first one below.</div>
    @endif
</div>

@if($them > 0)
<div class="rd-card">
    <h2>What it says</h2>
    <p class="sub">Our rate against the average of the competitors keyed in, with how {{ $property }} is selling for that night.</p>
    <div class="rd-wrap">
        <table class="rd-table cards" style="min-width:0">
            <thead><tr><th>Night</th><th>Our rate</th><th>Nearby</th><th>{{ $property }} sold</th><th>What to do</th></tr></thead>
            <tbody>
            @foreach($compare as $type => $byDate)
            <tr class="mk-type"><td class="wide" colspan="5">{{ $type }}</td></tr>
            @foreach($byDate as $date => $c)
            <tr>
                <td class="wide"><span class="rd-b">{{ $day($date)->format('D j M') }}</span>@if(count($tags($date)))<div class="rd-sub">{{ implode(', ', $tags($date)) }}</div>@endif</td>
                <td data-l="Our rate"><div class="mk-vs"><span class="rd-num">{{ $rm($c['ours']) }}</span>@if($c['diff'] !== null)<span class="rd-chip {{ $c['diff'] >= 10 ? 'warm' : ($c['diff'] <= -10 ? 'soft' : 'grey') }}">{{ $c['diff'] > 0 ? '+' : ($c['diff'] < 0 ? '−' : '') }}{{ abs($c['diff']) }}%</span>@endif</div></td>
                <td data-l="Nearby"><span class="rd-num">{{ $rm($c['avg']) }}</span>@if($c['n'])<div class="rd-sub">{{ $c['n'] }} place{{ $c['n'] > 1 ? 's' : '' }}@if($c['n'] > 1), {{ $rm($c['low']) }} to {{ $rm($c['high']) }}@endif</div>@endif</td>
                <td data-l="{{ $property }} sold">@if($c['cell'])<span class="rd-num">{{ $c['cell']['pct'] }}%</span><div class="rd-sub">normal {{ $c['cell']['normal'] }}% by now</div>@else<span class="rd-mute">–</span>@endif</td>
                <td class="mk-say wide" data-l="What to do"><p><span class="mk-dot {{ $c['say'][0] }}"></span>{{ $c['say'][1] }}</p></td>
            </tr>
            @endforeach
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="rd-card">
    <h2>Add a competitor</h2>
    <p class="sub">A place a guest would choose instead of {{ $property }}. Three to five for each room type is enough.</p>
    <form method="POST" action="/admin/revenue-desk/market/competitor" class="mk-add">
        @csrf
        <input type="hidden" name="property" value="{{ $property }}">
        <label>Name<input type="text" name="name" maxlength="120" required placeholder="e.g. another operator's studio in the same building" value="{{ old('name') }}"></label>
        <label>Compare with our<select name="room_type" required>@foreach($buildings[$property] ?? [] as $t)<option {{ old('room_type') === $t ? 'selected' : '' }}>{{ $t }}</option>@endforeach</select></label>
        <label>Link to its listing (optional)<input type="url" name="link" maxlength="500" placeholder="https://" value="{{ old('link') }}"></label>
        <button type="submit" class="btn btn-primary">Add</button>
    </form>
    @if($errors->any())<p class="sub" style="color:var(--red)">{{ $errors->first() }}</p>@endif
</div>

<div class="rd-card">
    <details class="rd-note">
        <summary>How to use this page</summary>
        <ul>
            <li><strong>Once a week,</strong> for each building: open each competitor on the channel you always use, look up the three nights, and type the price for one night.</li>
            <li><strong>The three nights</strong> start as this Saturday, next Saturday and the next public holiday, stadium event or convocation. Change them with the date boxes.</li>
            <li><strong>Our rate on sale</strong> is the price a guest sees for our own unit today. Homemoka does not know it, so key it in the same way.</li>
            <li><strong>What to do</strong> compares our rate with the competitors' average, and looks at whether the building is filling early or slowly for that night. A gap of less than 10% counts as in line.</li>
            <li><strong>Above nearby and still filling</strong> means the price is holding. <strong>Above nearby and not filling</strong> means it is too high. <strong>Below nearby and still slow</strong> means price is not the problem.</li>
            <li>The comparison is only as good as the competitors chosen. Pick places of the same kind and standard.</li>
        </ul>
    </details>
</div>
</div>
@endsection
