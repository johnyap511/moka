<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MOKA v2 — Web Routes
|--------------------------------------------------------------------------
*/

/* ── Public Marketing Pages ─────────────────────────────────────────── */
Route::group(['middleware' => 'lang'], function () {

    Route::get('/',             'Auth\WebController@newHomeNew');
    Route::get('/homepage',     'Auth\WebController@newHomeNew');
    Route::get('/about',        'Auth\WebController@HomeAbout');
    Route::get('/service',      'Auth\WebController@HomeService');
    Route::get('/get/estimate', 'Auth\WebController@getEstimate');
    Route::post('/estimate',    'Auth\WebController@submitEstimate')->name('estimate.submit');

    /* ── Property Search & Detail ─────────────────────────────────── */
    Route::get('/location/search',  'Auth\WebController@locationSearch')->name('search');
    Route::get('/listing/{key}',    'Auth\WebController@propertyDetail')->name('listing.detail');

    /* ── Auth ─────────────────────────────────────────────────────── */
    Route::get('/login',    'Auth\LoginController@showLoginForm')->name('login');
    Route::post('/login',   'Auth\LoginController@login');
    Route::post('/logout',  'Auth\LoginController@logout')->name('logout');
    Route::get('/register', 'Auth\RegisterController@showRegistrationForm')->name('register');
    Route::post('/register','Auth\RegisterController@register');

    Route::get('/password/reset',          'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
    Route::post('/password/email',         'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
    Route::get('/password/reset/{token}',  'Auth\ResetPasswordController@showResetForm')->name('password.reset');
    Route::post('/password/reset',         'Auth\ResetPasswordController@reset')->name('password.update');

    /* ── Owner Dashboard (authenticated) ─────────────────────────── */
    Route::middleware(['auth'])->group(function () {
        Route::get('/user/home',      'Auth\DashboardController@index')->name('dashboard');
        Route::get('/user/bookings',  'Auth\DashboardController@bookings')->name('dashboard.bookings');
        Route::get('/user/revenue',   'Auth\DashboardController@revenue')->name('dashboard.revenue');
        Route::get('/user/calendar',  'Auth\DashboardController@calendar')->name('dashboard.calendar');
    });

});
