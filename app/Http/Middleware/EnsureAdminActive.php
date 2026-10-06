<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** An archived or inactive admin login is signed out of the admin area. */
class EnsureAdminActive
{
    public function handle(Request $request, Closure $next): mixed
    {
        $u = Auth::user();
        if ($u && ($u->archived_at || (int) $u->status !== 1)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->with('error', 'This login has been archived. Ask the office if you need access.');
        }

        return $next($request);
    }
}
