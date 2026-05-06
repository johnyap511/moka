<?php

namespace App\Http\Middleware;

use Closure;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $current = session('app_language');
        if(is_null($current)){
            session(['app_language' => env('APP_LOCALE', 'en')]);
        }
        if(empty($current)){
            session(['app_language' => env('APP_LOCALE', 'en')]);
        }
        $lang = session('app_language');
        \App::setLocale($lang);

        return $next($request);
    }
}
