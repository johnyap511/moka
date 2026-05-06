@extends('owner.layout')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
    $myListings  = \App\Listing::where('user_id', Auth::id())->get();
    $activeCount = $myListings->where('status', 1)->count();
    $myBookings  = \App\Booking::whereIn('listing_id', $myListings->pluck('id'))->count();
    $totalRevenue = \App\Booking::whereIn('listing_id', $myListings->pluck('id'))->where('status', '>=', 5)->sum('price');
@endphp

<div class="page-header">
    <div>
        <h1>Welcome back, {{ Auth::user()->name }}</h1>
        <p>Your property portfolio at a glance.</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="val">{{ $myListings->count() }}</div>
        <div class="lbl">My Listings</div>
    </div>
    <div class="stat-card">
        <div class="val">{{ $activeCount }}</div>
        <div class="lbl">Active Listings</div>
    </div>
    <div class="stat-card">
        <div class="val">{{ $myBookings }}</div>
        <div class="lbl">Total Bookings</div>
    </div>
    <div class="stat-card">
        <div class="val">RM {{ number_format($revenueyearly ?? $totalRevenue ?? 0) }}</div>
        <div class="lbl">Yearly Revenue</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>My Properties</h2>
        <a href="/owner/listing" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <div class="card-body">
        @if($myListings->isEmpty())
            <div class="empty-state">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                <p>No properties assigned yet</p>
                <small>Contact admin to get your properties listed.</small>
            </div>
        @else
            <div class="listing-grid">
                @foreach($myListings as $l)
                <div class="listing-card">
                    <span class="badge {{ $l->status ? 'badge-green' : 'badge-red' }}">{{ $l->status ? 'Active' : 'Inactive' }}</span>
                    <h3>{{ $l->title ?? $l->name }}</h3>
                    <p class="addr">{{ $l->address }}</p>
                    <div class="price">RM {{ number_format($l->default_price ?? 0) }} <span>/ night</span></div>
                    <div class="actions" style="margin-top:12px">
                        <a href="/owner/listing/{{ $l->id }}/calendar" class="btn btn-secondary btn-sm">Calendar</a>
                        <a href="/owner/listing/chart/report?listing_id={{ $l->id }}" class="btn btn-secondary btn-sm">Report</a>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@if(!empty($graphArray) && count($graphArray) > 0)
<div class="card" style="margin-top:20px">
    <div class="card-header">
        <h2>Occupancy &amp; Revenue Data</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Occupancy %</th>
                    <th>Avg Rate (RM)</th>
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
@endif
@endsection
