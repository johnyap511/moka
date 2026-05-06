<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account — MOKA</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f5f5f7;color:#1d1d1f;min-height:100vh}
.topbar{background:#1d1d1f;color:#fff;padding:0 32px;height:52px;display:flex;align-items:center;justify-content:space-between}
.topbar .brand{font-size:17px;font-weight:600;letter-spacing:-.3px}
.topbar nav a{color:rgba(255,255,255,.7);text-decoration:none;font-size:14px;margin-left:24px}
.topbar nav a:hover{color:#fff}
.topbar .user{display:flex;align-items:center;gap:12px;font-size:14px}
.container{max-width:900px;margin:0 auto;padding:40px 24px}
h1{font-size:28px;font-weight:700;letter-spacing:-.5px;margin-bottom:4px}
.subtitle{color:#6e6e73;font-size:15px;margin-bottom:32px}
.section-title{font-size:20px;font-weight:600;margin-bottom:16px;letter-spacing:-.3px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-bottom:40px}
.card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.card h3{font-size:15px;font-weight:600;margin-bottom:8px}
.card p{font-size:13px;color:#6e6e73;line-height:1.5;margin-bottom:16px}
.card a{display:inline-block;background:#1d1d1f;color:#fff;padding:8px 18px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none}
</style>
</head>
<body>
<div class="topbar">
    <span class="brand">MOKA</span>
    <nav>
        <a href="/">Browse Properties</a>
        <a href="/home">My Bookings</a>
    </nav>
    <div class="user">
        <span>{{ Auth::user()->name }}</span>
        <form style="display:inline" action="/logout" method="POST">@csrf
            <button type="submit" style="background:none;border:none;color:rgba(255,255,255,.7);font-size:14px;cursor:pointer">Sign out</button>
        </form>
    </div>
</div>

<div class="container">
    <h1>Hello, {{ Auth::user()->name }}</h1>
    <p class="subtitle">Manage your bookings and account.</p>

    <p class="section-title">What would you like to do?</p>
    <div class="grid">
        <div class="card">
            <h3>Browse Properties</h3>
            <p>Explore our curated collection of short-stay properties across Malaysia.</p>
            <a href="/location/search">Browse Now →</a>
        </div>
        <div class="card">
            <h3>My Bookings</h3>
            <p>View your upcoming and past bookings in one place.</p>
            <a href="/home/booking">View Bookings →</a>
        </div>
        <div class="card">
            <h3>My Profile</h3>
            <p>Update your personal information and preferences.</p>
            <a href="/home/profile">Edit Profile →</a>
        </div>
    </div>
</div>
</body>
</html>
