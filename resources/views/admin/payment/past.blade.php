@extends('admin.layout')
@section('title', 'Past Payments')
@section('page-title', 'Past Payments')

@section('content')

<div class="page-header">
    <div>
        <h1>Past Payments</h1>
        <p>Completed owner payouts</p>
    </div>
    <a href="/admin/payment/upcoming" class="btn btn-secondary">View Upcoming Payments</a>
</div>

<div class="card">
    <div class="card-header">
        <h2>Paid Reports</h2>
        <span class="badge badge-green">{{ $reports->count() }} records</span>
    </div>
    <div class="table-wrap">
        @if($reports->isEmpty())
            <div class="empty-state">
                <p>No past payments found.</p>
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
