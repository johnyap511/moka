@extends('owner.layout')

@section('title', 'Calendar')
@section('page-title', 'Calendar')

@section('content')
<div class="page-header">
    <div>
        <h1>
            @if(!empty($listing))
                {{ $listing->title ?? $listing->name }} — Calendar
            @else
                Booking Calendar
            @endif
        </h1>
        <p>Upcoming and past bookings for your property.</p>
    </div>
    @if(!empty($listing))
    <div class="actions">
        <a href="/owner/listing" class="btn btn-secondary">Back to Listings</a>
    </div>
    @endif
</div>

@php
    $eventsData = is_string($events) ? json_decode($events, true) : (is_array($events) ? $events : []);
    $eventsData = $eventsData ?? [];
@endphp

<div class="card">
    <div class="card-header">
        <h2>Bookings ({{ count($eventsData) }})</h2>
    </div>

    @if(count($eventsData) === 0)
        <div class="empty-state">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <p>No bookings found</p>
            <small>There are no bookings for this property yet.</small>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Guest</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Nights</th>
                        <th>Guests</th>
                        <th>Price / Night (RM)</th>
                        <th>Source</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($eventsData as $event)
                    <tr>
                        <td>
                            <div class="font-600">{{ $event['title'] ?? 'Guest' }}</div>
                        </td>
                        <td>{{ $event['start'] ?? '-' }}</td>
                        <td>{{ $event['end'] ?? '-' }}</td>
                        <td>{{ $event['nights'] ?? '-' }}</td>
                        <td class="text-secondary">{{ $event['guest'] ?? '-' }}</td>
                        <td>{{ number_format($event['price_night'] ?? 0, 2) }}</td>
                        <td>
                            @if(!empty($event['source']))
                                <span class="badge badge-blue">{{ $event['source'] }}</span>
                            @else
                                <span class="text-secondary">—</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($event['id']))
                                <a href="/owner/book/{{ $event['id'] }}" class="btn btn-secondary btn-sm">View</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
