<?php



// User Dashboard
Route::get('/dashboard', 'User\HomeController@index');

//filtered listing
Route::get('/filter', 'User\HomeController@listingFilter');

//Listing
Route::get('/listing/{id}', 'User\ListingController@listingDetail');
Route::post('/booking/new', 'User\ListingController@preBooking');
Route::get('/booking/history', 'User\HomeController@bookingHistory');

//Review
Route::post('/booking/review', 'User\BookingController@reviewStore');


//Profile
Route::get('/profile', 'User\HomeController@profileEdit');
Route::put('/profile', 'User\HomeController@profileUpdate');

Route::get('/profile/verify', 'User\HomeController@verify');
Route::post('/profile/verify', 'User\HomeController@verifyStore');

Route::post('/promo/check', 'User\CheckoutController@checkPromo');