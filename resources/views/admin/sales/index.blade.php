@extends('admin.layout')
@section('title', 'Sales Commission')
@section('page-title', 'Sales Commission')

@section('content')
@php $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M') : '—'; $rm = fn ($v) => 'RM ' . number_format($v, 2); @endphp
<div class="page-header">
    <div>
        <h1>Sales Commission</h1>
        <p>{{ number_format($data['rate'] * 100, 2) }}% of room charges before SST, counted in the month each night was posted in eZee. Source: eZee's Transaction Detail Report, uploaded per property.</p>
    </div>
</div>

<div class="sc-grid">
    <div class="card">
        <div class="card-body">
            <form method="get" class="sc-filter">
                <label>Month <input type="month" name="month" value="{{ $ym }}" data-raw-dates></label>
                <label>Sales person
                    <select name="person"><option value="">Everyone</option>@foreach($people as $p)<option value="{{ $p }}" @selected($person === $p)>{{ $p }}</option>@endforeach</select>
                </label>
                <button type="submit" class="btn btn-primary btn-sm">Show</button>
            </form>
            <table class="sc-table" style="margin-top:12px">
                <thead><tr><th>Sales person</th><th>Stays</th><th>Nights</th><th>Room charges (net)</th><th>Commission</th><th>On hold</th></tr></thead>
                <tbody>
                @forelse($data['people'] as $p)
                    <tr><td><a href="?month={{ $ym }}&person={{ urlencode($p->name) }}">{{ $p->name }}</a></td><td>{{ $p->stays }}</td><td>{{ $p->nights }}</td><td>{{ $rm($p->net) }}</td><td><b>{{ $rm($p->commission) }}</b></td>
                        <td>@if($p->warnings)<span class="sc-warn" title="Stays changed in eZee since the last upload">{{ $p->warnings }} stay(s), {{ $rm($p->held) }}</span>@else—@endif</td></tr>
                @empty
                    <tr><td colspan="6" style="color:var(--text-secondary);padding:18px">No posted room nights with a sales person for {{ \Carbon\Carbon::parse($data['from'])->format('F Y') }}. Upload the reports for that month.</td></tr>
                @endforelse
                </tbody>
                @if(count($data['people']) > 1)
                <tfoot><tr><th>Total</th><th>{{ array_sum(array_column($data['people'], 'stays')) }}</th><th>{{ array_sum(array_column($data['people'], 'nights')) }}</th><th>{{ $rm(array_sum(array_column($data['people'], 'net'))) }}</th><th>{{ $rm(array_sum(array_column($data['people'], 'commission'))) }}</th><th>{{ $rm(array_sum(array_column($data['people'], 'held'))) }}</th></tr></tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3 style="margin:0 0 6px;font-size:15px">Upload eZee reports</h3>
            <p class="sc-help">In eZee: Reports → Back Office → <b>Transaction Detail Report</b>, one file per property, exported as Excel. Upload weekly. Overlapping dates are fine: the newest file replaces what it covers, so nothing is counted twice and cancelled or shortened stays correct themselves.</p>
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
                    <tr><td>{{ $name }}</td><td class="{{ !$c || $stale ? 'sc-warn' : '' }}">{{ $c ? \Carbon\Carbon::parse($c->last_day)->format('j M Y') : 'never' }}</td><td>{{ $c ? \Carbon\Carbon::parse($c->last_upload)->format('j M H:i') : '—' }}</td></tr>
                @endforeach</tbody>
            </table>
        </div>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-body">
        <h3 style="margin:0 0 10px;font-size:15px">Stays behind the figures{{ $person ? ' — ' . $person : '' }}</h3>
        <div class="table-wrap">
        <table class="sc-table" data-enhance="search" data-search-placeholder="Search guest, RES, folio, unit">
            <thead><tr><th>Sales person</th><th>Property</th><th>Guest</th><th>RES / Folio</th><th>Unit</th><th>Stay</th><th>Nights this month</th><th>Room charges (net)</th><th>Commission</th><th>Source</th><th>Note</th></tr></thead>
            <tbody>
            @forelse($data['stays'] as $s)
                <tr class="{{ $s->warn ? 'sc-row-warn' : '' }}">
                    <td>{{ $s->sales_person }}</td><td>{{ $s->property }}</td><td>{{ $s->guest }}</td><td class="mono">{{ $s->res_no ?: 'no RES' }}<br><span style="color:var(--text-secondary)">{{ $s->folio_no }}</span></td>
                    <td>{{ $s->room }}</td><td class="text-nowrap">{{ $fmt($s->arrival) }} → {{ $fmt($s->departure) }}</td><td>{{ $s->nights }}</td><td>{{ $rm($s->net) }}</td>
                    <td>{{ $s->warn ? 'on hold' : $rm($s->commission) }}</td><td>{{ $s->source }}</td><td class="sc-note">{{ $s->warn ?: $s->status }}</td>
                </tr>
            @empty
                <tr><td colspan="11" style="color:var(--text-secondary);padding:18px">Nothing to show.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

@if(count($uploads))
<details style="margin-top:16px"><summary style="cursor:pointer;font-size:13px;color:var(--text-secondary)">Recent uploads</summary>
<div class="card" style="margin-top:8px"><div class="card-body"><table class="sc-table sc-small">
    <thead><tr><th>When</th><th>Property</th><th>File</th><th>Period</th><th>Rows stored</th><th>Skipped (reported months)</th><th>Replaced</th></tr></thead>
    <tbody>@foreach($uploads as $u)<tr><td class="text-nowrap">{{ \Carbon\Carbon::parse($u->created_at)->format('j M H:i') }}</td><td>{{ $hotels[$u->hotel_code] ?? $u->hotel_code }}</td><td>{{ $u->filename }}</td><td class="text-nowrap">{{ $fmt($u->period_from) }} → {{ $fmt($u->period_to) }}</td><td>{{ $u->rows_stored }}</td><td>{{ $u->rows_skipped_locked }}</td><td>{{ $u->rows_replaced }}</td></tr>@endforeach</tbody>
</table></div></div></details>
@endif
@endsection

@push('scripts')
<style>
.sc-grid{display:grid;grid-template-columns:minmax(0,3fr) minmax(280px,2fr);gap:16px}
@media (max-width:900px){.sc-grid{grid-template-columns:1fr}}
.sc-filter{display:flex;gap:12px;align-items:end;flex-wrap:wrap;font-size:13px}.sc-filter label{display:grid;gap:4px}.sc-filter input,.sc-filter select{padding:6px 8px;font-size:13px}
.sc-table{width:100%;border-collapse:collapse;font-size:13px}.sc-table th,.sc-table td{padding:7px 8px;border-bottom:1px solid var(--border,#e5e7eb);text-align:left;vertical-align:top}.sc-table th{font-size:11.5px;text-transform:uppercase;letter-spacing:.03em;color:var(--text-secondary)}
.sc-table tfoot th{text-transform:none;font-size:13px;color:inherit}
.sc-small{font-size:12px}.sc-small th,.sc-small td{padding:5px 6px}
.sc-help{font-size:12.5px;color:var(--text-secondary);line-height:1.5;margin:0 0 10px}
.sc-upload{display:grid;gap:8px;font-size:13px}.sc-upload select{padding:6px 8px;font-size:13px}
.sc-warn{color:#b45309;font-weight:600}.sc-row-warn td{background:#fffbeb}.sc-note{font-size:12px;color:var(--text-secondary);max-width:280px}
</style>
@endpush
