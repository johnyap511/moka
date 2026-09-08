@extends('owner.layout')
@section('title', 'Calendar')

@php
    $channelColour = \App\Support\Channel::colours();
@endphp

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css">
<style>
.cal-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px}
.cal-head h1{font-size:24px;font-weight:700;letter-spacing:-.4px}
.cal-head p{color:var(--text-secondary);margin-top:4px;font-size:14px}
.cal-toolbar{display:grid;grid-template-columns:minmax(220px,1fr) 190px auto;gap:10px;align-items:center;background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:12px 14px;margin-bottom:14px}
.cal-toolbar .btn{padding:10px 22px;font-size:13.5px;font-weight:600;justify-content:center}
.cal-legend{display:flex;flex-wrap:wrap;gap:6px 14px;align-items:center;font-size:12px;color:var(--text-secondary);margin:0 4px 12px}
.cal-legend .k{display:inline-flex;align-items:center;gap:6px}
.cal-legend .sw{width:12px;height:12px;border-radius:4px}
.cal-legend .today-sw{width:18px;height:18px;border-radius:50%;background:#F36523;color:#fff;font-size:10px;font-weight:700;display:inline-flex;align-items:center;justify-content:center}
.cal-wrap{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:18px 20px 20px}
.cal-stats{display:flex;gap:18px;flex-wrap:wrap;font-size:12.5px;color:var(--text-secondary);margin-bottom:12px}
.cal-stats b{color:var(--text);font-size:14px}

/* FullCalendar, restyled */
.fc{font-family:inherit;--fc-border-color:#eef0f3;--fc-today-bg-color:transparent;--fc-page-bg-color:#fff;--fc-neutral-bg-color:#f8f9fb}
.fc .fc-toolbar{margin-bottom:14px!important;gap:10px}
.fc .fc-toolbar-title{font-size:17px;font-weight:700;color:var(--text);letter-spacing:-.3px}
.fc .fc-button{background:#fff!important;border:1px solid var(--border)!important;color:var(--text)!important;font-size:12.5px!important;font-weight:500!important;border-radius:8px!important;padding:6px 12px!important;box-shadow:none!important;text-transform:none!important}
.fc .fc-button:hover{background:#f5f5f7!important}
.fc .fc-button-primary:not(:disabled).fc-button-active,.fc .fc-button-primary:not(:disabled):active{background:#fff7ed!important;border-color:#F36523!important;color:#F36523!important}
.fc .fc-button:focus{outline:none!important;box-shadow:0 0 0 3px rgba(243,101,35,.15)!important}
.fc .fc-prev-button,.fc .fc-next-button{padding:6px 9px!important}
.fc .fc-today-button{text-transform:capitalize!important}
.fc .fc-col-header-cell{background:#fff;border-bottom:1px solid var(--border)!important;padding:8px 0}
.fc .fc-col-header-cell-cushion{font-size:11px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.08em;text-decoration:none!important}
.fc .fc-daygrid-day-frame{min-height:96px;padding:2px}
.fc .fc-daygrid-day-top{flex-direction:row;padding:4px 6px 0}
.fc .fc-daygrid-day-number{font-size:12.5px;font-weight:500;color:var(--text-secondary);text-decoration:none!important;padding:2px 0;width:24px;height:24px;display:flex;align-items:center;justify-content:center;border-radius:50%}
.fc .fc-day-other .fc-daygrid-day-number{color:#c4c4c8}
.fc .fc-day-past:not(.fc-day-today){background:#fbfbfc}
.fc .fc-day-sat,.fc .fc-day-sun{background:#fcfcfd}
.fc .fc-day-today{background:#fff7f1!important;box-shadow:inset 0 2px 0 #F36523}
.fc .fc-day-today .fc-daygrid-day-number{background:#F36523;color:#fff;font-weight:700}
.fc td,.fc th{border-color:#eef0f3!important}
.fc .fc-daygrid-event{border-radius:6px!important;border:none!important;font-size:11.5px!important;font-weight:600!important;padding:3px 8px!important;margin:1px 3px!important;cursor:pointer;box-shadow:0 1px 0 rgba(0,0,0,.05);line-height:1.3}
.fc .fc-daygrid-event .fc-event-main{color:#fff}
.fc .fc-daygrid-event:hover{filter:brightness(.94)}
.fc .fc-daygrid-event .ev{display:flex;align-items:center;gap:6px;min-width:0}
.fc .fc-daygrid-event .ev .n{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1}
.fc .fc-daygrid-event .ev .c{font-size:10px;font-weight:600;opacity:.9;background:rgba(255,255,255,.22);padding:1px 6px;border-radius:10px;flex-shrink:0}
.fc .fc-daygrid-more-link{font-size:11px;color:#F36523;font-weight:600}
.fc .fc-list{border-radius:10px;overflow:hidden}
.fc .fc-list-day-cushion{background:#f8f9fb!important}
.fc .fc-list-event:hover td{background:#fff7f1}
.fc .fc-list-event-dot{border-width:5px!important}
@media (max-width:1024px){.cal-toolbar{grid-template-columns:1fr 170px auto}}
@media (max-width:700px){
  .cal-toolbar{grid-template-columns:1fr}
  .cal-head h1{font-size:20px}
  .cal-wrap{padding:12px}
  .fc .fc-toolbar{flex-wrap:wrap}
  .fc .fc-toolbar-chunk{display:flex;align-items:center;gap:6px}
  .fc .fc-toolbar-title{font-size:15px}
  .fc .fc-button{padding:6px 9px!important;font-size:12px!important}
  .fc .fc-daygrid-day-frame{min-height:64px}
  .fc .fc-daygrid-event{font-size:10.5px!important;padding:2px 5px!important;margin:1px 2px!important}
  .fc .fc-daygrid-event .ev .c{display:none}
}

/* Booking popup */
#ev-popup{display:none;position:fixed;z-index:9999;background:#fff;border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,.18);width:290px;overflow:hidden;animation:popIn .15s ease}
@keyframes popIn{from{opacity:0;transform:translateY(6px) scale(.97)}to{opacity:1;transform:none}}
#ev-popup .pop-header{padding:14px 16px 12px;display:flex;align-items:flex-start;justify-content:space-between;gap:8px;color:#fff}
#ev-popup .pop-name{font-weight:700;font-size:14px;flex:1;line-height:1.3}
#ev-popup .pop-chip{font-size:11px;font-weight:600;background:rgba(255,255,255,.22);padding:2px 8px;border-radius:10px;margin-top:4px;display:inline-block}
#ev-popup .pop-close{color:rgba(255,255,255,.85);cursor:pointer;font-size:22px;line-height:1;flex-shrink:0}
#ev-popup .pop-body{padding:10px 16px 6px}
#ev-popup .pop-row{display:flex;justify-content:space-between;align-items:center;font-size:12.5px;padding:6px 0;border-bottom:1px solid #f3f4f6}
#ev-popup .pop-row:last-child{border-bottom:none}
#ev-popup .pop-label{color:var(--text-secondary)}
#ev-popup .pop-val{font-weight:600;color:var(--text)}
#ev-popup .pop-footer{padding:8px 16px 14px}
#ev-popup .pop-btn{display:block;text-align:center;background:#fff7ed;color:#F36523;font-size:12.5px;font-weight:600;padding:9px;border-radius:9px}
#ev-popup .pop-btn:hover{background:#ffedd5}
</style>
@endpush

@section('content')

<div class="cal-head">
    <div>
        <h1>Calendar</h1>
        <p>{{ $listing->name ?? 'Your unit' }} · {{ isset($selDate) ? $selDate->format('F Y') : date('F Y') }}</p>
    </div>
</div>

<form method="GET" action="/owner/calendar" class="cal-toolbar">
    <select name="listing_id" class="form-select" onchange="this.form.submit()" aria-label="Unit">
        @foreach($allListings ?? [] as $l)
            <option value="{{ $l->id }}" {{ ($selectedId == $l->id) ? 'selected' : '' }}>{{ $l->name }}</option>
        @endforeach
    </select>
    <input type="month" name="date" class="form-input" value="{{ isset($selDate) ? $selDate->format('Y-m') : date('Y-m') }}" aria-label="Month">
    <button type="submit" class="btn btn-primary">Update</button>
</form>

<div class="cal-legend" id="cal-legend">
    <span class="k"><span class="today-sw">{{ date('j') }}</span> Today</span>
</div>

<div class="cal-wrap">
    <div class="cal-stats" id="cal-stats"></div>
    <div id="calendar"></div>
</div>

<div id="ev-popup">
    <div class="pop-header" id="pop-header">
        <div style="flex:1;min-width:0"><div class="pop-name" id="pop-name"></div><span class="pop-chip" id="pop-chip"></span></div>
        <span class="pop-close" onclick="closePopup()">×</span>
    </div>
    <div class="pop-body">
        <div class="pop-row"><span class="pop-label">Check-in</span><span class="pop-val" id="pop-in"></span></div>
        <div class="pop-row"><span class="pop-label">Check-out</span><span class="pop-val" id="pop-out"></span></div>
        <div class="pop-row"><span class="pop-label">Nights</span><span class="pop-val" id="pop-nights"></span></div>
        <div class="pop-row"><span class="pop-label">Guests</span><span class="pop-val" id="pop-guests"></span></div>
    </div>
    <div class="pop-footer"><a id="pop-link" href="#" class="pop-btn">View booking details →</a></div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
<script>
(function () {
    var raw         = {!! $events !!};
    var colours     = {!! json_encode($channelColour) !!};
    var initialDate = '{{ $initialDate ?? date('Y-m-d') }}';
    var fallback    = '#64748b';

    function colourOf(channel) { return colours[channel] || fallback; }

    var fcEvents = raw.map(function (e) {
        var ch = e.channel || 'Other';
        return {
            id: e.id, title: e.name || 'Guest', start: e.start, end: e.end,
            backgroundColor: colourOf(ch), borderColor: 'transparent', textColor: '#fff',
            extendedProps: { nights: e.nights, guest: e.guest, channel: ch }
        };
    });

    // Legend: only the channels present on this unit, in palette order.
    var present = {}; raw.forEach(function (e) { present[e.channel || 'Other'] = true; });
    var legend = document.getElementById('cal-legend');
    Object.keys(colours).concat(['Other']).forEach(function (ch) {
        if (!present[ch]) return;
        var k = document.createElement('span'); k.className = 'k';
        k.innerHTML = '<span class="sw" style="background:' + colourOf(ch) + '"></span>' + ch;
        legend.appendChild(k);
    });

    var fmt = function (d) { return d ? new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) : '—'; };

    var cal = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        initialDate: initialDate,
        firstDay: 1,
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listMonth' },
        buttonText: { today: 'Today', dayGridMonth: 'Month', listMonth: 'List' },
        events: fcEvents,
        eventClick: showPopup,
        height: 'auto',
        dayMaxEvents: 3,
        fixedWeekCount: false,
        eventContent: function (arg) {
            var ch = arg.event.extendedProps.channel;
            return { html: '<div class="ev"><span class="n">' + escapeHtml(arg.event.title) + '</span><span class="c">' + escapeHtml(ch) + '</span></div>' };
        },
        datesSet: function (info) { monthStats(info.view.currentStart, info.view.currentEnd); }
    });
    cal.render();

    function monthStats(start, end) {
        var s = start.toISOString().slice(0, 10), e = end.toISOString().slice(0, 10);
        var nights = 0, count = 0;
        raw.forEach(function (ev) {
            var a = ev.start > s ? ev.start : s, b = ev.end < e ? ev.end : e;
            var n = Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000);
            if (n > 0) { nights += n; count++; }
        });
        var days = Math.round((end - start) / 86400000);
        document.getElementById('cal-stats').innerHTML =
            '<span><b>' + count + '</b> booking' + (count === 1 ? '' : 's') + '</span>' +
            '<span><b>' + nights + '</b> of ' + days + ' nights sold</span>' +
            '<span><b>' + (days ? Math.round(nights / days * 100) : 0) + '%</b> occupancy</span>';
    }

    function escapeHtml(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

    function showPopup(info) {
        var e = info.event, ch = e.extendedProps.channel;
        document.getElementById('pop-header').style.background = colourOf(ch);
        document.getElementById('pop-name').textContent   = e.title;
        document.getElementById('pop-chip').textContent   = ch;
        document.getElementById('pop-in').textContent     = fmt(e.startStr);
        document.getElementById('pop-out').textContent    = fmt(e.endStr);
        document.getElementById('pop-nights').textContent = e.extendedProps.nights ?? '—';
        document.getElementById('pop-guests').textContent = e.extendedProps.guest ?? '—';
        document.getElementById('pop-link').href          = e.id ? '/owner/book/' + e.id : '#';
        var popup = document.getElementById('ev-popup'); popup.style.display = 'block';
        var r = info.el.getBoundingClientRect(), w = 290, h = popup.offsetHeight || 260;
        var left = Math.min(Math.max(8, r.left), window.innerWidth - w - 8);
        var top  = r.bottom + 8; if (top + h > window.innerHeight - 8) top = Math.max(8, r.top - h - 8);
        popup.style.top = top + 'px'; popup.style.left = left + 'px';
    }
    window.closePopup = function () { document.getElementById('ev-popup').style.display = 'none'; };
    document.addEventListener('click', function (ev) {
        var p = document.getElementById('ev-popup');
        if (p.style.display === 'block' && !p.contains(ev.target) && !ev.target.closest('.fc-event')) p.style.display = 'none';
    });
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') closePopup(); });
})();
</script>
@endpush
