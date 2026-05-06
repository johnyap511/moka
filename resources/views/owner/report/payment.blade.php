@extends('owner.layout')

@section('title', 'Payment Report')
@section('page-title', 'Payment Report')

@section('content')
<div class="page-header">
    <div>
        <h1>Payment Report</h1>
        <p>Monthly payment records for your properties.</p>
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-body">
        <form method="GET" action="/owner/report/payment" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
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
                <a href="/owner/report/payment" class="btn btn-secondary">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Payments{{ $year ? ' — ' . $year : '' }}</h2>
        @if($revenues->count() > 0)
        <span class="badge badge-teal">{{ $revenues->count() }} records</span>
        @endif
    </div>

    @if($revenues->isEmpty())
        <div class="empty-state">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p>No payment data found</p>
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
                        <th>Adj. 1</th>
                        <th>Adj. 2</th>
                        <th>Adj. 3</th>
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
                        <td>
                            @if($row->adjustment1_text)
                                <span title="{{ $row->adjustment1_text }}">RM {{ number_format($row->adjustment1_amount ?? 0, 2) }}</span>
                            @else
                                <span class="text-secondary">—</span>
                            @endif
                        </td>
                        <td>
                            @if($row->adjustment2_text)
                                <span title="{{ $row->adjustment2_text }}">RM {{ number_format($row->adjustment2_amount ?? 0, 2) }}</span>
                            @else
                                <span class="text-secondary">—</span>
                            @endif
                        </td>
                        <td>
                            @if($row->adjustment3_text)
                                <span title="{{ $row->adjustment3_text }}">RM {{ number_format($row->adjustment3_amount ?? 0, 2) }}</span>
                            @else
                                <span class="text-secondary">—</span>
                            @endif
                        </td>
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
