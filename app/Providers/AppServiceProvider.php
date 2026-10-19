<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Mail account set from the admin portal wins over the server file.
        \App\Support\AppSettings::applyMail();

        // Admin lists paginate with a compact view: the default Tailwind view has no CSS here
        // and showed raw "pagination.previous" keys and giant arrows (19 Oct 2026).
        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.moka');
        \Illuminate\Pagination\Paginator::defaultSimpleView('vendor.pagination.moka');

        // \URL::forceScheme('https');
    }
}
