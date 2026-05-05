@extends('v2.partial.layout')
@section('title', 'Reset Password — MOKA')

@section('content')
<section style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:80px 20px;">
    <div style="background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.12);padding:48px;width:100%;max-width:440px;">
        <h1 style="font-family:'Playfair Display',serif;font-size:2rem;margin-bottom:8px;color:#003d3c;">Reset password</h1>
        <p style="color:#6b7280;margin-bottom:32px;">Enter your email and we'll send a reset link.</p>
        @if(session('status'))
            <div style="background:#f0fdf4;border:1px solid #86efac;color:#16a34a;padding:12px 16px;border-radius:10px;margin-bottom:20px;">{{ session('status') }}</div>
        @endif
        <form method="POST" action="/password/email">
            @csrf
            <div style="margin-bottom:28px;">
                <label style="display:block;font-weight:600;color:#374151;margin-bottom:6px;font-size:.9rem;">Email address</label>
                <input type="email" name="email" required
                       style="width:100%;padding:12px 16px;border:1.5px solid #d1d5db;border-radius:10px;font-size:1rem;outline:none;box-sizing:border-box;"
                       placeholder="you@example.com">
            </div>
            <button type="submit"
                    style="width:100%;padding:14px;background:#003d3c;color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:600;cursor:pointer;">
                Send Reset Link
            </button>
        </form>
        <p style="text-align:center;margin-top:24px;">
            <a href="/login" style="color:#003d3c;font-weight:600;font-size:.9rem;">Back to login</a>
        </p>
    </div>
</section>
@endsection
