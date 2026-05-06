<?php



// Dashboard Page
Route::get('/dashboard', 'Owner\HomeController@index');
Route::get('/change_password', 'Owner\HomeController@change_password');
Route::put('/update/change_password/{id}', 'Owner\HomeController@update');

// Listing
Route::resource('/listing', 'Owner\ListingController');
Route::get('/performance', 'Owner\ListingController@performance');

// Calendar
Route::get('/listing/{id}/calendar', 'Owner\CalendarController@index');
Route::get('/calendar', 'Owner\CalendarController@allBooks');
Route::get('/listing/{id}/calendar', 'Owner\CalendarController@index');
Route::get('/book/{id}', 'Owner\CalendarController@show');
Route::post('/calendar/export', 'Owner\CalendarController@export');
// Report
Route::get('/listing/chart/report', 'Owner\ListingController@report');
// Excel Export
Route::get('/listing/excel/export', 'Owner\ListingController@exportExcel');

Route::get('/report/revenue', 'Owner\ReportController@revenue');
Route::get('/report/payment', 'Owner\ReportController@payment');