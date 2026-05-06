<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Owner Dashboard — MOKA</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f5f5f7;color:#1d1d1f;min-height:100vh}
.topbar{background:#1d1d1f;color:#fff;padding:0 32px;height:52px;display:flex;align-items:center;justify-content:space-between}
.topbar .brand{font-size:17px;font-weight:600;color:#fff;letter-spacing:-.3px}
.topbar nav{display:flex;gap:24px}
.topbar nav a{color:rgba(255,255,255,.7);text-decoration:none;font-size:14px}
.topbar nav a:hover{color:#fff}
.topbar .user{display:flex;align-items:center;gap:12px;font-size:14px}
.container{max-width:1100px;margin:0 auto;padding:40px 24px}
h1{font-size:28px;font-weight:700;letter-spacing:-.5px;margin-bottom:4px}
.subtitle{color:#6e6e73;font-size:15px;margin-bottom:32px}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:40px}
.stat-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.stat-card .value{font-size:36px;font-weight:700;letter-spacing:-1px}
.stat-card .label{font-size:13px;color:#6e6e73;margin-top:4px}
.section-title{font-size:20px;font-weight:600;margin-bottom:16px;letter-spacing:-.3px}
.listing-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:40px}
.listing-card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.listing-card h3{font-size:15px;font-weight:600;margin-bottom:4px}
.listing-card .addr{font-size:13px;color:#6e6e73;margin-bottom:12px}
.listing-card .price{font-size:17px;font-weight:600;color:#1d1d1f}
.listing-card .price span{font-size:13px;font-weight:400;color:#6e6e73}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:500;margin-bottom:12px}
.badge-active{background:#d1fae5;color:#065f46}
.badge-inactive{background:#fee2e2;color:#991b1b}
.empty{background:#fff;border-radius:16px;padding:40px;text-align:center;color:#6e6e73;box-shadow:0 1px 3px rgba(0,0,0,.08)}
</style>
</head>
<body>
<div class="topbar">
    <span class="brand">MOKA Owner</span>
    <nav>
        <a href="/owner/dashboard">Dashboard</a>
        <a href="/owner/listing">My Listings</a>
        <a href="/owner/report">Reports</a>
    </nav>
    <div class="user">
        <span>{{ Auth::user()->name }}</span>
        <form style="display:inline" action="/logout" method="POST">@csrf
            <button type="submit" style="background:none;border:none;color:rgba(255,255,255,.7);font-size:14px;cursor:pointer">Sign out</button>
        </form>
    </div>
</div>

<div class="container">
    <h1>Welcome back, {{ Auth::user()->name }}</h1>
    <p class="subtitle">Your property portfolio at a glance.</p>

    @php
        $myListings  = \App\Listing::where('user_id', Auth::id())->get();
        $activeCount = $myListings->where('status', 1)->count();
        $myBookings  = \App\Booking::whereIn('listing_id', $myListings->pluck('id'))->count();
        $revenue     = \App\Booking::whereIn('listing_id', $myListings->pluck('id'))->where('status', '>=', 5)->sum('total_price');
    @endphp

    <div class="stats">
        <div class="stat-card">
            <div class="value">{{ $myListings->count() }}</div>
            <div class="label">My Listings</div>
        </div>
        <div class="stat-card">
            <div class="value">{{ $activeCount }}</div>
            <div class="label">Active</div>
        </div>
        <div class="stat-card">
            <div class="value">{{ $myBookings }}</div>
            <div class="label">Total Bookings</div>
        </div>
        <div class="stat-card">
            <div class="value">RM {{ number_format($revenue) }}</div>
            <div class="label">Total Revenue</div>
        </div>
    </div>

    <p class="section-title">My Properties</p>

    @if($myListings->isEmpty())
        <div class="empty">
            <p style="font-size:17px;font-weight:600;margin-bottom:8px">No listings yet</p>
            <p>Contact the MOKA admin team to get your properties listed.</p>
        </div>
    @else
        <div class="listing-grid">
            @foreach($myListings as $l)
            <div class="listing-card">
                <span class="badge {{ $l->status ? 'badge-active' : 'badge-inactive' }}">{{ $l->status ? 'Active' : 'Inactive' }}</span>
                <h3>{{ $l->title ?? $l->name }}</h3>
                <p class="addr">{{ $l->address }}</p>
                <div class="price">RM {{ number_format($l->default_price) }} <span>/ night</span></div>
            </div>
            @endforeach
        </div>
    @endif
</div>
</body>
</html>
