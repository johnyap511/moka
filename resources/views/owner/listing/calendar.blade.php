@extends('owner.layout')

@section('title', 'Calendar')
@section('page-title', 'Calendar')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css">
<style>
    #calendar-wrap { background:#fff; border-radius:8px; padding:20px; border:1px solid var(--border,#e5e7eb); }
    .fc .fc-toolbar-title { font-size:1.1rem; font-weight:600; }
    .fc .fc-button { background:var(--primary,#2563eb); border-color:var(--primary,#2563eb); font-size:13px; }
    .fc .fc-button:hover { background:#1d4ed8; border-color:#1d4ed8; }
    .fc .fc-button-active { background:#1e40af !important; border-color:#1e40af !important; }
    .fc-event { cursor:pointer; font-size:12px; padding:1px 4px; border-radius:3px; }
    .fc-daygrid-event-dot { display:none; }

    /* Popup */
    #ev-popup {
        display:none; position:fixed; z-index:9999;
        background:#fff; border:1px solid #e5e7eb; border-radius:10px;
        box-shadow:0 10px 30px rgba(0,0,0,.15); padding:16px 18px;
        min-width:240px; max-width:300px;
    }
    #ev-popup .ev-title { font-weight:600; font-size:14px; margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid #f0f0f0; }
    #ev-popup .ev-row { display:flex; justify-content:space-between; font-size:13px; padding:3px 0; color:#374151; }
    #ev-popup .ev-label { color:#6b7280; }
    #ev-popup .ev-close { position:absolute; top:10px; right:12px; cursor:pointer; color:#9ca3af; font-size:16px; line-height:1; }
    #ev-popup .ev-close:hover { color:#374151; }
    #ev-popup .ev-link { display:block; margin-top:12px; text-align:center; font-size:13px; padding:6px; background:#f3f4f6; border-radius:6px; color:#2563eb; text-decoration:none; }
    #ev-popup .ev-link:hover { background:#e5e7eb; }
</style>
@endpush

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
        <p>Click any booking to see details.</p>
    </div>
    @if(!empty($listing))
    <a href="/owner/listing" class="btn btn-secondary">Back to Listings</a>
    @endif
</div>

<div id="calendar-wrap">
    <div id="calendar"></div>
</div>

{{-- Event detail popup --}}
<div id="ev-popup">
    <span class="ev-close" onclick="closePopup()">✕</span>
    <div class="ev-title" id="ep-name"></div>
    <div class="ev-row"><span class="ev-label">Check-in</span><span id="ep-in"></span></div>
    <div class="ev-row"><span class="ev-label">Check-out</span><span id="ep-out"></span></div>
    <div class="ev-row"><span class="ev-label">Nights</span><span id="ep-nights"></span></div>
    <div class="ev-row"><span class="ev-label">Guests</span><span id="ep-guests"></span></div>
    <div class="ev-row"><span class="ev-label">Source</span><span id="ep-source"></span></div>
    <a id="ep-link" href="#" class="ev-link" style="display:none">View Details →</a>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
<script>
(function () {
    var rawEvents = {!! $events !!};

    // Colour palette — cycle through for variety
    var colours = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4','#ec4899','#84cc16'];
    var colourMap = {};
    var colourIdx = 0;

    var fcEvents = rawEvents.map(function(e) {
        if (!colourMap[e.title]) {
            colourMap[e.title] = colours[colourIdx % colours.length];
            colourIdx++;
        }
        return {
            id:          e.id,
            title:       e.title || 'Guest',
            start:       e.start,
            end:         e.end,
            color:       colourMap[e.title],
            extendedProps: {
                nights:  e.nights,
                guest:   e.guest,
                source:  e.source || '—',
            }
        };
    });

    var cal = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView:     'dayGridMonth',
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,listMonth'
        },
        buttonText: { listMonth: 'List' },
        events:      fcEvents,
        eventClick:  function(info) { showPopup(info); },
        eventDidMount: function(info) {
            info.el.title = info.event.title;
        }
    });
    cal.render();

    function showPopup(info) {
        var e = info.event;
        document.getElementById('ep-name').textContent    = e.title;
        document.getElementById('ep-in').textContent      = e.startStr;
        document.getElementById('ep-out').textContent     = e.endStr;
        document.getElementById('ep-nights').textContent  = e.extendedProps.nights ?? '—';
        document.getElementById('ep-guests').textContent  = e.extendedProps.guest  ?? '—';
        document.getElementById('ep-source').textContent  = e.extendedProps.source ?? '—';

        var link = document.getElementById('ep-link');
        if (e.id) {
            link.href = '/owner/book/' + e.id;
            link.style.display = 'block';
        } else {
            link.style.display = 'none';
        }

        var popup = document.getElementById('ev-popup');
        popup.style.display = 'block';

        // Position near the clicked element
        var rect = info.el.getBoundingClientRect();
        var top  = rect.bottom + window.scrollY + 6;
        var left = rect.left   + window.scrollX;
        var pw   = 300;
        if (left + pw > window.innerWidth - 10) { left = window.innerWidth - pw - 10; }
        popup.style.top  = top  + 'px';
        popup.style.left = left + 'px';
    }

    window.closePopup = function() {
        document.getElementById('ev-popup').style.display = 'none';
    };

    // Close popup on outside click
    document.addEventListener('click', function(e) {
        var popup = document.getElementById('ev-popup');
        if (popup.style.display === 'block' && !popup.contains(e.target) && !e.target.closest('.fc-event')) {
            popup.style.display = 'none';
        }
    });
})();
</script>
@endpush
