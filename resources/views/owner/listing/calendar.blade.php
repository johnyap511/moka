@extends('owner.layout')

@section('title', 'Calendar')
@section('page-title', 'Calendar')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css">
<style>
/* ── Container ─────────────────────────────────────────── */
.cal-wrap {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,.07), 0 4px 16px rgba(0,0,0,.05);
    padding: 24px 28px 28px;
    overflow: hidden;
}

/* ── Stats strip ───────────────────────────────────────── */
.cal-stats {
    display: flex;
    gap: 12px;
    margin-bottom: 22px;
    flex-wrap: wrap;
}
.cal-stat {
    flex: 1;
    min-width: 110px;
    background: #f8faff;
    border: 1px solid #e8ecf8;
    border-radius: 10px;
    padding: 12px 16px;
    text-align: center;
}
.cal-stat .s-val {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.1;
}
.cal-stat .s-lbl {
    font-size: 11.5px;
    color: #64748b;
    margin-top: 3px;
    text-transform: uppercase;
    letter-spacing: .04em;
}

/* ── FullCalendar overrides ────────────────────────────── */
.fc {
    font-family: inherit;
}
.fc .fc-toolbar {
    align-items: center;
    margin-bottom: 16px;
}
.fc .fc-toolbar-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
}
.fc .fc-button {
    background: #fff !important;
    border: 1px solid #e2e8f0 !important;
    color: #374151 !important;
    font-size: 12.5px !important;
    font-weight: 500 !important;
    border-radius: 7px !important;
    padding: 5px 13px !important;
    box-shadow: none !important;
    transition: background .15s, border-color .15s;
}
.fc .fc-button:hover {
    background: #f1f5f9 !important;
    border-color: #cbd5e1 !important;
}
.fc .fc-button-active,
.fc .fc-button:focus {
    background: #eff6ff !important;
    border-color: #93c5fd !important;
    color: #1d4ed8 !important;
    outline: none !important;
    box-shadow: none !important;
}
.fc .fc-button-primary:not(:disabled).fc-button-active {
    background: #dbeafe !important;
    border-color: #93c5fd !important;
    color: #1d4ed8 !important;
}
.fc .fc-col-header-cell {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    padding: 8px 0;
}
.fc .fc-col-header-cell-cushion {
    font-size: 11.5px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .05em;
    text-decoration: none !important;
}
.fc .fc-daygrid-day-number {
    font-size: 12.5px;
    font-weight: 500;
    color: #64748b;
    padding: 6px 8px;
    text-decoration: none !important;
}
.fc .fc-day-today {
    background: #f0f9ff !important;
}
.fc .fc-day-today .fc-daygrid-day-number {
    background: #3b82f6;
    color: #fff;
    border-radius: 50%;
    width: 26px;
    height: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    margin: 5px;
}
.fc .fc-daygrid-day-frame {
    min-height: 90px;
}
.fc td, .fc th {
    border-color: #f1f5f9 !important;
}

/* ── Events ────────────────────────────────────────────── */
.fc-event {
    border: none !important;
    border-radius: 5px !important;
    font-size: 11.5px !important;
    font-weight: 500 !important;
    padding: 2px 7px !important;
    cursor: pointer !important;
    transition: filter .15s, transform .1s;
    margin: 1px 2px !important;
}
.fc-event:hover {
    filter: brightness(.92);
    transform: translateY(-1px);
}
.fc-event .fc-event-title {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.fc-daygrid-event-dot { display: none !important; }
.fc-list-event:hover td { background: #f8faff !important; }
.fc-list-event-dot { border-color: inherit !important; }

/* ── Popup ─────────────────────────────────────────────── */
#ev-popup {
    display: none;
    position: fixed;
    z-index: 9999;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,.14), 0 2px 8px rgba(0,0,0,.08);
    padding: 0;
    width: 270px;
    overflow: hidden;
    animation: popIn .15s ease;
}
@keyframes popIn {
    from { opacity:0; transform:translateY(6px) scale(.97); }
    to   { opacity:1; transform:translateY(0)  scale(1);    }
}
#ev-popup .pop-header {
    padding: 14px 16px 12px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
}
#ev-popup .pop-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 4px;
}
#ev-popup .pop-name {
    font-weight: 600;
    font-size: 14px;
    color: #0f172a;
    flex: 1;
    line-height: 1.3;
}
#ev-popup .pop-close {
    color: #94a3b8;
    cursor: pointer;
    font-size: 18px;
    line-height: 1;
    flex-shrink: 0;
    transition: color .15s;
}
#ev-popup .pop-close:hover { color: #1e293b; }
#ev-popup .pop-divider { height: 1px; background: #f1f5f9; }
#ev-popup .pop-body { padding: 12px 16px; }
#ev-popup .pop-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12.5px;
    padding: 5px 0;
    border-bottom: 1px solid #f8fafc;
}
#ev-popup .pop-row:last-child { border-bottom: none; }
#ev-popup .pop-label { color: #64748b; }
#ev-popup .pop-val { font-weight: 500; color: #1e293b; }
#ev-popup .pop-footer {
    padding: 10px 16px 14px;
}
#ev-popup .pop-btn {
    display: block;
    text-align: center;
    background: #eff6ff;
    color: #2563eb;
    font-size: 12.5px;
    font-weight: 600;
    padding: 8px;
    border-radius: 8px;
    text-decoration: none;
    transition: background .15s;
}
#ev-popup .pop-btn:hover { background: #dbeafe; }

</style>
@endpush

@section('content')

@php
    $eventsArr = is_string($events) ? json_decode($events, true) : [];
    $eventsArr = $eventsArr ?? [];
    $totalBookings = count($eventsArr);

    // Count bookings this month
    $thisMonth = date('Y-m');
    $thisMonthCount = collect($eventsArr)->filter(function($e) use ($thisMonth) {
        return isset($e['start']) && str_starts_with($e['start'], $thisMonth);
    })->count();

    // Upcoming (from today)
    $today = date('Y-m-d');
    $upcomingCount = collect($eventsArr)->filter(function($e) use ($today) {
        return isset($e['start']) && $e['start'] >= $today;
    })->count();
@endphp

<div class="page-header">
    <div>
        <h1>
            @if(!empty($listing))
                {{ $listing->title ?? $listing->name }}
            @else
                Booking Calendar
            @endif
        </h1>
        <p style="color:#64748b;font-size:13.5px;margin-top:2px">Click any booking block to see details.</p>
    </div>
    @if(!empty($listing))
    <a href="/owner/listing" class="btn btn-secondary">← Back to Listings</a>
    @endif
</div>

{{-- Stats strip --}}
<div class="cal-stats">
    <div class="cal-stat">
        <div class="s-val">{{ $totalBookings }}</div>
        <div class="s-lbl">Total Bookings</div>
    </div>
    <div class="cal-stat">
        <div class="s-val">{{ $thisMonthCount }}</div>
        <div class="s-lbl">This Month</div>
    </div>
    <div class="cal-stat">
        <div class="s-val">{{ $upcomingCount }}</div>
        <div class="s-lbl">Upcoming</div>
    </div>
    <div class="cal-stat">
        <div class="s-val">{{ $totalBookings - $upcomingCount }}</div>
        <div class="s-lbl">Past</div>
    </div>
</div>

{{-- Calendar --}}
<div class="cal-wrap">
    <div id="calendar"></div>
</div>

{{-- Popup --}}
<div id="ev-popup">
    <div class="pop-header">
        <span class="pop-dot" id="pop-dot"></span>
        <span class="pop-name" id="pop-name"></span>
        <span class="pop-close" onclick="closePopup()">×</span>
    </div>
    <div class="pop-divider"></div>
    <div class="pop-body">
        <div class="pop-row"><span class="pop-label">Check-in</span>  <span class="pop-val" id="pop-in"></span></div>
        <div class="pop-row"><span class="pop-label">Check-out</span> <span class="pop-val" id="pop-out"></span></div>
        <div class="pop-row"><span class="pop-label">Nights</span>    <span class="pop-val" id="pop-nights"></span></div>
        <div class="pop-row"><span class="pop-label">Guests</span>    <span class="pop-val" id="pop-guests"></span></div>
        <div class="pop-row"><span class="pop-label">Source</span>    <span class="pop-val" id="pop-source"></span></div>
    </div>
    <div class="pop-divider"></div>
    <div class="pop-footer">
        <a id="pop-link" href="#" class="pop-btn">View Full Details →</a>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
<script>
(function () {
    var raw = {!! $events !!};

    // Pastel palette — light bg, readable text
    var palette = [
        { bg: '#dbeafe', text: '#1d4ed8' },  // blue
        { bg: '#d1fae5', text: '#065f46' },  // green
        { bg: '#fef3c7', text: '#92400e' },  // amber
        { bg: '#ede9fe', text: '#5b21b6' },  // violet
        { bg: '#fee2e2', text: '#991b1b' },  // red
        { bg: '#cffafe', text: '#0e7490' },  // cyan
        { bg: '#fce7f3', text: '#9d174d' },  // pink
        { bg: '#dcfce7', text: '#166534' },  // emerald
    ];
    var colourMap = {};
    var colIdx = 0;

    function getColour(name) {
        if (!colourMap[name]) {
            colourMap[name] = palette[colIdx % palette.length];
            colIdx++;
        }
        return colourMap[name];
    }

    var fcEvents = raw.map(function(e) {
        var c = getColour(e.title || 'Guest');
        return {
            id:    e.id,
            title: e.title || 'Guest',
            start: e.start,
            end:   e.end,
            backgroundColor: c.bg,
            textColor:       c.text,
            extendedProps: {
                nights: e.nights,
                guest:  e.guest,
                source: e.source || '—',
                colour: c.bg,
            }
        };
    });

    // Go to most recent booking month if no current-month bookings
    var initialDate = new Date();
    var hasCurrentMonth = raw.some(function(e) {
        return e.start && e.start.slice(0,7) === initialDate.toISOString().slice(0,7);
    });
    if (!hasCurrentMonth && raw.length > 0) {
        var sorted = raw.slice().sort(function(a,b){ return (b.start||'').localeCompare(a.start||''); });
        var latest = sorted[0].start;
        if (latest) initialDate = new Date(latest);
    }

    var cal = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView:  'dayGridMonth',
        initialDate:  initialDate,
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,listMonth'
        },
        buttonText:  { listMonth: 'List' },
        events:      fcEvents,
        eventClick:  showPopup,
        eventDidMount: function(info) {
            info.el.title = info.event.title;
        },
        datesSet: updateStats,
    });
    cal.render();


    // Stats strip — update for visible month
    function updateStats(dateInfo) {
        var start = dateInfo.startStr.slice(0,7);
        var end   = dateInfo.endStr.slice(0,7);
        var inView = raw.filter(function(e) {
            return e.start && e.start.slice(0,7) >= start && e.start.slice(0,7) <= end;
        });
        // highlight this-month stat card label
        var lbl = document.querySelectorAll('.cal-stat')[1];
        if (lbl) {
            var monthName = new Date(dateInfo.view.currentStart).toLocaleString('default',{month:'long',year:'numeric'});
            lbl.querySelector('.s-lbl').textContent = monthName;
            lbl.querySelector('.s-val').textContent = inView.length;
        }
    }

    // Popup
    function showPopup(info) {
        var e = info.event;
        var c = e.extendedProps.colour || '#dbeafe';
        document.getElementById('pop-dot').style.background   = c;
        document.getElementById('pop-name').textContent       = e.title;
        document.getElementById('pop-in').textContent         = e.startStr;
        document.getElementById('pop-out').textContent        = e.endStr;
        document.getElementById('pop-nights').textContent     = e.extendedProps.nights ?? '—';
        document.getElementById('pop-guests').textContent     = e.extendedProps.guest  ?? '—';
        document.getElementById('pop-source').textContent     = e.extendedProps.source ?? '—';
        var link = document.getElementById('pop-link');
        link.href = e.id ? '/owner/book/' + e.id : '#';

        var popup = document.getElementById('ev-popup');
        popup.style.display = 'block';

        var rect = info.el.getBoundingClientRect();
        var scrollY = window.scrollY || document.documentElement.scrollTop;
        var scrollX = window.scrollX || document.documentElement.scrollLeft;
        var top  = rect.bottom + scrollY + 8;
        var left = rect.left  + scrollX;
        var pw = 270;
        if (left + pw > window.innerWidth - 12) left = window.innerWidth - pw - 12;
        if (top + 280 > window.innerHeight + scrollY) top = rect.top + scrollY - 290;
        popup.style.top  = top  + 'px';
        popup.style.left = left + 'px';
    }

    window.closePopup = function() {
        document.getElementById('ev-popup').style.display = 'none';
    };

    document.addEventListener('click', function(ev) {
        var popup = document.getElementById('ev-popup');
        if (popup.style.display === 'block'
            && !popup.contains(ev.target)
            && !ev.target.closest('.fc-event')) {
            popup.style.display = 'none';
        }
    });

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
})();
</script>
@endpush
