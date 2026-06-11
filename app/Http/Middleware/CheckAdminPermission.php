<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdminPermission
{
    /**
     * Handle an incoming request.
     *
     * Usage in routes:  ->middleware('adminperm:listings.manage')
     */
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        if (! admin_can($permission)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
