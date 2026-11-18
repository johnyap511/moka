<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

class ForgotPasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showLinkRequestForm()
    {
        return view('v2.pages.forgot-password');
    }

    /** Emails a reset link (valid 60 minutes). Same reply whether or not the address exists. */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        if (! \App\Support\Turnstile::passes($request)) {
            return back()->withErrors(['email' => \App\Support\Turnstile::MESSAGE])->onlyInput('email');
        }

        // One reset email a minute for any one email address, so nobody can flood an owner's inbox.
        // A request inside that minute gets the usual reply and sends nothing.
        $key = 'reset-mail:' . sha1(mb_strtolower(trim((string) $request->email)));
        if (! RateLimiter::tooManyAttempts($key, 1)) {
            RateLimiter::hit($key, 60);
            if ($user = \App\Models\User::preferredForEmail($request->email)) {
                Password::sendResetLink(['email' => $user->email, 'id' => $user->id]);   // the admin record when the email is shared
            }
        }

        return back()->with('status', 'If that email is registered, a reset link has been sent. It is valid for 60 minutes.');
    }
}
