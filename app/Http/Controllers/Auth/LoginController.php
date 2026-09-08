<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
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

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();
            return $this->redirectAfterLogin();
        }

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
