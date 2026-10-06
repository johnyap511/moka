<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

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
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If that email is registered, a reset link has been sent. It is valid for 60 minutes.');
    }
}
