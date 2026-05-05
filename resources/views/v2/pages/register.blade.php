@extends('v2.partial.layout')
@section('title', 'Register — MOKA')

@section('content')
<section style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:80px 20px;">
    <div style="background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.12);padding:48px;width:100%;max-width:440px;">
        <h1 style="font-family:'Playfair Display',serif;font-size:2rem;margin-bottom:8px;color:#003d3c;">Create account</h1>
        <p style="color:#6b7280;margin-bottom:32px;">Join MOKA as a property owner.</p>

        @if($errors->any())
            <div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:.9rem;">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/register">
            @csrf
            <div style="margin-bottom:20px;">
                <label style="display:block;font-weight:600;color:#374151;margin-bottom:6px;font-size:.9rem;">Full name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       style="width:100%;padding:12px 16px;border:1.5px solid #d1d5db;border-radius:10px;font-size:1rem;outline:none;box-sizing:border-box;"
                       placeholder="John Doe">
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block;font-weight:600;color:#374151;margin-bottom:6px;font-size:.9rem;">Email address</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       style="width:100%;padding:12px 16px;border:1.5px solid #d1d5db;border-radius:10px;font-size:1rem;outline:none;box-sizing:border-box;"
                       placeholder="you@example.com">
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block;font-weight:600;color:#374151;margin-bottom:6px;font-size:.9rem;">Password</label>
                <input type="password" name="password" required
                       style="width:100%;padding:12px 16px;border:1.5px solid #d1d5db;border-radius:10px;font-size:1rem;outline:none;box-sizing:border-box;"
                       placeholder="Min. 8 characters">
            </div>
            <div style="margin-bottom:28px;">
                <label style="display:block;font-weight:600;color:#374151;margin-bottom:6px;font-size:.9rem;">Confirm password</label>
                <input type="password" name="password_confirmation" required
                       style="width:100%;padding:12px 16px;border:1.5px solid #d1d5db;border-radius:10px;font-size:1rem;outline:none;box-sizing:border-box;"
                       placeholder="••••••••">
            </div>
            <button type="submit"
                    style="width:100%;padding:14px;background:#003d3c;color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:600;cursor:pointer;">
                Create Account
            </button>
        </form>
        <p style="text-align:center;margin-top:24px;color:#6b7280;font-size:.9rem;">
            Already have an account? <a href="/login" style="color:#003d3c;font-weight:600;">Sign in</a>
        </p>
    </div>
</section>
@endsection
