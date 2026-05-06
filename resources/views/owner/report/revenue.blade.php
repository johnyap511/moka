@extends('owner.layout')

@section('title', 'Revenue Report')
@section('page-title', 'Revenue Report')

@section('content')
<div class="page-header">
    <div>
        <h1>Revenue Report</h1>
        <p>Monthly revenue breakdown for your properties.</p>
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-body">
        <form method="GET" action="/owner/report/revenue" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Year</label>
                <select name="year" class="form-select" style="width:120px">
                    <option value="">All Years</option>
                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                        <option value="{{ $y }}" {{ ($year == $y) ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            @if($year)
                <a href="/owner/report/revenue" class="btn btn-secondary">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Revenue{{ $year ? ' — ' . $year : '' }}</h2>
        @if($revenues->count() > 0)
        <span class="badge badge-teal">{{ $revenues->count() }} records</span>
        @endif
    </div>

    @if($revenues->isEmpty())
        <div class="empty-state">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p>No revenue data found</p>
            <small>Try selecting a different year or check back later.</small>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Listing ID</th>
                        <th>Year</th>
                        <th>Month</th>
                        <th>Owner Income (RM)</th>
                        <th>Platform Income (RM)</th>
                        <th>Water (RM)</th>
                        <th>Electricity (RM)</th>
                        <th>Internet (RM)</th>
                        <th>Report File</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($revenues as $row)
                    <tr>
                        <td class="text-secondary">#{{ $row->listing_id }}</td>
                        <td>{{ $row->year ?? '-' }}</td>
                        <td>{{ $row->month ?? '-' }}</td>
                        <td class="font-600" style="color:var(--teal)">{{ number_format($row->owner_income ?? 0, 2) }}</td>
                        <td>{{ number_format($row->platform_income ?? 0, 2) }}</td>
                        <td>{{ number_format($row->water_amount ?? 0, 2) }}</td>
                        <td>{{ number_format($row->electricity_amount ?? 0, 2) }}</td>
                        <td>{{ number_format($row->internet_amount ?? 0, 2) }}</td>
                        <td>
                            @if($row->excel_name)
                                <span class="badge badge-blue">{{ $row->excel_name }}</span>
                            @else
                                <span class="text-secondary">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:24px;font-size:13.5px">
            <span class="text-secondary">Total Owner Income:</span>
            <span class="font-600" style="color:var(--teal)">RM {{ number_format($revenues->sum('owner_income'), 2) }}</span>
        </div>
    @endif
</div>
@endsection
