<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MOKA Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware('lang')->group(function () {

    /* ── Public Pages ───────────────────────────────────────────────── */
    Route::get('/',             'Auth\WebController@newHome');
    Route::get('/homepage',     'Auth\WebController@newHomepage');
    Route::get('/about',        'Auth\WebController@newAbout');
    Route::get('/service',      'Auth\WebController@HomeService');
    Route::get('/designs',      'Auth\WebController@HomeDesigns');
    Route::get('/get/estimate', 'Auth\WebController@estimate');
    Route::post('/estimate',    'Auth\WebController@submitEstimate')->name('estimate.submit');
    Route::get('/language/{lang}', 'Auth\HomeController@setLanguage');

    /* ── Public Info Pages ──────────────────────────────────────────── */
    Route::get('/contact',         'Auth\WebController@contact');
    Route::post('/contact',        'Auth\WebController@contactStore');
    Route::get('/policy',          'Auth\WebController@policy');
    Route::get('/terms',           'Auth\WebController@terms');
    Route::post('/subscribe',      'Auth\WebController@subscribe');
    Route::get('/announcement',    'Admin\OwnerController@announcement');

    /* ── Property Search & Detail ───────────────────────────────────── */
    Route::get('/location/search', 'Auth\WebController@locationSearch')->name('search');
    Route::get('/listing/{key}',   'Auth\WebController@propertyDetail')->name('listing.detail');

    /* ── Payment ─────────────────────────────────────────────────────── */
    Route::get('/payment/redirect',   'User\PaymentController@paymentRedirect');
    Route::post('/payment/callback',  'User\PaymentController@paymentCallback');

    /* ── Email Verification ──────────────────────────────────────────── */
    Route::get('/register/{code}/activation', 'Auth\WebController@verificationCode');

    /* ── Authentication ─────────────────────────────────────────────── */
    Route::middleware('guest')->group(function () {
        Route::get('/login',               'Auth\LoginController@showLoginForm')->name('login');
        Route::post('/login',              'Auth\LoginController@login')->middleware('throttle:10,1');
        Route::get('/register',            'Auth\RegisterController@showRegistrationForm')->name('register');
        Route::post('/register',           'Auth\RegisterController@register');
        Route::get('/password/reset',      'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
        Route::post('/password/email',     'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
        Route::get('/password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
        Route::post('/password/reset',     'Auth\ResetPasswordController@reset')->name('password.update');
    });

    Route::post('/logout', 'Auth\WebController@logout')->name('logout');
    Route::get('/logout',  'Auth\WebController@logout');

    /* ── Admin Panel ─────────────────────────────────────────────────── */
    Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
        require base_path('routes/routes/admin.php');
    });

    /* ── Owner Panel ─────────────────────────────────────────────────── */
    Route::prefix('owner')->middleware(['auth', 'role:owner'])->group(function () {
        require base_path('routes/routes/owner.php');
    });

    /* ── User (Guest Booker) Panel ───────────────────────────────────── */
    Route::prefix('home')->middleware(['auth', 'role:user'])->group(function () {
        require base_path('routes/routes/user.php');
    });

});
