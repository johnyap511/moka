@extends('admin.layout')
@section('title', 'Guest: ' . $user->name)
@section('page-title', 'Guest Detail')

@section('content')

@php
    $bookings = App\Models\Booking::where('user_id', $user->id)->orderBy('created_at','desc')->limit(10)->get();
@endphp

<div class="page-header">
    <div>
        <h1>Guest: {{ $user->name }}</h1>
        <p>Guest account details and booking history</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/users" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back
        </a>
        <a href="/admin/users/{{ $user->id }}/edit" class="btn btn-primary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit Guest
        </a>
    </div>
</div>

<div style="display:grid;grid-template-columns:320px 1fr;gap:20px;align-items:start">

    {{-- Left: Profile Card --}}
    <div class="card">
        <div class="card-header">
            <h2>Profile</h2>
            @if($user->status == 1)
                <span class="badge badge-green">Active</span>
            @else
                <span class="badge badge-red">Inactive</span>
            @endif
        </div>
        <div class="card-body" style="text-align:center;padding-top:32px">
            <div style="width:72px;height:72px;border-radius:50%;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;margin:0 auto 16px">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div style="font-size:17px;font-weight:700;letter-spacing:-.3px">{{ $user->name }}</div>
            <div style="color:var(--text-secondary);font-size:13px;margin-top:4px">Guest</div>
        </div>
        <div class="divider" style="margin:0"></div>
        <div class="card-body" style="padding:0">
            <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
                <div class="text-sm text-secondary" style="margin-bottom:3px">Email</div>
                <div style="font-size:13.5px;font-weight:500">{{ $user->email }}</div>
            </div>
            <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
                <div class="text-sm text-secondary" style="margin-bottom:3px">Phone</div>
                <div style="font-size:13.5px;font-weight:500">{{ $user->phone ?? '—' }}</div>
            </div>
            <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
                <div class="text-sm text-secondary" style="margin-bottom:3px">Status</div>
                <div>
                    @if($user->status == 1)
                        <span class="badge badge-green">Active</span>
                    @else
                        <span class="badge badge-red">Inactive</span>
                    @endif
                </div>
            </div>
            <div style="padding:14px 20px">
                <div class="text-sm text-secondary" style="margin-bottom:3px">Member Since</div>
                <div style="font-size:13.5px;font-weight:500">{{ $user->created_at ? $user->created_at->format('d M Y') : '—' }}</div>
            </div>
        </div>
    </div>

    {{-- Right: Booking History --}}
    <div class="card">
        <div class="card-header">
            <h2>Booking History</h2>
            <span class="badge badge-gray">Last 10</span>
        </div>
        <div class="table-wrap">
            @if($bookings->isEmpty())
                <div class="empty-state" style="padding:40px 20px">
                    <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p>No bookings yet</p>
                    <small>This guest has not made any bookings.</small>
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Listing</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                        <tr>
                            <td>
                                <div class="font-600">
                                    @if($booking->listing)
                                        {{ $booking->listing->title ?? 'Listing #' . $booking->listing_id }}
                                    @else
                                        <span class="text-secondary">Listing #{{ $booking->listing_id }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-sm">{{ $booking->check_in ? \Carbon\Carbon::parse($booking->check_in)->format('d M Y') : '—' }}</td>
                            <td class="text-sm">{{ $booking->check_out ? \Carbon\Carbon::parse($booking->check_out)->format('d M Y') : '—' }}</td>
                            <td>
                                @php $s = $booking->status ?? '' @endphp
                                @if(in_array($s, ['confirmed', 'approved', 'active']))
                                    <span class="badge badge-green">{{ ucfirst($s) }}</span>
                                @elseif(in_array($s, ['pending', 'review']))
                                    <span class="badge badge-orange">{{ ucfirst($s) }}</span>
                                @elseif(in_array($s, ['cancelled', 'rejected', 'canceled']))
                                    <span class="badge badge-red">{{ ucfirst($s) }}</span>
                                @elseif(in_array($s, ['completed', 'done']))
                                    <span class="badge badge-teal">{{ ucfirst($s) }}</span>
                                @else
                                    <span class="badge badge-gray">{{ $s ? ucfirst($s) : '—' }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

</div>

@endsection
