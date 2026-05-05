@extends('v2.partial.layout')
@section('title', 'New Password — MOKA')
@section('content')
<section style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:80px 20px;">
    <div style="background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.12);padding:48px;width:100%;max-width:440px;">
        <h1 style="font-family:'Playfair Display',serif;font-size:2rem;margin-bottom:32px;color:#003d3c;">New password</h1>
        <form method="POST" action="/password/reset">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">
            <div style="margin-bottom:20px;">
                <label style="display:block;font-weight:600;color:#374151;margin-bottom:6px;font-size:.9rem;">New password</label>
                <input type="password" name="password" required style="width:100%;padding:12px 16px;border:1.5px solid #d1d5db;border-radius:10px;font-size:1rem;outline:none;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:28px;">
                <label style="display:block;font-weight:600;color:#374151;margin-bottom:6px;font-size:.9rem;">Confirm password</label>
                <input type="password" name="password_confirmation" required style="width:100%;padding:12px 16px;border:1.5px solid #d1d5db;border-radius:10px;font-size:1rem;outline:none;box-sizing:border-box;">
            </div>
            <button type="submit" style="width:100%;padding:14px;background:#003d3c;color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:600;cursor:pointer;">Reset Password</button>
        </form>
    </div>
</section>
@endsection
