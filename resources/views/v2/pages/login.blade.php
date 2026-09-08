{{-- Hosts log in. Route: /login → Auth\LoginController. Homepage template, blog styling. --}}
@extends('auth.newTheme.layout')
@section('seo_title', 'Owner Log In | MOKA')
@section('seo_description', 'Sign in to your MOKA owner account to see bookings, calendar and revenue for your units.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}?v={{ filemtime(public_path('new-theme23/css/blog23.css')) }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/account23.css') }}?v={{ filemtime(public_path('new-theme23/css/account23.css')) }}">
    <meta name="robots" content="noindex">
@endpush

@section('content')
    @include('auth.newTheme.partials.header')

    <div class="blog-hero acct-hero">
        <div class="blog-hero__inner">
            <div class="blog-hero__eyebrow">Owners</div>
            <h1>Welcome back</h1>
            <p class="blog-hero__meta">Sign in to see your bookings, calendar and revenue.</p>
        </div>
    </div>

    <div class="acct-body">
        <div class="acct-card">
            @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
            @if(session('status'))<div class="ok">{{ session('status') }}</div>@endif
            <form method="POST" action="/login">
                @csrf
                <label for="l-email">Email address</label>
                <input id="l-email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" enterkeyhint="next" spellcheck="false" autocapitalize="off" placeholder="you@example.com">
                <label for="l-password">Password</label>
                <div class="acct-pw">
                    <input id="l-password" type="password" name="password" required autocomplete="current-password" enterkeyhint="go" placeholder="••••••••">
                    <button type="button" class="acct-pw__toggle" aria-label="Show password" onclick="var i=document.getElementById('l-password');var show=i.type==='password';i.type=show?'text':'password';this.textContent=show?'Hide':'Show';this.setAttribute('aria-label',show?'Hide password':'Show password');">Show</button>
                </div>
                <div class="acct-row">
                    <label><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                    <a href="/password/reset">Forgot password?</a>
                </div>
                <button type="submit">Sign in</button>
            </form>
            <p class="foot">Not a MOKA owner yet? <a href="/get/estimate">Get a free estimate</a></p>
        </div>
        @include('v2.pages._account-focus')
        <p class="acct-help">Trouble signing in? <a href="https://wa.me/message/GJMYMABOT7CSG1" target="_blank" rel="noopener">Chat on WhatsApp</a> or email <a href="mailto:hello@homemoka.com">hello@homemoka.com</a>.</p>
    </div>
@endsection
@push('scripts')
<script>if (window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true) { location.replace('/login?app=1'); }</script>
@endpush
