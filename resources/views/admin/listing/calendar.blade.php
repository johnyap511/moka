@extends('admin.layout')
@section('title', 'Listing Calendar')
@section('page-title', 'Listing Calendar')

@section('content')

<div class="page-header">
    <div>
        <h1>Listing Calendar</h1>
        <p>Booking overview for selected property</p>
    </div>
    <div class="flex gap-2">
        @if(isset($listing) && $listing)
        <a href="/admin/listing/{{ $listing->id }}/edit" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Edit Listing
        </a>
        @endif
    </div>
</div>

{{-- Listing selector --}}
@if(isset($allListings) && $allListings->count())
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" action="/admin/calendar" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <label style="font-size:12px;font-weight:600;color:var(--text-secondary);white-space:nowrap">Select Property:</label>
            <select name="listing_id" class="form-input" style="min-width:260px;max-width:400px" onchange="this.form.submit()">
                @foreach($allListings as $l)
                    <option value="{{ $l->id }}" {{ $l->id == ($selectedId ?? '') ? 'selected' : '' }}>
                        {{ $l->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm">View</button>
        </form>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h2>Calendar View</h2>
        @if(isset($listing) && $listing)
            <span class="badge badge-blue">{{ $listing->name }}</span>
        @endif
    </div>
    <div class="card-body">
        {{-- FullCalendar container --}}
        <div id="calendar" style="min-height:500px"></div>
    </div>
</div>

@php
    // $events is already json_encode()'d by the controller; just pass it through
    $eventsJson = is_string($events) ? $events : json_encode($events ?? []);
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {
    var events = {!! $eventsJson !!};

    // Simple table fallback if FullCalendar is not loaded
    if (typeof FullCalendar === 'undefined') {
        var calEl = document.getElementById('calendar');
        if (events && events.length > 0) {
            var html = '<table><thead><tr><th>Title</th><th>Start</th><th>End</th></tr></thead><tbody>';
            events.forEach(function(e) {
                html += '<tr><td>' + (e.title || '') + '</td><td>' + (e.start || '') + '</td><td>' + (e.end || '') + '</td></tr>';
            });
            html += '</tbody></table>';
            calEl.innerHTML = html;
        } else {
            calEl.innerHTML = '<div class="empty-state"><p>No events to display</p></div>';
        }
        return;
    }

    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        events: events,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        eventColor: '#14b8a6'
    });
    calendar.render();
});
</script>

@endsection
