<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Admin') — MOKA</title>
@include('partials.favicon')
<style>
:root{
  --sidebar-w:240px;
  --topbar-h:56px;
  --bg:#f5f5f7;
  --surface:#ffffff;
  --sidebar-bg:#1c1c1e;
  --sidebar-hover:rgba(255,255,255,.08);
  --sidebar-active:rgba(255,255,255,.12);
  --text:#1d1d1f;
  --text-secondary:#6e6e73;
  --border:#d2d2d7;
  --teal:#0a8a72;
  --teal-light:#e0f5f1;
  --blue:#0071e3;
  --red:#ff3b30;
  --orange:#ff9500;
  --green:#30d158;
  --radius:12px;
  --shadow:0 1px 4px rgba(0,0,0,.08),0 0 0 1px rgba(0,0,0,.04);
}
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:-apple-system,BlinkMacSystemFont,'SF Pro Text','Segoe UI',sans-serif;font-size:14px;background:var(--bg);color:var(--text)}
a{color:inherit;text-decoration:none}
/* Sidebar */
.sidebar{position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;background:var(--sidebar-bg);display:flex;flex-direction:column;z-index:100;overflow-y:auto}
.sidebar-brand{height:var(--topbar-h);display:flex;align-items:center;padding:0 20px;border-bottom:1px solid rgba(255,255,255,.08);flex-shrink:0}
.sidebar-brand span{font-size:18px;font-weight:700;color:#fff;letter-spacing:-.5px}
.sidebar-brand small{font-size:11px;color:rgba(255,255,255,.4);margin-left:8px;font-weight:400;letter-spacing:0}
.brand-link{display:flex;align-items:center}
.brand-logo{height:34px;width:auto;display:block}
.sidebar-section{padding:20px 12px 8px;font-size:11px;font-weight:600;color:rgba(255,255,255,.3);letter-spacing:.6px;text-transform:uppercase}
.sidebar-link{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;margin:1px 8px;color:rgba(255,255,255,.7);font-size:13.5px;font-weight:450;transition:background .15s,color .15s;cursor:pointer}
.sidebar-link:hover{background:var(--sidebar-hover);color:#fff}
.sidebar-link.active{background:var(--sidebar-active);color:#fff}
.sidebar-link svg{width:16px;height:16px;flex-shrink:0;opacity:.8}
.sidebar-link.active svg{opacity:1}
.sidebar-footer{margin-top:auto;padding:12px;border-top:1px solid rgba(255,255,255,.08)}
/* Topbar */
.topbar{position:fixed;top:0;left:var(--sidebar-w);right:0;height:var(--topbar-h);background:rgba(255,255,255,.9);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 28px;z-index:99;gap:12px}
.topbar-title{font-size:17px;font-weight:600;letter-spacing:-.3px;flex:1}
.topbar-user{display:flex;align-items:center;gap:10px;font-size:13px;color:var(--text-secondary)}
.topbar-user .avatar{width:30px;height:30px;border-radius:50%;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;flex-shrink:0}
.topbar-badge{background:var(--bg);border:1px solid var(--border);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:500;color:var(--text-secondary)}
/* Content */
.content{margin-left:var(--sidebar-w);margin-top:var(--topbar-h);min-height:calc(100vh - var(--topbar-h));padding:28px}
/* Page header */
.page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;gap:16px}
.page-header h1{font-size:24px;font-weight:700;letter-spacing:-.5px}
.page-header p{color:var(--text-secondary);font-size:14px;margin-top:3px}
/* Cards */
.card{background:var(--surface);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
.card-header{padding:18px 20px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px}
.card-header h2{font-size:15px;font-weight:600;letter-spacing:-.2px}
.card-body{padding:20px}
/* Stats grid */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:var(--surface);border-radius:var(--radius);box-shadow:var(--shadow);padding:20px}
.stat-card .val{font-size:32px;font-weight:700;letter-spacing:-1px;line-height:1}
.stat-card .lbl{font-size:12px;color:var(--text-secondary);margin-top:6px;font-weight:500}
.stat-card .change{font-size:11px;margin-top:6px;display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:10px}
.change-up{background:#d1fae5;color:#065f46}
.change-dn{background:#fee2e2;color:#991b1b}
/* Table */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{padding:10px 14px;text-align:left;font-size:11.5px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border);white-space:nowrap}
tbody tr{border-bottom:1px solid #f0f0f2;transition:background .1s}
tbody tr:hover{background:#fafafa}
tbody tr:last-child{border-bottom:none}
td{padding:12px 14px;font-size:13.5px;vertical-align:middle}
td.mono{font-family:'SF Mono',Menlo,monospace;font-size:12.5px}
/* Buttons */
.btn{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;border:none;transition:all .15s;line-height:1.4;text-decoration:none}
.btn-primary{background:var(--teal);color:#fff}
.btn-primary:hover{background:#0a7a65}
.btn-secondary{background:var(--surface);color:var(--text);border:1px solid var(--border)}
.btn-secondary:hover{background:#f0f0f2}
.btn-danger{background:#fff0ef;color:var(--red);border:1px solid #ffd0ce}
.btn-danger:hover{background:#ffe0de}
.btn-blue{background:var(--blue);color:#fff}
.btn-blue:hover{background:#0062c4}
.btn-sm{padding:5px 12px;font-size:12px;border-radius:6px}
.btn-icon{padding:6px;width:32px;height:32px;justify-content:center}
/* Badges */
.badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:11.5px;font-weight:500;white-space:nowrap}
.badge-green{background:#d1fae5;color:#065f46}
.badge-red{background:#fee2e2;color:#991b1b}
.badge-orange{background:#fff7ed;color:#c2410c}
.badge-blue{background:#eff6ff;color:#1d4ed8}
.badge-gray{background:#f3f4f6;color:#4b5563}
.badge-teal{background:var(--teal-light);color:var(--teal)}
/* Forms */
.form-group{margin-bottom:18px}
.form-label{display:block;font-size:13px;font-weight:500;margin-bottom:6px;color:var(--text)}
.form-input,.form-select,.form-textarea{width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:inherit;color:var(--text);background:var(--surface);transition:border-color .15s,box-shadow .15s;outline:none}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(10,138,114,.12)}
.form-textarea{min-height:100px;resize:vertical}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.form-help{font-size:12px;color:var(--text-secondary);margin-top:4px}
/* Type-ahead combobox (see x-combobox partial) */
.combo{position:relative}
/* Fixed, not absolute: .card uses overflow:hidden for its rounded corners,
   which would otherwise clip the dropdown. Position comes from JS. z-index
   clears the assign modal at 200. */
.combo-list{display:none;position:fixed;z-index:300;margin:0;padding:4px;list-style:none;max-height:240px;overflow-y:auto;background:var(--surface);border:1px solid var(--border);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12)}
.combo-list.open{display:block}
.combo-list li{padding:8px 10px;font-size:13px;border-radius:6px;cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.combo-list li[aria-selected="true"],.combo-list li:hover{background:#f1f5f9}
.combo-list li.empty{color:var(--text-secondary);cursor:default}
.combo-list li.empty:hover{background:transparent}
.form-error{font-size:12px;color:var(--red);margin-top:4px}
/* Search bar */
.search-bar{display:flex;align-items:center;gap:10px;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:8px 14px;min-width:240px}
.search-bar input{border:none;outline:none;font-size:13.5px;flex:1;font-family:inherit;color:var(--text);background:transparent}
.search-bar svg{color:var(--text-secondary);flex-shrink:0}
/* Alerts */
.alert{padding:12px 16px;border-radius:10px;font-size:13.5px;margin-bottom:20px;display:flex;align-items:center;gap:10px}
.alert-success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
.alert-warning{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}
/* Pagination */
.pagination{display:flex;align-items:center;gap:4px;margin-top:16px;justify-content:flex-end}
.pagination a,.pagination span{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;font-size:13px;border:1px solid var(--border);color:var(--text);transition:all .15s}
.pagination a:hover{background:var(--bg)}
.pagination span.active{background:var(--teal);color:#fff;border-color:var(--teal)}
/* Empty state */
.empty-state{text-align:center;padding:60px 20px;color:var(--text-secondary)}
.empty-state svg{margin-bottom:16px;opacity:.3}
.empty-state p{font-size:15px;font-weight:500;color:var(--text);margin-bottom:6px}
.empty-state small{font-size:13px}
/* Utility */
.flex{display:flex}.items-center{align-items:center}.justify-between{justify-content:space-between}.gap-2{gap:8px}.gap-3{gap:12px}.mt-1{margin-top:4px}.mt-2{margin-top:8px}.mt-3{margin-top:12px}.mb-2{margin-bottom:8px}.mb-4{margin-bottom:16px}.text-sm{font-size:12.5px}.text-secondary{color:var(--text-secondary)}.font-600{font-weight:600}.truncate{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.divider{height:1px;background:var(--border);margin:20px 0}
/* Sidebar submenu */
.sidebar-sub{padding-left:12px}
.sidebar-sub .sidebar-link{font-size:13px;padding:7px 12px}
/* Action cell */
.actions{display:flex;gap:6px;align-items:center}
/* ---- Responsive: tablet and phone ---------------------------------------
   The sidebar slides in over the page below 1024px; a menu button in the top
   bar opens it. Tables keep their columns and scroll sideways inside .table-wrap
   instead of squeezing every cell into a tall sliver. */
.menu-btn{display:none;width:36px;height:36px;border:1px solid var(--border);border-radius:8px;background:var(--surface);align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;color:var(--text)}
.menu-btn svg{width:18px;height:18px}
.topbar-logout{display:inline-flex}
.topbar-logo{display:none;align-items:center;flex-shrink:0}
.topbar-logo img{height:28px;width:auto;display:block}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:99}
.sidebar-overlay.show{display:block}
.table-wrap{-webkit-overflow-scrolling:touch}
.table-wrap table{min-width:640px}
.table-wrap.wide table{min-width:1100px}
@media (max-width:1024px){
  .sidebar{transform:translateX(-100%);transition:transform .2s ease;box-shadow:none;height:100vh;height:100dvh;padding-bottom:env(safe-area-inset-bottom)}
  .sidebar-footer{margin-top:12px}
  .sidebar.open{transform:translateX(0);box-shadow:0 0 40px rgba(0,0,0,.35)}
  .topbar{left:0;padding:0 16px}
  .menu-btn{display:inline-flex}
  .topbar-logo{display:flex}
  .content{margin-left:0;padding:16px}
  .page-header{flex-direction:column;align-items:stretch;gap:10px}
  .page-header .flex{flex-wrap:wrap}
  .page-header .btn{flex:1 1 auto;justify-content:center}
  .page-header h1{font-size:20px}
  .card-header{flex-wrap:wrap}
  .card-body{padding:16px}
  .search-bar{min-width:0;width:100%}
  .pagination{flex-wrap:wrap;justify-content:center}
  .stat-card .val{font-size:26px}
}
@media (max-width:700px){
  .topbar-badge{display:none}
  [style*="grid-template-columns"]{grid-template-columns:1fr!important}
  .card-body form > *,.card-body form label > *{min-width:0;max-width:100%}
  .card-body input,.card-body select,.card-body textarea{max-width:100%}
  .topbar-user span{display:none}
  .form-row,.form-row-3{grid-template-columns:1fr}
  .btn{min-height:38px}
  .btn-sm{min-height:32px}
  .actions{flex-wrap:wrap}
  td,thead th{padding:10px 10px}
  .stats-grid{grid-template-columns:1fr 1fr;gap:10px}
  .content{padding:12px}
  .card{border-radius:10px}
  [style*="width:760px"],[style*="width:640px"],[style*="width: 900px"],[style*="width:900px"],[style*="width:420px"]{width:100%!important;max-width:100%!important}
}
@media (max-width:420px){
  .stats-grid{grid-template-columns:1fr}
}
/* Date and month fields: the whole field opens the picker, not just the icon,
   and the icon itself is a comfortable tap target. */
input[type="month"],input[type="date"]{cursor:pointer}
/* No text selection or tap flash on touch devices; the app should feel like
   an app. Fields the user types into keep selection. */
@media (hover:none) and (pointer:coarse){
  body{-webkit-user-select:none;user-select:none;-webkit-tap-highlight-color:transparent;-webkit-touch-callout:none}
  input,textarea,[contenteditable="true"]{-webkit-user-select:text;user-select:text}
}
/* The month and date fields: no blue highlight on the focused segment. */
input[type="month"]::-webkit-datetime-edit-month-field:focus,input[type="month"]::-webkit-datetime-edit-year-field:focus,
input[type="date"]::-webkit-datetime-edit-day-field:focus,input[type="date"]::-webkit-datetime-edit-month-field:focus,input[type="date"]::-webkit-datetime-edit-year-field:focus{background:transparent;color:inherit;outline:none}
input[type="month"],input[type="date"]{-webkit-user-select:none;user-select:none;caret-color:transparent}
input[type="month"]::selection,input[type="date"]::selection{background:transparent}
</style>
@stack('styles')
</head>
<body>

{{-- SIDEBAR --}}
<aside class="sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <a href="/admin/dashboard" class="brand-link" aria-label="Moka admin home"><img src="{{ asset('images/layout/logo-w.svg') }}" alt="Moka" class="brand-logo"></a><small>Admin</small>
    </div>

    <div class="sidebar-section">Main</div>
    @if(admin_can('dashboard.view'))
    <a href="/admin/dashboard" class="sidebar-link {{ request()->is('admin/dashboard') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Dashboard
    </a>
    @endif
    @if(admin_can('calendar.view'))
    <a href="/admin/calendar" class="sidebar-link {{ request()->is('admin/calendar') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Calendar
    </a>
    @endif

    @if(admin_can('listings.view'))
    <div class="sidebar-section">Properties</div>
    <a href="/admin/listing" class="sidebar-link {{ request()->is('admin/listing*') && !request()->is('admin/listing/*/book*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        Listings
    </a>
    <a href="/admin/group" class="sidebar-link {{ request()->is('admin/group*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Groups
    </a>
    @endif

    @if(admin_can('bookings.view') || admin_can('finance.view') || admin_can('ezee.view'))
    <div class="sidebar-section">Operations</div>
    @endif
    @if(admin_can('bookings.view'))
    <a href="/admin/book" class="sidebar-link {{ request()->is('admin/book*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        Bookings
    </a>
    @endif
    @if(admin_can('finance.view'))
    {{-- Payments hidden on request; the route still works if visited directly. --}}
    {{-- <a href="/admin/payment/upcoming" class="sidebar-link {{ request()->is('admin/payment*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Payments
    </a> --}}
    <a href="/admin/listing/chart/report" class="sidebar-link {{ request()->is('admin/listing/chart/report*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Monthly Report
    </a>
    @endif
    @if(admin_can('ezee.view'))
    <a href="/admin/ezee/booking" class="sidebar-link {{ request()->is('admin/ezee/booking') || request()->is('admin/ezee/assigned_booking') || request()->is('admin/ezee/unassigned_booking') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        EZEE Bookings
    </a>
    <a href="/admin/ezee/booking_report" class="sidebar-link {{ request()->is('admin/ezee/booking_report*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        EZEE Report
    </a>
    <a href="/admin/ezee/revenue-export" class="sidebar-link {{ request()->is('admin/ezee/revenue-export*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Revenue Export (EZEE)
    </a>
    @endif
    @if(admin_can('ezee.manage'))
    <a href="/admin/ezee/upload_bookings" class="sidebar-link {{ request()->is('admin/ezee/upload_bookings*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
        Upload Bookings
    </a>
    <a href="/admin/ezee/assignment-log?method=conflict" class="sidebar-link {{ request()->is('admin/ezee/assignment*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
        Needs Review
    </a>
    <a href="/admin/ezee/room-mapping" class="sidebar-link {{ request()->is('admin/ezee/room-mapping*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        Room Mapping
    </a>
    <a href="/admin/ezee/group" class="sidebar-link {{ request()->is('admin/ezee/group*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        EZEE Groups
    </a>
    @endif
    @if(admin_can('ezee.history'))
    <a href="/admin/booking/histroy/api" class="sidebar-link {{ request()->is('admin/booking/histroy/api*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Historical API
    </a>
    @endif
    {{-- Listing Approval and Approvals hidden on request; the @if wrapper goes
         with them since these were its only two entries. Routes still work if
         visited directly. --}}
    {{-- @if(admin_can('bookings.view'))
    <a href="/admin/approval/month_wise" class="sidebar-link {{ request()->is('admin/approval/month*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
        Listing Approval
    </a>
    <a href="/admin/approval/review" class="sidebar-link {{ request()->is('admin/approval/review*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Approvals
    </a>
    @endif --}}

    @if(admin_can('owners.view') || admin_can('users.view') || admin_can('roles.manage'))
    <div class="sidebar-section">People</div>
    @endif
    @if(admin_can('owners.view'))
    <a href="/admin/owners" class="sidebar-link {{ request()->is('admin/owners*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        Owners
    </a>
    @endif
    @if(admin_can('users.view'))
    <a href="/admin/users" class="sidebar-link {{ request()->is('admin/users*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        Guests
    </a>
    @endif
    @if(admin_can('roles.manage'))
    <a href="/admin/admin" class="sidebar-link {{ request()->is('admin/admin*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        Admin Users
    </a>
    @endif

    @if(admin_can('settings.view') || admin_can('roles.manage'))
    <div class="sidebar-section">Settings</div>
    @endif
    @if(admin_can('settings.view'))
    <a href="/admin/setting/zone" class="sidebar-link {{ request()->is('admin/setting/zone*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Zones
    </a>
    <a href="/admin/setting/amenities" class="sidebar-link {{ request()->is('admin/setting/amenities*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
        Amenities
    </a>
    <a href="/admin/setting/estimate" class="sidebar-link {{ request()->is('admin/setting/estimate*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Estimates
    </a>
    <a href="/admin/setting/logs" class="sidebar-link {{ request()->is('admin/setting/logs*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        System Logs
    </a>
    <a href="/admin/subscribe" class="sidebar-link {{ request()->is('admin/subscribe*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        Subscribers
    </a>
    <a href="/admin/filemanager" class="sidebar-link {{ request()->is('admin/filemanager*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
        File Manager
    </a>
    @endif
    @if(admin_can('roles.manage'))
    <a href="/admin/setting/admin-roles" class="sidebar-link {{ request()->is('admin/setting/admin-roles*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
        Admin Roles
    </a>
    @endif

    <div class="sidebar-footer">
        <form action="/logout" method="POST">
            @csrf
            <button type="submit" class="sidebar-link" style="width:100%;background:none;border:none;cursor:pointer;text-align:left">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Sign Out
            </button>
        </form>
    </div>
</aside>

{{-- TOPBAR --}}
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar(false)"></div>
<header class="topbar">
    <button type="button" class="menu-btn" aria-label="Open menu" aria-controls="adminSidebar" aria-expanded="false" onclick="toggleSidebar()">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <a href="/admin/dashboard" class="topbar-logo" aria-label="Moka admin home"><img src="{{ asset('images/layout/logo-orange.svg') }}" alt="Moka"></a>
    <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
    <span class="topbar-badge">{{ date('D, d M Y') }}</span>
    <div class="topbar-user">
        <div class="avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
        <span>{{ Auth::user()->name }}</span>
    </div>
    <form action="/logout" method="POST" style="margin:0">
        @csrf
        <button type="submit" class="menu-btn topbar-logout" aria-label="Log out" title="Log out">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </button>
    </form>
</header>

{{-- MAIN CONTENT --}}
<main class="content">
    @if(session('success'))
        <div class="alert alert-success">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        </div>
    @endif

    @yield('content')
</main>

<script>
/**
 * Type-ahead select. Renders over a hidden input so the posted value stays the
 * record id while the visible field is free text.
 *
 * opts: { id, items:[{id,name}], required, submitOnSelect }
 * Returns { reset, close, set } so callers can drive it (the EZEE assign modal
 * resets it each time it opens).
 */
window.makeCombo = function (opts) {
    var search = document.getElementById(opts.id + '-search'),
        value  = document.getElementById(opts.id + '-value'),
        list   = document.getElementById(opts.id + '-list'),
        error  = document.getElementById(opts.id + '-error'),
        items  = opts.items || [],
        form   = search ? search.closest('form') : null,
        matches = [], active = -1;

    if (!search) return { reset: function () {}, close: function () {}, set: function () {} };

    function close() {
        list.classList.remove('open');
        search.setAttribute('aria-expanded', 'false');
        active = -1;
    }

    // The list is position:fixed to escape ancestor overflow, so it has to be
    // placed manually, and re-placed while it is open.
    function place() {
        var r = search.getBoundingClientRect(),
            gap = 4,
            below = window.innerHeight - r.bottom,
            above = r.top;

        list.style.width = r.width + 'px';
        list.style.left  = r.left + 'px';

        if (below < 160 && above > below) {          // not enough room: flip up
            list.style.top       = 'auto';
            list.style.bottom    = (window.innerHeight - r.top + gap) + 'px';
            list.style.maxHeight = Math.max(120, Math.min(360, above - 12)) + 'px';
        } else {
            list.style.bottom    = 'auto';
            list.style.top       = (r.bottom + gap) + 'px';
            list.style.maxHeight = Math.max(120, Math.min(360, below - 12)) + 'px';
        }
    }

    function reposition() { if (list.classList.contains('open')) place(); }
    window.addEventListener('resize', reposition);
    // Capture phase so scrolling any ancestor container repositions it too.
    window.addEventListener('scroll', reposition, true);
    function set(item) {
        value.value  = item ? item.id : '';
        search.value = item ? item.name : '';
        if (error) error.style.display = 'none';
    }
    function reset() { set(null); close(); }
    function choose(item) {
        set(item);
        close();
        if (opts.submitOnSelect && form) form.submit();
    }
    function highlight(i) {
        active = i;
        Array.prototype.forEach.call(list.children, function (li, n) {
            li.setAttribute('aria-selected', n === i ? 'true' : 'false');
        });
        if (i >= 0 && list.children[i]) list.children[i].scrollIntoView({ block: 'nearest' });
    }
    function render(q) {
        q = (q || '').toLowerCase().trim();
        matches = q ? items.filter(function (u) {
            return (u.name || '').toLowerCase().indexOf(q) !== -1;
        }) : items.slice();

        list.innerHTML = '';
        if (!matches.length) {
            var none = document.createElement('li');
            none.className = 'empty';
            none.textContent = 'No match';
            list.appendChild(none);
        } else {
            // Every match is rendered, not the first 50. Listings sort
            // alphabetically and there are more than 50 before EkoCheras begins,
            // so a cap made most of the estate unreachable without typing. The
            // list scrolls, and a few hundred items cost nothing.
            matches.forEach(function (u) {
                var li = document.createElement('li');
                li.textContent = u.name;
                li.setAttribute('role', 'option');
                li.addEventListener('mousedown', function (e) { e.preventDefault(); choose(u); });
                list.appendChild(li);
            });
        }
        list.classList.add('open');
        search.setAttribute('aria-expanded', 'true');
        place();
        highlight(matches.length ? 0 : -1);
    }

    search.addEventListener('input', function () { value.value = ''; render(this.value); });
    search.addEventListener('focus', function () { render(''); if (!window.matchMedia('(pointer:coarse)').matches) this.select(); });
    search.addEventListener('blur',  function () { setTimeout(close, 120); });
    search.addEventListener('keydown', function (e) {
        var last = matches.length - 1;
        if (!list.classList.contains('open') && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) { render(this.value); return; }
        if (e.key === 'ArrowDown')    { e.preventDefault(); highlight(Math.min(active + 1, last)); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(Math.max(active - 1, 0)); }
        else if (e.key === 'Enter' && list.classList.contains('open') && active >= 0 && matches[active]) {
            e.preventDefault(); choose(matches[active]);
        }
        else if (e.key === 'Escape')  { close(); }
    });

    // Hidden inputs are exempt from browser constraint validation, so a
    // required combobox has to be checked here.
    if (opts.required && form) {
        form.addEventListener('submit', function (e) {
            if (!value.value) {
                e.preventDefault();
                if (error) error.style.display = 'block';
                search.focus();
            }
        });
    }

    return { reset: reset, close: close, set: set };
};
</script>
<script>
// Off-canvas sidebar for tablet and phone. Closes on overlay tap, Escape, or
// when a link is followed, so the page underneath is never left covered.
function toggleSidebar(open) {
    var sb = document.getElementById('adminSidebar'), ov = document.getElementById('sidebarOverlay'), btn = document.querySelector('.menu-btn');
    if (!sb) return;
    var isOpen = typeof open === 'boolean' ? open : !sb.classList.contains('open');
    sb.classList.toggle('open', isOpen);
    if (ov) ov.classList.toggle('show', isOpen);
    if (btn) btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    document.body.style.overflow = isOpen && window.innerWidth <= 1024 ? 'hidden' : '';
}
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') toggleSidebar(false); });
document.addEventListener('click', function (e) { if (e.target.closest && e.target.closest('#adminSidebar a')) toggleSidebar(false); });
window.addEventListener('resize', function () { if (window.innerWidth > 1024) toggleSidebar(false); });
</script>
<script>
// Chrome opens a date or month picker only from its small icon. showPicker()
// opens it from a tap anywhere on the field; browsers without it keep the icon.
// The field itself never takes focus, so Chrome cannot highlight a segment:
// the press is swallowed and only the picker opens.
// Phones and tablets open their own picker on a tap anywhere in the field,
// so they are left alone. On a mouse, Chrome only opens the picker from the
// icon; a click anywhere on the field opens it, and the field is blurred
// so no segment is highlighted.
var coarsePointer = window.matchMedia('(pointer:coarse)').matches;
function openPicker(e) {
    if (coarsePointer) return;
    var el = e.target;
    if (!(el instanceof HTMLInputElement) || (el.type !== 'month' && el.type !== 'date')) return;
    if (typeof el.showPicker !== 'function') return;   // older browser: native behaviour
    try { el.showPicker(); e.preventDefault(); } catch (err) { return; }
    if (document.activeElement === el) el.blur();
}
document.addEventListener('click', openPicker);
document.addEventListener('keydown', function (e) {
    var el = e.target;
    if ((e.key === 'Enter' || e.key === ' ') && el instanceof HTMLInputElement && (el.type === 'month' || el.type === 'date') && typeof el.showPicker === 'function') {
        e.preventDefault(); try { el.showPicker(); } catch (err) {}
    }
});
</script>
<script>
// A table with many columns keeps them readable on a phone by scrolling
// sideways inside its wrapper, instead of squeezing every cell to a sliver.
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.content table').forEach(function (t) {
        if (t.style.minWidth || t.closest('.fc')) return;
        var cols = (t.tHead && t.tHead.rows[0]) ? t.tHead.rows[0].cells.length : (t.rows[0] ? t.rows[0].cells.length : 0);
        if (cols >= 5) t.style.minWidth = Math.max(640, cols * 120) + 'px';
        var wrap = t.parentElement;
        if (wrap && getComputedStyle(wrap).overflowX !== 'auto' && getComputedStyle(wrap).overflowX !== 'scroll') wrap.style.overflowX = 'auto';
    });
});
</script>
@stack('scripts')
</body>
</html>
