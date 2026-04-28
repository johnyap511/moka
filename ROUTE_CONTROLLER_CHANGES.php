<?php
/**
 * MOKA v2 — Route & Controller Reference
 * =========================================
 * Add these route entries to your existing routes/web.php
 * inside the Route::group(['middleware' => 'lang']) block.
 *
 * Update the corresponding WebController methods to return the new v2 views.
 * The existing routes remain the same — only the view name changes.
 */

// ─────────────────────────────────────────
// routes/web.php  (inside the lang middleware group)
// ─────────────────────────────────────────

// Public marketing pages → now use v2 views
Route::get('/homepage', 'Auth\WebController@newHomeNew');   // → v2.pages.home
Route::get('/about',    'Auth\WebController@HomeAbout');    // → v2.pages.about
Route::get('/service',  'Auth\WebController@HomeService');  // → v2.pages.service
Route::get('/get/estimate', 'Auth\WebController@getEstimate'); // → v2.pages.estimate (NEW standalone)

// Property search & detail (already exist, just update view)
Route::get('/location/search', 'Auth\WebController@locationSearch'); // → v2.pages.property-search
Route::get('/listing/{key}',   'Auth\WebController@propertyDetail'); // → v2.pages.property-detail

// ─────────────────────────────────────────
// app/Http/Controllers/Auth/WebController.php
// ─────────────────────────────────────────
// Change ONLY the view() return values in these methods:

/*
public function newHomeNew()
{
    return view('v2.pages.home');
}

public function HomeAbout()
{
    return view('v2.pages.about');
}

public function HomeService()
{
    return view('v2.pages.service');
}

public function getEstimate()
{
    return view('v2.pages.estimate');
}

public function locationSearch(Request $request)
{
    // ... existing logic unchanged ...
    return view('v2.pages.property-search', compact(
        'listings', 'selectedZone', 'checkIn', 'checkOut', 'guestNo', 'children'
    ));
}

public function propertyDetail($key)
{
    // ... existing logic unchanged ...
    return view('v2.pages.property-detail', compact('listing'));
}
*/
