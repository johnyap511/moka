<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — MOKA</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f5f5f7;color:#1d1d1f;min-height:100vh}
.topbar{background:#1d1d1f;color:#fff;padding:0 32px;height:52px;display:flex;align-items:center;justify-content:space-between}
.topbar a{color:rgba(255,255,255,.7);text-decoration:none;font-size:14px}
.topbar a:hover{color:#fff}
.topbar .brand{font-size:17px;font-weight:600;color:#fff;letter-spacing:-.3px}
.topbar nav{display:flex;gap:24px}
.topbar .user{display:flex;align-items:center;gap:12px;font-size:14px}
.container{max-width:1100px;margin:0 auto;padding:40px 24px}
h1{font-size:28px;font-weight:700;letter-spacing:-.5px;margin-bottom:4px}
.subtitle{color:#6e6e73;font-size:15px;margin-bottom:32px}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:40px}
.stat-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.stat-card .value{font-size:36px;font-weight:700;letter-spacing:-1px;color:#1d1d1f}
.stat-card .label{font-size:13px;color:#6e6e73;margin-top:4px}
.section-title{font-size:20px;font-weight:600;margin-bottom:16px;letter-spacing:-.3px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-bottom:40px}
.card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.card h3{font-size:15px;font-weight:600;margin-bottom:8px}
.card p{font-size:13px;color:#6e6e73;line-height:1.5;margin-bottom:16px}
.card a{display:inline-block;background:#1d1d1f;color:#fff;padding:8px 18px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none}
.card a:hover{background:#333}
.form-logout{display:inline}
</style>
</head>
<body>
<div class="topbar">
    <span class="brand">MOKA Admin</span>
    <nav>
        <a href="/admin/dashboard">Dashboard</a>
        <a href="/admin/listing">Listings</a>
        <a href="/admin/user">Users</a>
        <a href="/admin/booking">Bookings</a>
    </nav>
    <div class="user">
        <span>{{ Auth::user()->name }}</span>
        <form class="form-logout" action="/logout" method="POST">@csrf
            <button type="submit" style="background:none;border:none;color:rgba(255,255,255,.7);font-size:14px;cursor:pointer">Sign out</button>
        </form>
    </div>
</div>

<div class="container">
    <h1>Good {{ date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') }}, {{ Auth::user()->name }}</h1>
    <p class="subtitle">Here's what's happening on MOKA today.</p>

    @php
        $totalListings  = \App\Listing::count();
        $activeListings = \App\Listing::where('status', 1)->count();
        $totalUsers     = \App\User::count();
        $totalBookings  = \App\Booking::count();
    @endphp

    <div class="stats">
        <div class="stat-card">
            <div class="value">{{ $totalListings }}</div>
            <div class="label">Total Listings</div>
        </div>
        <div class="stat-card">
            <div class="value">{{ $activeListings }}</div>
            <div class="label">Active Listings</div>
        </div>
        <div class="stat-card">
            <div class="value">{{ $totalUsers }}</div>
            <div class="label">Registered Users</div>
        </div>
        <div class="stat-card">
            <div class="value">{{ $totalBookings }}</div>
            <div class="label">Total Bookings</div>
        </div>
    </div>

    <p class="section-title">Quick Actions</p>
    <div class="grid">
        <div class="card">
            <h3>Manage Listings</h3>
            <p>Add, edit, or remove property listings across all zones.</p>
            <a href="/admin/listing">View Listings →</a>
        </div>
        <div class="card">
            <h3>Manage Users</h3>
            <p>View and manage owner and guest accounts.</p>
            <a href="/admin/user">View Users →</a>
        </div>
        <div class="card">
            <h3>Bookings</h3>
            <p>Review all bookings, statuses, and payment records.</p>
            <a href="/admin/booking">View Bookings →</a>
        </div>
        <div class="card">
            <h3>Zones</h3>
            <p>Manage listing zones and area classifications.</p>
            <a href="/admin/zone">Manage Zones →</a>
        </div>
        <div class="card">
            <h3>Amenities</h3>
            <p>Configure the amenities catalogue for all listings.</p>
            <a href="/admin/amenities">Manage Amenities →</a>
        </div>
        <div class="card">
            <h3>Public Site</h3>
            <p>View the live public-facing MOKA website.</p>
            <a href="/" target="_blank">Open Site →</a>
        </div>
    </div>
</div>
</body>
</html>
