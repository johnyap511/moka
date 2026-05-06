@extends('owner.layout')

@section('title', 'Listing Report')
@section('page-title', 'Listing Report')

@section('content')
<div class="page-header">
    <div>
        <h1>Listing Report</h1>
        <p>Occupancy and revenue overview.</p>
    </div>
</div>

{{-- Listing Selector --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-body">
        <form method="GET" action="/owner/listing/chart/report" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <div class="form-group" style="margin-bottom:0;flex:1;min-width:200px">
                <label class="form-label">Select Listing</label>
                <select name="listing_id" class="form-select" onchange="this.form.submit()">
                    @foreach($allListings as $l)
                        <option value="{{ $l->id }}" {{ ($id == $l->id) ? 'selected' : '' }}>
                            {{ $l->title ?? $l->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Month</label>
                <input type="month" name="date" class="form-input" value="{{ $selDate ? date_format(date_create(is_string($selDate) ? $selDate : date_format($selDate, 'Y-m-d')), 'Y-m') : date('Y-m') }}" style="width:auto">
            </div>
            <button type="submit" class="btn btn-primary">Apply</button>
        </form>
    </div>
</div>

@if($listing)
<div style="margin-bottom:12px">
    <span class="font-600">{{ $listing->title ?? $listing->name }}</span>
    <span class="text-secondary" style="margin-left:8px;font-size:13px">{{ $listing->address }}</span>
</div>
@endif

@if(!empty($graphArray) && count($graphArray) > 0)
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h2>Occupancy &amp; Average Rate</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Occupancy %</th>
                    <th>Avg Nightly Rate (RM)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($graphArray as $i => $row)
                <tr>
                    <td>{{ str_replace('`', ' ', $row[0] ?? '-') }}</td>
                    <td>{{ $row[1] ?? 0 }}%</td>
                    <td>RM {{ number_format($graphavg[$i][1] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="card">
    <div class="empty-state">
        <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <p>No report data available</p>
        <small>No bookings found for the selected period.</small>
    </div>
</div>
@endif

@if(!empty($graphmonth) && count($graphmonth) > 0)
<div class="card" style="margin-top:20px">
    <div class="card-header">
        <h2>Monthly Breakdown</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Year</th>
                    <th>Nights</th>
                    <th>Price / Night (RM)</th>
                    <th>Cleaning Fee (RM)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($graphmonth as $row)
                <tr>
                    <td>{{ $row['month'] ?? '-' }}</td>
                    <td>{{ $row['year'] ?? '-' }}</td>
                    <td>{{ $row['nights'] ?? '-' }}</td>
                    <td>{{ number_format($row['price_night'] ?? 0, 2) }}</td>
                    <td>{{ number_format($row['cleaning_fee'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
