<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/home';

    protected $namespace = 'App\Http\Controllers';

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Deposit refund form (public, behind a 48-hour link). Two caps per IP: a
        // burst cap against scripted floods and a daily cap wide enough for a whole
        // building on one shared WiFi address; the per-phone cap is in App\Support\Refunds.
        RateLimiter::for('refund-form', fn (Request $request) => Limit::perMinute(30)->by('rf-form|' . $request->ip()));
        RateLimiter::for('refund-submit', fn (Request $request) => [
            Limit::perMinute(6)->by('rf-burst|' . $request->ip()),
            Limit::perDay(150)->by('rf-day|' . $request->ip()),
        ]);
    }
}
