<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Owner') — MOKA</title>
@include('partials.favicon')
<style>
:root{
  --sidebar-w:220px;
  --orange:#F36523;
  --orange-dark:#d4541a;
  --teal:#0a8a72;
  --bg:#f5f5f7;
  --border:#e5e5ea;
  --text:#1d1d1f;
  --text-secondary:#6e6e73;
  --radius:12px;
  --shadow:0 1px 4px rgba(0,0,0,.08),0 0 0 1px rgba(0,0,0,.04);
}
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:14px;background:var(--bg);color:var(--text)}
a{color:inherit;text-decoration:none}

/* Sidebar */
.sidebar{position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;background:var(--orange);display:flex;flex-direction:column;z-index:100;overflow-y:auto}
.sidebar-brand{padding:20px 18px 16px;border-bottom:1px solid rgba(255,255,255,.2);flex-shrink:0;display:flex;align-items:center;gap:10px}
.sidebar-brand .logo-icon{width:36px;height:36px;background:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sidebar-brand .logo-icon svg{width:22px;height:22px;color:var(--orange)}
.sidebar-brand .logo-text{font-size:20px;font-weight:700;color:#fff;letter-spacing:-.5px}
.brand-link{display:flex;align-items:center}
.brand-logo{height:44px;width:auto;display:block}
.brand-logo-sm{height:30px;width:auto;display:block}
.sidebar-link{display:flex;align-items:center;justify-content:space-between;padding:11px 18px;color:rgba(255,255,255,.85);font-size:13.5px;font-weight:500;transition:background .15s;cursor:pointer;border:none;background:none;width:100%;text-align:left}
.sidebar-link:hover{background:rgba(0,0,0,.1);color:#fff}
.sidebar-link.active{background:rgba(0,0,0,.15);color:#fff;font-weight:600}
.sidebar-link .link-inner{display:flex;align-items:center;gap:10px}
.sidebar-link svg{width:18px;height:18px;flex-shrink:0}
.sidebar-spacer{flex:1}
.sidebar-divider{height:1px;background:rgba(255,255,255,.2);margin:8px 0}

/* Content */
.content{margin-left:var(--sidebar-w);min-height:100vh;padding:28px;background:var(--bg)}

/* Cards */
.card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
.card-header{padding:16px 20px 12px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px}
.card-header h2{font-size:15px;font-weight:600}
.card-body{padding:20px}

/* Table */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{padding:10px 14px;text-align:left;font-size:11.5px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border);white-space:nowrap}
tbody tr{border-bottom:1px solid #f0f0f2;transition:background .1s}
tbody tr:hover{background:#fafafa}
tbody tr:last-child{border-bottom:none}
td{padding:12px 14px;font-size:13.5px;vertical-align:middle}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;border:none;transition:all .15s;line-height:1.4;text-decoration:none}
.btn-primary{background:var(--orange);color:#fff}
.btn-primary:hover{background:var(--orange-dark)}
.btn-secondary{background:#fff;color:var(--text);border:1px solid var(--border)}
.btn-secondary:hover{background:#f0f0f2}
.btn-sm{padding:5px 12px;font-size:12px;border-radius:6px}

/* Badges */
.badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:11.5px;font-weight:500;white-space:nowrap}
.badge-green{background:#d1fae5;color:#065f46}
.badge-red{background:#fee2e2;color:#991b1b}
.badge-orange{background:#fff7ed;color:#c2410c}
.badge-blue{background:#eff6ff;color:#1d4ed8}
.badge-gray{background:#f3f4f6;color:#4b5563}
.badge-teal{background:#e0f5f1;color:var(--teal)}

/* Forms */
.form-group{margin-bottom:18px}
.form-label{display:block;font-size:13px;font-weight:500;margin-bottom:6px;color:var(--text)}
.form-input,.form-select,.form-textarea{width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:inherit;color:var(--text);background:#fff;transition:border-color .15s,box-shadow .15s;outline:none}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--orange);box-shadow:0 0 0 3px rgba(243,101,35,.12)}

/* Alerts */
.alert{padding:12px 16px;border-radius:10px;font-size:13.5px;margin-bottom:20px;display:flex;align-items:center;gap:10px}
.alert-success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}

/* Empty state */
.empty-state{text-align:center;padding:60px 20px;color:var(--text-secondary)}
.empty-state svg{margin-bottom:16px;opacity:.3}
.empty-state p{font-size:15px;font-weight:500;color:var(--text);margin-bottom:6px}
.empty-state small{font-size:13px}

/* Utility */
.text-sm{font-size:12.5px}.text-secondary{color:var(--text-secondary)}.font-600{font-weight:600}
.actions{display:flex;gap:6px;align-items:center}
/* ---- Responsive: tablet and phone ---- */
.mobile-bar{display:none;position:sticky;top:0;z-index:98;height:52px;background:#fff;border-bottom:1px solid var(--border);align-items:center;gap:12px;padding:0 14px;margin:-28px -28px 16px}
.mobile-bar .menu-btn{width:36px;height:36px;border:1px solid var(--border);border-radius:8px;background:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text)}
.mobile-bar .menu-btn svg{width:18px;height:18px}
.mobile-bar .brand{font-weight:700;font-size:16px;color:var(--orange)}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:99}
.sidebar-overlay.show{display:block}
.table-wrap{-webkit-overflow-scrolling:touch}
.table-wrap table{min-width:560px}
@media (max-width:1024px){
  .sidebar{transform:translateX(-100%);transition:transform .2s ease}
  .sidebar.open{transform:translateX(0);box-shadow:0 0 40px rgba(0,0,0,.35)}
  .content{margin-left:0;padding:16px}
  .mobile-bar{display:flex;margin:-16px -16px 16px}
  .card-header{flex-wrap:wrap}
  .card-body{padding:16px}
  [style*="width:760px"],[style*="width:420px"]{width:100%!important;max-width:100%!important}
}
@media (max-width:700px){
  .btn{min-height:38px}
  [style*="grid-template-columns:1fr 1fr"],[style*="grid-template-columns: 1fr 1fr"]{grid-template-columns:1fr!important}
  td,thead th{padding:10px 10px}
  .content{padding:12px}
  .mobile-bar{margin:-12px -12px 12px}
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
<aside class="sidebar" id="ownerSidebar">
    <div class="sidebar-brand">
        <a href="/owner/dashboard" class="brand-link" aria-label="Moka home"><img src="{{ asset('images/layout/logo-w.svg') }}" alt="Moka" class="brand-logo"></a>
    </div>

    <a href="/owner/dashboard" class="sidebar-link {{ request()->is('owner/dashboard') ? 'active' : '' }}">
        <span class="link-inner">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </span>
    </a>
    <a href="/owner/listing" class="sidebar-link {{ request()->is('owner/listing*') ? 'active' : '' }}">
        <span class="link-inner">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Listing
        </span>
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>
    <a href="/owner/calendar" class="sidebar-link {{ request()->is('owner/calendar') ? 'active' : '' }}">
        <span class="link-inner">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Calendar
        </span>
    </a>
    {{-- Performance is hidden: production serves a "coming soon" placeholder
         here, so there is nothing for owners to see yet. The route and the
         report itself stay in place — restore this link when the page is
         ready. --}}
    {{--
    <a href="/owner/listing/chart/report" class="sidebar-link {{ request()->is('owner/listing/chart/report') ? 'active' : '' }}">
        <span class="link-inner">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Performance
        </span>
    </a>
    --}}

    <div class="sidebar-divider"></div>

    <a href="/owner/change_password" class="sidebar-link {{ request()->is('owner/change_password') ? 'active' : '' }}">
        <span class="link-inner">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            Change Password
        </span>
    </a>

    <div class="sidebar-spacer"></div>

    <div class="sidebar-divider"></div>
    <form action="/logout" method="POST" style="margin:0">
        @csrf
        <button type="submit" class="sidebar-link" style="color:rgba(255,255,255,.85)">
            <span class="link-inner">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Log Out
            </span>
        </button>
    </form>
</aside>

{{-- MAIN CONTENT --}}
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar(false)"></div>
<main class="content">
    <div class="mobile-bar">
        <button type="button" class="menu-btn" aria-label="Open menu" aria-controls="ownerSidebar" aria-expanded="false" onclick="toggleSidebar()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <a href="/owner/dashboard" aria-label="Moka home"><img src="{{ asset('images/layout/logo-orange.svg') }}" alt="Moka" class="brand-logo-sm"></a>
    </div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    @yield('content')
</main>

<script>
// Off-canvas sidebar for tablet and phone. Closes on overlay tap, Escape, or
// when a link is followed, so the page underneath is never left covered.
function toggleSidebar(open) {
    var sb = document.getElementById('ownerSidebar'), ov = document.getElementById('sidebarOverlay'), btn = document.querySelector('.menu-btn');
    if (!sb) return;
    var isOpen = typeof open === 'boolean' ? open : !sb.classList.contains('open');
    sb.classList.toggle('open', isOpen);
    if (ov) ov.classList.toggle('show', isOpen);
    if (btn) btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    document.body.style.overflow = isOpen && window.innerWidth <= 1024 ? 'hidden' : '';
}
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') toggleSidebar(false); });
document.addEventListener('click', function (e) { if (e.target.closest && e.target.closest('#ownerSidebar a')) toggleSidebar(false); });
window.addEventListener('resize', function () { if (window.innerWidth > 1024) toggleSidebar(false); });
</script>
<script>
// Chrome opens a date or month picker only from its small icon. showPicker()
// opens it from a tap anywhere on the field; browsers without it keep the icon.
// The field itself never takes focus, so Chrome cannot highlight a segment:
// the press is swallowed and only the picker opens.
function openPicker(e) {
    var el = e.target;
    if (!(el instanceof HTMLInputElement) || (el.type !== 'month' && el.type !== 'date')) return;
    if (typeof el.showPicker !== 'function') return;   // older browser: native behaviour
    e.preventDefault();
    try { el.showPicker(); } catch (err) {}
    if (document.activeElement === el) el.blur();
}
document.addEventListener('pointerdown', openPicker);
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
