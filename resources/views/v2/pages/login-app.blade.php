{{--
    Log-in screen shown when the owner portal is opened from the home-screen app
    (/login?app=1, remembered by the moka_app cookie). No marketing header, hero
    or footer: logo, two fields, button, help line.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in — MOKA</title>
    @include('partials.pwa')
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/all23.css') }}?v={{ filemtime(public_path('new-theme23/css/all23.css')) }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}?v={{ filemtime(public_path('new-theme23/css/blog23.css')) }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/account23.css') }}?v={{ filemtime(public_path('new-theme23/css/account23.css')) }}">
    <style>
        html,body{margin:0;min-height:100%;background:var(--blog-teal)}
        body{display:flex;flex-direction:column;min-height:100vh;min-height:100dvh;-webkit-text-size-adjust:100%;overscroll-behavior-y:none;padding:env(safe-area-inset-top) 0 env(safe-area-inset-bottom)}
        .app-auth{flex:1;display:flex;flex-direction:column;justify-content:center;padding:32px 20px 24px}
        .app-auth__brand{text-align:center;margin-bottom:26px}
        .app-auth__brand img{height:64px;width:auto}
        .app-auth__brand p{margin:12px 0 0;color:rgba(255,255,255,.78);font-size:15px;text-align:center}
        .app-auth .acct-card{margin:0 auto;max-width:440px;box-shadow:0 18px 50px rgba(0,0,0,.28);border:0}
        .app-auth__help{text-align:center;color:rgba(255,255,255,.75);font-size:14.5px;margin:22px 0 0;padding:0 8px}
        .app-auth__help a{color:#fff;font-family:SemiBold;text-decoration:none}
        html.acct-typing .app-auth{justify-content:flex-start;padding-top:20px}
        html.acct-typing .app-auth__brand{margin-bottom:14px}
        html.acct-typing .app-auth__brand p{display:none}
    </style>
</head>
<body>
<div class="app-auth">
    <div class="app-auth__brand">
        <img src="{{ asset('images/layout/logo-w.svg') }}" alt="MOKA">
        <p>Sign in to your owner portal</p>
    </div>
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
                <label><input type="checkbox" name="remember" value="1" checked> Keep me signed in</label>
                <a href="/password/reset?app=1">Forgot password?</a>
            </div>
            <button type="submit">Sign in</button>
        </form>
    </div>
    <p class="app-auth__help">Trouble signing in? <a href="https://wa.me/message/GJMYMABOT7CSG1" target="_blank" rel="noopener">Chat on WhatsApp</a><br>or email <a href="mailto:hello@homemoka.com">hello@homemoka.com</a></p>
</div>
@include('v2.pages._account-focus')
</body>
</html>
