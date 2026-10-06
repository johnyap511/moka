<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showResetForm(Request $request, $token)
    {
        return view('v2.pages.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ], ['password.confirmed' => 'The two passwords do not match.', 'password.min' => 'Use at least 8 characters.']);

        $preferred = \App\Models\User::preferredForEmail($request->email);
        $credentials = $request->only('email', 'password', 'password_confirmation', 'token') + ['id' => $preferred ? $preferred->id : 0];
        $status = Password::reset($credentials, function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => $status === Password::INVALID_TOKEN
                ? 'This link has expired or was already used. Ask for a new one from "Forgot password?".'
                : __($status)]);
        }

        return redirect('/login')->with('status', 'Your password is set. Sign in with it.');
    }
}
