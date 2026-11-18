<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller
{
    const MAX_WRONG = 5;
    const LOCK_SECONDS = 900;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm(Request $request)
    {
        // Opened from the home-screen app (manifest start_url -> /home?source=app -> /login?app=1):
        // a bare sign-in screen, remembered for the rest of that app session by a cookie so a
        // failed attempt comes back to the same screen.
        if ($request->boolean('app') || $request->cookie('moka_app')) {
            return response()->view('v2.pages.login-app')->cookie('moka_app', '1', 60 * 24 * 30, '/', null, true, true);
        }
        return view('v2.pages.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Account lock: five wrong passwords for one email, from any address, lock that email for
        // 15 minutes. The route's own limit counts per address, which does nothing against guesses
        // spread over thousands of addresses. The same reply is given whether or not the email exists.
        $lockKey = 'login-lock:' . sha1(mb_strtolower(trim((string) $request->email)));
        if (RateLimiter::tooManyAttempts($lockKey, self::MAX_WRONG)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($lockKey) / 60));
            return back()->withErrors(['email' => "Too many wrong attempts. For your security this account is locked for {$minutes} more minute" . ($minutes === 1 ? '' : 's') . '. You can also reset your password.'])->onlyInput('email');
        }

        if (! \App\Support\Turnstile::passes($request)) {
            return back()->withErrors(['email' => \App\Support\Turnstile::MESSAGE])->onlyInput('email');
        }

        // Several records can share one email (a staff login and a guest profile): sign in the admin one.
        // Resolve on the auth model (App\User carries the role methods), not App\Models\User.
        $found = \App\Models\User::preferredForEmail($request->email);
        $user  = $found ? \App\User::find($found->id) : null;
        if ($user && \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            RateLimiter::clear($lockKey);
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            $user->forceFill(['last_login_at' => now()])->saveQuietly();
            return $this->redirectAfterLogin();
        }

        RateLimiter::hit($lockKey, self::LOCK_SECONDS);

        return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    protected function redirectAfterLogin()
    {
        if (Auth::user()->hasRole('admin')) {
            return redirect('/admin/dashboard');
        }
        if (Auth::user()->hasRole('owner')) {
            return redirect('/owner/dashboard');
        }
        return redirect('/home/dashboard');
    }
}
