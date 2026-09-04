@extends('admin.layout')
@section('title', 'Listing Calendar')
@section('page-title', 'Listing Calendar')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
<style>
#calendar { font-family: inherit; }
.fc .fc-toolbar-title { font-size: 16px; font-weight: 600; }
.fc .fc-button { background: var(--bg-secondary); border: 1px solid var(--border); color: var(--text-primary); font-size: 12px; padding: 5px 12px; box-shadow: none; text-transform: capitalize; }
.fc .fc-button:hover { background: var(--border); }
.fc .fc-button-primary:not(:disabled).fc-button-active,
.fc .fc-button-primary:not(:disabled):active { background: var(--teal); border-color: var(--teal); color: #fff; }
.fc .fc-today-button { background: var(--teal); border-color: var(--teal); color: #fff; }
.fc .fc-daygrid-day.fc-day-today { background: rgba(20,184,166,.07); }
.fc-event { border-radius: 4px !important; border: none !important; padding: 2px 6px !important; font-size: 12px !important; }
.fc-event-title { font-weight: 500; }
.fc-daygrid-event-dot { display: none; }
/* Event popup */
.cal-popup { position:fixed; background:#fff; border-radius:12px; padding:18px 20px; box-shadow:0 8px 32px rgba(0,0,0,.18); z-index:9999; min-width:240px; max-width:300px; font-size:13px; }
.cal-popup h4 { margin:0 0 8px; font-size:14px; font-weight:600; }
.cal-popup .row { display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px solid var(--border); }
.cal-popup .row:last-child { border:none; }
.cal-popup .lbl { color:var(--text-secondary); font-size:12px; }
.cal-popup-close { position:absolute; top:10px; right:14px; cursor:pointer; font-size:18px; color:var(--text-secondary); }
</style>
@endpush

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
            @include('admin.partials.combobox', [
                'id'             => 'property',
                'name'           => 'listing_id',
                'items'          => $allListings,
                'selected'       => $selectedId ?? null,
                'placeholder'    => 'Type to search properties…',
                'style'          => 'min-width:280px;max-width:440px',
                'submitOnSelect' => true,
            ])
        </form>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h2>
            @if(isset($listing) && $listing)
                {{ $listing->name }}
            @else
                Calendar View
            @endif
        </h2>
        @php $cnt = is_string($events) ? count(json_decode($events, true) ?? []) : 0; @endphp
        <span class="badge badge-blue">{{ $cnt }} booking{{ $cnt == 1 ? '' : 's' }}</span>
    </div>
    <div class="card-body" style="padding:16px">
        <div id="calendar"></div>
    </div>
</div>

{{-- Event detail popup --}}
<div id="cal-popup" class="cal-popup" style="display:none">
    <span class="cal-popup-close" onclick="closePopup()">×</span>
    <h4 id="pp-name"></h4>
    <div class="row"><span class="lbl">Booking ID</span><span id="pp-booking-id" class="mono"></span></div>
    <div class="row"><span class="lbl">EZEE Res No</span><span id="pp-ezee-res" class="mono"></span></div>
    <div class="row"><span class="lbl">Check In</span><span id="pp-start"></span></div>
    <div class="row"><span class="lbl">Check Out</span><span id="pp-end"></span></div>
    <div class="row"><span class="lbl">Nights</span><span id="pp-nights"></span></div>
    <div class="row"><span class="lbl">Price / Night</span><span id="pp-price-night"></span></div>
    <div class="row"><span class="lbl">SST</span><span id="pp-sst"></span></div>
    <div class="row"><span class="lbl">Cleaning Fee</span><span id="pp-cleaning"></span></div>
    <div class="row"><span class="lbl">SST (CF)</span><span id="pp-sst-cf"></span></div>
    <div class="row"><span class="lbl">M&amp;A Fee</span><span id="pp-ota"></span></div>
    <div class="row" style="font-weight:600"><span class="lbl" style="font-weight:600">Total</span><span id="pp-price"></span></div>
    <div class="row" style="justify-content:flex-end;gap:6px;margin-top:8px"><a id="pp-open" href="#" class="btn btn-secondary btn-sm">Open booking</a><a id="pp-unit" href="#" class="btn btn-secondary btn-sm">Unit / room move</a></div>
    <div style="margin-top:12px">
        <a id="pp-view" href="#" class="btn btn-primary btn-sm" style="width:100%;text-align:center">View Booking</a>
    </div>
</div>

@php
    $eventsJson = is_string($events) ? $events : json_encode($events ?? []);
@endphp

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var events = {!! $eventsJson !!};

    // Assign a colour palette cycling through bookings
    var colors = ['#14b8a6','#3b82f6','#8b5cf6','#f59e0b','#ef4444','#10b981','#6366f1','#ec4899'];
    events = events.map(function(e, i) {
        return Object.assign({}, e, { backgroundColor: colors[i % colors.length], borderColor: 'transparent' });
    });

    var cal = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        height: 'auto',
        events: events,
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,listMonth'
        },
        buttonText: {
            today:     'Today',
            month:     'Month',
            listMonth: 'List'
        },
        eventDisplay: 'block',
        dayMaxEvents: 4,
        eventClick: function(info) {
            var e = info.event;
            var ext = e.extendedProps;
            var fmt = function(v) { return (v !== null && v !== undefined && v !== '') ? 'RM ' + parseFloat(v).toFixed(2) : '—'; };
            document.getElementById('pp-name').textContent        = e.title || 'Guest';
            document.getElementById('pp-booking-id').textContent  = '#' + (ext.booking_id || e.id);
            document.getElementById('pp-open').href = '/admin/book/' + (ext.booking_id || e.id) + '/edit';
            document.getElementById('pp-unit').href = '/admin/book/' + (ext.booking_id || e.id) + '/edit#unit-card';
            document.getElementById('pp-ezee-res').textContent    = ext.ezee_res || '—';
            document.getElementById('pp-start').textContent       = e.startStr;
            document.getElementById('pp-end').textContent         = e.endStr;
            document.getElementById('pp-nights').textContent      = ext.nights || calcNights(e.startStr, e.endStr);
            document.getElementById('pp-price-night').textContent = fmt(ext.price_night);
            document.getElementById('pp-sst').textContent         = fmt(ext.sst);
            document.getElementById('pp-cleaning').textContent    = fmt(ext.cleaning_fee);
            document.getElementById('pp-sst-cf').textContent      = fmt(ext.sst_cf);
            document.getElementById('pp-ota').textContent         = fmt(ext.ota_fee);
            document.getElementById('pp-price').textContent       = ext.price ? 'RM ' + parseFloat(ext.price).toFixed(2) : '—';
            document.getElementById('pp-view').href               = '/admin/book/' + e.id;
            // Position popup near click
            var popup = document.getElementById('cal-popup');
            popup.style.display = 'block';
            var rect = info.el.getBoundingClientRect();
            var top  = rect.bottom + window.scrollY + 8;
            var left = Math.min(rect.left + window.scrollX, window.innerWidth - 320);
            popup.style.top  = top + 'px';
            popup.style.left = Math.max(8, left) + 'px';
        }
    });
    cal.render();

    function calcNights(s, e) {
        if (!s || !e) return '—';
        return Math.round((new Date(e) - new Date(s)) / 86400000);
    }

    document.addEventListener('click', function(ev) {
        var popup = document.getElementById('cal-popup');
        if (popup.style.display !== 'none' && !popup.contains(ev.target) && !ev.target.closest('.fc-event')) {
            popup.style.display = 'none';
        }
    });
});

function closePopup() {
    document.getElementById('cal-popup').style.display = 'none';
}
</script>
@endpush

@endsection
