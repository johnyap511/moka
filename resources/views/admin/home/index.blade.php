@extends('admin.layout')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
    $totalListings  = \App\Listing::count();
    $activeListings = \App\Listing::where('status', 1)->count();
    $totalOwners    = \App\User::whereHas('roles', fn($q) => $q->where('name','owner'))->count();
    $totalGuests    = \App\User::whereHas('roles', fn($q) => $q->where('name','user'))->count();
    $totalBookings  = \App\Booking::count();
    $activeBookings = \App\Booking::where('status', '>=', 3)->where('status', '<', 9)->count();
    // Revenue is counted by the month the stay checks in (bookings are split at
    // month ends, so a row belongs to one calendar month), never by the date the
    // row was created: imports and repairs create rows in bulk.
    $monthStart     = date('Y-m-01');
    $monthEnd       = date('Y-m-01', strtotime('+1 month'));
    $lastStart      = date('Y-m-01', strtotime('-1 month'));
    $showRevenue    = admin_can('dashboard.revenue');
    $thisMonth      = $showRevenue ? \App\Booking::where('status', '>=', 5)->where('check_in', '>=', $monthStart)->where('check_in', '<', $monthEnd)->sum('price') : 0;
    $lastMonth      = $showRevenue ? \App\Booking::where('status', '>=', 5)->where('check_in', '>=', $lastStart)->where('check_in', '<', $monthStart)->sum('price') : 0;
    $recentBookings = \App\Booking::with(['listing'])->orderBy('created_at','desc')->limit(8)->get();
@endphp

<div class="page-header">
    <div>
        <h1>Good {{ date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') }}, {{ Auth::user()->name }}</h1>
        <p>Here's what's happening on MOKA today.</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="val">{{ $activeListings }}</div>
        <div class="lbl">Active Listings</div>
        <div class="text-sm text-secondary mt-1">{{ $totalListings }} total</div>
    </div>
    <div class="stat-card">
        <div class="val">{{ $totalOwners }}</div>
        <div class="lbl">Property Owners</div>
    </div>
    <div class="stat-card">
        <div class="val">{{ $activeBookings }}</div>
        <div class="lbl">Active Bookings</div>
        <div class="text-sm text-secondary mt-1">{{ $totalBookings }} total</div>
    </div>
    @if($showRevenue)
    <div class="stat-card">
        <div class="val">RM {{ number_format($thisMonth) }}</div>
        <div class="lbl">Revenue {{ date('M Y') }}, stays checking in</div>
        <div class="text-sm text-secondary mt-1">{{ date('M Y', strtotime('-1 month')) }}: RM {{ number_format($lastMonth) }}</div>
    </div>
    @endif
    <div class="stat-card">
        <div class="val">{{ $totalGuests }}</div>
        <div class="lbl">Registered Guests</div>
    </div>
</div>

{{-- Recent Bookings removed on request. The controller still provides
     $recentBookings, so restoring this only needs the comment markers gone. --}}
{{--
<div class="card">
    <div class="card-header">
        <h2>Recent Bookings</h2>
        <a href="/admin/book" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Property</th>
                    <th>Guest</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentBookings as $b)
                <tr>
                    <td class="mono">#{{ $b->id }}</td>
                    <td>{{ $b->listing->title ?? $b->listing->name ?? '—' }}</td>
                    <td>{{ $b->name ?? '—' }}</td>
                    <td>{{ $b->check_in }}</td>
                    <td>{{ $b->check_out }}</td>
                    <td>RM {{ number_format($b->price ?? 0) }}</td>
                    <td>
                        @php $s = $b->status ?? 0; @endphp
                        @if($s >= 9) <span class="badge badge-gray">Completed</span>
                        @elseif($s >= 5) <span class="badge badge-green">Confirmed</span>
                        @elseif($s >= 3) <span class="badge badge-blue">Pending</span>
                        @else <span class="badge badge-red">Cancelled</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--text-secondary)">No bookings yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
--}}
@endsection
