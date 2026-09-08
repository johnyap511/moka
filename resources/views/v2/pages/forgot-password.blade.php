{{-- Reset link request. Route: /password/reset → Auth\ForgotPasswordController. --}}
@extends('auth.newTheme.layout')
@section('seo_title', 'Reset Password | MOKA')
@section('seo_description', 'Request a password reset link for your MOKA owner account.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}?v={{ filemtime(public_path('new-theme23/css/blog23.css')) }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/account23.css') }}?v={{ filemtime(public_path('new-theme23/css/account23.css')) }}">
    <meta name="robots" content="noindex">
@endpush

@section('content')
    @include('auth.newTheme.partials.header')

    <div class="blog-hero acct-hero">
        <div class="blog-hero__inner">
            <div class="blog-hero__eyebrow">Hosts</div>
            <h1>Reset your password</h1>
            <p class="blog-hero__meta">Enter your email and we will send you a reset link.</p>
        </div>
    </div>

    <div class="acct-body">
        <div class="acct-card">
            @if(session('status'))<div class="ok">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
            <form method="POST" action="/password/email">
                @csrf
                <label for="f-email">Email address</label>
                <input id="f-email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" enterkeyhint="go" spellcheck="false" autocapitalize="off" placeholder="you@example.com">
                <button type="submit">Send reset link</button>
            </form>
            <p class="foot"><a href="/login">Back to sign in</a></p>
        </div>
        @include('v2.pages._account-focus')
    </div>
@endsection
