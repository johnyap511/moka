@extends('owner.layout')
@section('title', 'Calendar')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css">
<style>
.filter-bar{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.07);padding:14px 20px;display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.cal-wrap{background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.07);padding:24px}
/* FC overrides */
.fc{font-family:inherit}
.fc .fc-toolbar-title{font-size:1rem;font-weight:600;color:#1e293b}
.fc .fc-button{background:#fff!important;border:1px solid #e2e8f0!important;color:#374151!important;font-size:12.5px!important;font-weight:500!important;border-radius:7px!important;padding:5px 13px!important;box-shadow:none!important}
.fc .fc-button:hover{background:#f1f5f9!important}
.fc .fc-button-active,.fc .fc-button:focus{background:#fff7ed!important;border-color:#F36523!important;color:#F36523!important;outline:none!important;box-shadow:none!important}
.fc .fc-button-primary:not(:disabled).fc-button-active{background:#fff7ed!important;border-color:#F36523!important;color:#F36523!important}
.fc .fc-col-header-cell{background:#f8fafc;border-bottom:2px solid #e2e8f0;padding:8px 0}
.fc .fc-col-header-cell-cushion{font-size:11.5px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;text-decoration:none!important}
.fc .fc-daygrid-day-number{font-size:12.5px;font-weight:500;color:#64748b;padding:6px 8px;text-decoration:none!important}
.fc .fc-day-today{background:#fff8f5!important}
.fc .fc-day-today .fc-daygrid-day-number{background:#F36523;color:#fff;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;padding:0;margin:5px}
.fc .fc-daygrid-day-frame{min-height:80px}
.fc td,.fc th{border-color:#f1f5f9!important}
.fc-event{border:none!important;border-radius:5px!important;font-size:11.5px!important;font-weight:500!important;padding:2px 7px!important;cursor:pointer!important}
.fc-event:hover{filter:brightness(.92)}
.fc-daygrid-event-dot{display:none!important}
/* Popup */
#ev-popup{display:none;position:fixed;z-index:9999;background:#fff;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.14);padding:0;width:270px;overflow:hidden;animation:popIn .15s ease}
@keyframes popIn{from{opacity:0;transform:translateY(6px) scale(.97)}to{opacity:1;transform:none}}
#ev-popup .pop-header{padding:14px 16px 12px;display:flex;align-items:flex-start;justify-content:space-between;gap:8px;background:#F36523}
#ev-popup .pop-name{font-weight:600;font-size:13.5px;color:#fff;flex:1;line-height:1.3}
#ev-popup .pop-close{color:rgba(255,255,255,.8);cursor:pointer;font-size:20px;line-height:1;flex-shrink:0}
#ev-popup .pop-close:hover{color:#fff}
#ev-popup .pop-divider{height:1px;background:#f1f5f9}
#ev-popup .pop-body{padding:12px 16px}
#ev-popup .pop-row{display:flex;justify-content:space-between;align-items:center;font-size:12.5px;padding:5px 0;border-bottom:1px solid #f8fafc}
#ev-popup .pop-row:last-child{border-bottom:none}
#ev-popup .pop-label{color:#64748b}
#ev-popup .pop-val{font-weight:500;color:#1e293b}
#ev-popup .pop-footer{padding:10px 16px 14px}
#ev-popup .pop-btn{display:block;text-align:center;background:#fff7ed;color:#F36523;font-size:12.5px;font-weight:600;padding:8px;border-radius:8px;text-decoration:none;transition:background .15s}
#ev-popup .pop-btn:hover{background:#fff0e0}
</style>
@endpush

@section('content')

{{-- Filter bar --}}
<form method="GET" action="/owner/calendar" class="filter-bar">
    <select name="listing_id" class="form-select" style="flex:1;min-width:200px;max-width:420px" onchange="this.form.submit()">
        @foreach($allListings ?? [] as $l)
            <option value="{{ $l->id }}" {{ ($selectedId == $l->id) ? 'selected' : '' }}>{{ $l->name }}</option>
        @endforeach
    </select>
    <input type="month" name="date" class="form-input" value="{{ isset($selDate) ? $selDate->format('Y-m') : date('Y-m') }}" style="width:160px">
    <button type="submit" class="btn" style="background:#0a5c4a;color:#fff;padding:9px 28px;font-weight:600">Update</button>
</form>

{{-- Calendar --}}
<div class="cal-wrap">
    <div id="calendar"></div>
</div>

{{-- Popup --}}
<div id="ev-popup">
    <div class="pop-header">
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
    var raw         = {!! $events !!};
    var initialDate = '{{ $initialDate ?? date('Y-m-d') }}';

    var fcEvents = raw.map(function(e) {
        return {
            id:    e.id,
            title: e.title || 'Booked',
            start: e.start,
            end:   e.end,
            backgroundColor: '#F36523',
            textColor:       '#fff',
            borderColor:     'transparent',
            extendedProps: {
                nights: e.nights,
                guest:  e.guest,
                source: e.source || '—',
            }
        };
    });

    var cal = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView:  'dayGridMonth',
        initialDate:  initialDate,
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek,listMonth'
        },
        buttonText: { timeGridWeek: 'week', listMonth: 'list' },
        events:     fcEvents,
        eventClick: showPopup,
        height:     'auto',
    });
    cal.render();

    function showPopup(info) {
        var e = info.event;
        document.getElementById('pop-name').textContent    = e.title;
        document.getElementById('pop-in').textContent      = e.startStr;
        document.getElementById('pop-out').textContent     = e.endStr;
        document.getElementById('pop-nights').textContent  = e.extendedProps.nights ?? '—';
        document.getElementById('pop-guests').textContent  = e.extendedProps.guest  ?? '—';
        document.getElementById('pop-source').textContent  = e.extendedProps.source ?? '—';
        document.getElementById('pop-link').href           = e.id ? '/owner/book/' + e.id : '#';

        var popup = document.getElementById('ev-popup');
        popup.style.display = 'block';
        var rect  = info.el.getBoundingClientRect();
        var scrollY = window.scrollY || 0, scrollX = window.scrollX || 0;
        var top  = rect.bottom + scrollY + 8;
        var left = rect.left  + scrollX;
        if (left + 270 > window.innerWidth - 12) left = window.innerWidth - 282;
        if (top + 280  > window.innerHeight + scrollY) top = rect.top + scrollY - 290;
        popup.style.top  = top  + 'px';
        popup.style.left = left + 'px';
    }

    window.closePopup = function() {
        document.getElementById('ev-popup').style.display = 'none';
    };

    document.addEventListener('click', function(ev) {
        var popup = document.getElementById('ev-popup');
        if (popup.style.display === 'block' && !popup.contains(ev.target) && !ev.target.closest('.fc-event')) {
            popup.style.display = 'none';
        }
    });
})();
</script>
@endpush
