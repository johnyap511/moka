{{-- New password form. Route: /password/reset/{token} → Auth\ResetPasswordController. --}}
@extends('auth.newTheme.layout')
@section('seo_title', 'New Password | MOKA')
@section('seo_description', 'Choose a new password for your MOKA owner account.')

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
            <h1>Choose a new password</h1>
            <p class="blog-hero__meta">Pick something at least 8 characters long.</p>
        </div>
    </div>

    <div class="acct-body">
        <div class="acct-card">
            @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
            <form method="POST" action="/password/reset">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">
                <label for="r-password">New password</label>
                <input id="r-password" type="password" name="password" required autocomplete="new-password" enterkeyhint="next">
                <label for="r-confirm">Confirm password</label>
                <input id="r-confirm" type="password" name="password_confirmation" required autocomplete="new-password" enterkeyhint="go">
                <button type="submit">Save new password</button>
            </form>
            <p class="foot"><a href="/login">Back to sign in</a></p>
        </div>
        @include('v2.pages._account-focus')
    </div>
@endsection
