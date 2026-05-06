@extends('admin.layout')
@section('title', 'Upcoming Payments')
@section('page-title', 'Upcoming Payments')

@section('content')

<div class="page-header">
    <div>
        <h1>Upcoming Payments</h1>
        <p>Pending owner payouts</p>
    </div>
    <a href="/admin/payment/past" class="btn btn-secondary">View Past Payments</a>
</div>

{{-- Filter Form --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h2>Filter</h2></div>
    <div class="card-body">
        <form method="GET" action="/admin/payment/upcoming">
            <div class="form-row">
                <div class="form-group">
                    <label>Year</label>
                    <input type="number" name="year" class="form-input" value="{{ $year ?? date('Y') }}" placeholder="YYYY">
                </div>
                <div class="form-group">
                    <label>Month</label>
                    <input type="number" name="month" class="form-input" value="{{ $month ?? date('m') }}" min="1" max="12" placeholder="MM">
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end">
                    <button type="submit" name="filter" value="true" class="btn btn-primary">Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Reports Table --}}
<div class="card">
    <div class="card-header">
        <h2>Reports</h2>
        <span class="badge badge-blue">{{ $reports->count() }} records</span>
    </div>
    <div class="table-wrap">
        @if($reports->isEmpty())
            <div class="empty-state">
                <p>No upcoming payments found for the selected period.</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Listing</th>
                        <th>Period</th>
                        <th>Owner Income</th>
                        <th>Platform Income</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reports as $report)
                    <tr>
                        <td>{{ $report->id }}</td>
                        <td>{{ $report->listing_id }}</td>
                        <td>{{ $report->month }}/{{ $report->year }}</td>
                        <td>RM {{ number_format($report->owner_income ?? 0, 2) }}</td>
                        <td>RM {{ number_format($report->platform_income ?? 0, 2) }}</td>
                        <td class="actions">
                            <a href="#" class="btn btn-secondary btn-sm">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
