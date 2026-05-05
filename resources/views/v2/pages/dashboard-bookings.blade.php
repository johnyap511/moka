@extends('v2.partial.layout')
@section('title', 'Bookings — MOKA Dashboard')
@section('content')
<section style="padding:100px 20px 60px;max-width:1200px;margin:0 auto;">
    <a href="/user/home" style="color:#003d3c;font-weight:600;text-decoration:none;">&larr; Dashboard</a>
    <h1 style="font-family:'Playfair Display',serif;font-size:2.2rem;color:#003d3c;margin:24px 0 8px;">Bookings</h1>
    <p style="color:#6b7280;margin-bottom:40px;">Booking data will appear here once connected to your channel manager.</p>
    <div style="background:#fff;border-radius:16px;padding:48px;box-shadow:0 4px 20px rgba(0,0,0,.08);text-align:center;color:#9ca3af;">
        No bookings data available in staging environment.
    </div>
</section>
@endsection
