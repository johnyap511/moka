<?php

// Dashboard Page
Route::get('/dashboard', 'Admin\HomeController@index');
Route::get('/view/calendar', 'Admin\HomeController@view_calendar');

// Cities
Route::resource('/users', 'Admin\UserController');
Route::get('/user/admin', 'Admin\UserController@userListAdmin');
Route::post('/user/admindata', 'Admin\UserController@admin_Ajex');
Route::post('/user/export/{type}', 'Admin\UserController@exportUsers');
Route::get('/user/verify', 'Admin\UserController@userVerify');
Route::post('/user/verify', 'Admin\UserController@userVerifyStore');

Route::resource('/owners', 'Admin\OwnerController');
Route::get('/owners/{id}/listing', 'Admin\OwnerController@ownerListing');
Route::get('/owners/{id}/listing/create', 'Admin\OwnerController@ownerAddListing');
Route::get('/owners/{id}/listing/{listingId}/edit', 'Admin\OwnerController@ownerListingEdit');
Route::resource('/admin', 'Admin\AdminController');

// Listing
Route::post('/listing/archive', 'Admin\ListingController@setArchived')->name('admin.listing.archive');
Route::post('/listing/{id}/status', 'Admin\ListingController@toggleStatus')->name('admin.listing.status');
Route::resource('/listing', 'Admin\ListingController')->names('admin.listing');
Route::get('/listing/excel/export', 'Admin\ListingController@exportExcel');
Route::post('/listing/report/{id}', 'Admin\ListingController@reportExport');
Route::post('/listings/reports/{id}', 'Admin\ListingController@reportExports');
Route::post('/listing/update/reports', 'Admin\ListingController@reportImports');
Route::post('/listing/generate/ezee', 'Admin\ListingController@generateEzeeToken');

// Report
Route::get('/listing/chart/report', 'Admin\ListingController@report');
//Details
Route::get('/listing/{listingId}/details', 'Admin\ListingController@listingDetails');
Route::post('/listing/{listingId}/details', 'Admin\ListingController@listingDetailsStore');

// Calendar
Route::get('/listing/{id}/calendar', 'Admin\CalendarController@index');
Route::get('/calendar', 'Admin\CalendarController@allBooks');
Route::post('/calendar/export', 'Admin\CalendarController@export');

// Price
Route::get('/listing/{id}/price', 'Admin\ListingController@pricing');
Route::post('/listing/{id}/price', 'Admin\ListingController@pricingStore');

// Book
Route::get('/listing/{id}/book', 'Admin\BookController@create');
Route::post('/listing/{id}/book', 'Admin\BookController@store');

Route::get('/ezee/booking', 'Admin\BookController@ezeeBookings');
Route::get('/ezee/bookings-by-property', 'Admin\BookController@ezeeBookingsByProperty')->name('admin.ezee.bookings-by-property');

//Segregate assigned and unassigned
Route::get('/ezee/assigned_booking', 'Admin\BookController@ezeeBookingsAssigned');
Route::get('/ezee/unassigned_booking', 'Admin\BookController@ezeeBookingsUnAssigned');
// EZEE Booking routes
Route::get('/ezee/booking/{id}/edit', 'Admin\EzeeBookingController@edit')->name('admin.ezee.booking.edit');
Route::put('/ezee/booking/{id}', 'Admin\EzeeBookingController@update')->name('admin.ezee.booking.update');

Route::get('ezee/booking_report', 'Admin\BookController@ezeeBookingsReports');
Route::get('/ezee/revenue-export', 'Admin\EzeeRevenueExportController@index')->name('admin.ezee.revenue-export');
Route::post('/ezee/revenue-export', 'Admin\EzeeRevenueExportController@download')->name('admin.ezee.revenue-export.download');
Route::get('ezee/upload_bookings', 'Admin\BookController@uploadBookings'); 
Route::post('ezee/upload_bookings_data', 'Admin\BookController@uploadBookingData');
Route::get('ezee/booking_by_date/{date}', 'Admin\BookController@ezeeBookingsDate');
Route::post('/ezee/booking/{bookId}', 'Admin\BookController@ezeeBookingStore');
Route::post('/ezee/bookingEdit/{bookId}', 'Admin\BookController@ezeeBookingStoreEdit')->name('admin.ezee.booking.store.edit');
Route::delete('/ezee/booking/{bookId}', 'Admin\BookController@ezeeBookingDelete');
Route::post('/ezee/bookings/remove-duplicates', 'Admin\BookController@ezeeRemoveDuplicates')->name('admin.ezee.remove-duplicates');

// EZEE Room Mapping & Auto-Assignment
Route::get('/ezee/room-mapping', 'Admin\EzeeRoomMappingController@index')->name('admin.ezee.room-mapping');
Route::post('/ezee/room-mapping/save', 'Admin\EzeeRoomMappingController@saveAll')->name('admin.ezee.room-mapping.save');
Route::post('/ezee/room-mapping/auto-assign', 'Admin\EzeeRoomMappingController@autoAssign')->name('admin.ezee.auto-assign');
Route::post('/ezee/room-mapping/archive', 'Admin\EzeeRoomMappingController@setArchived')->name('admin.ezee.room-mapping.archive');
Route::post('/ezee/booking/{ezeeBookingId}/reassign', 'Admin\EzeeRoomMappingController@reassign')->name('admin.ezee.reassign');
Route::post('/ezee/booking/{ezeeBookingId}/assign-history', 'Admin\EzeeRoomMappingController@assignHistory')->name('admin.ezee.assign-history');
Route::post('/ezee/booking/{ezeeBookingId}/no-unit', 'Admin\EzeeRoomMappingController@noUnit')->name('admin.ezee.no-unit');
Route::post('/ezee/booking/{ezeeBookingId}/restore', 'Admin\EzeeRoomMappingController@restore')->name('admin.ezee.restore');
Route::post('/ezee/booking/{ezeeBookingId}/accept-dates', 'Admin\EzeeRoomMappingController@acceptDates')->name('admin.ezee.accept-dates');
// Split divides one stay across two units; reassign moves a whole booking.
Route::post('/booking/{bookingId}/split', 'Admin\EzeeRoomMappingController@split')->name('admin.booking.split');
Route::get('/ezee/assignment-log', 'Admin\EzeeRoomMappingController@auditLog')->name('admin.ezee.assignment-log');
Route::post('/ezee/assignment-log/{log}/resolve', 'Admin\EzeeRoomMappingController@resolveConflict')->name('admin.ezee.assignment-log.resolve');

// Images
Route::get('/listing/{id}/images', 'Admin\ListingImagesController@show');
Route::post('/listing/{id}/images', 'Admin\ListingImagesController@store');
Route::delete('/listing/{id}/images/{imageId}', 'Admin\ListingImagesController@destroy');
Route::get('/listing/{id}/video', 'Admin\ListingImagesController@videoShow');
Route::post('/listing/{id}/video', 'Admin\ListingImagesController@videoStore');

// Booking
Route::resource('/book', 'Admin\BookingController');
Route::post('/booking/excel/import', 'Admin\BookingController@importExcel');
Route::get('/booking/excel/export', 'Admin\BookingController@exportExcel');
Route::get('/booking/excel/template', 'Admin\BookingController@downloadBookingTemplate');

Route::post('/booking/excel/export_range', 'Admin\BookingController@exportExcelRange');
Route::post('/booking/historical/api', 'Admin\BookController@history_api');
Route::get('/booking/histroy/api', 'Admin\BookController@history');

// Group
Route::resource('/group', 'Admin\GroupController');

// Payment
Route::get('/payment/upcoming', 'Admin\PaymentController@upcoming');
Route::post('/payment/update/{reportId}', 'Admin\PaymentController@paymentUpdate');
Route::post('/payment/price/{reportId}', 'Admin\PaymentController@priceUpdate');
Route::get('/payment/past', 'Admin\PaymentController@past');

// Subscribe List
Route::get('/subscribe', 'Admin\HomeController@subscribeList');

// Setting
Route::get('/setting/admin-roles', 'Admin\AdminRoleController@index')->name('admin.roles.index');
Route::get('/setting/estimate', 'Admin\SettingController@estimate');
Route::delete('/setting/estimate/{id}', 'Admin\SettingController@estimateDestroy');
Route::get('/setting/logs', 'Admin\SettingController@logs');
Route::resource('/setting/zone', 'Admin\ZoneController');
Route::get('/listing/{listingId}/zone', 'Admin\ZoneController@listingZone');
Route::post('/listing/{listingId}/zone', 'Admin\ZoneController@listingZoneStore');
Route::resource('/setting/amenities', 'Admin\AmenitiesController');
Route::get('/listing/{listingId}/amenities', 'Admin\AmenitiesController@listingAmenities');
Route::post('/listing/{listingId}/amenities', 'Admin\AmenitiesController@listingAmenitiesStore');

// Approval Task
Route::get('/approval/review', 'Admin\ApprovalReviewController@index');
Route::get('/approval/review/{id}/edit', 'Admin\ApprovalReviewController@edit');
Route::put('/approval/review/{id}', 'Admin\ApprovalReviewController@update');

// EZEE Group
Route::get('/ezee/group', 'Admin\EZEEGroupController@groupList');
Route::get('/ezee/group/create', 'Admin\EZEEGroupController@create');
Route::post('/ezee/group', 'Admin\EZEEGroupController@store');
Route::get('/ezee/group/listing/{groupId}', 'Admin\EZEEGroupController@addListing');
Route::post('/ezee/group/listing/{groupId}', 'Admin\EZEEGroupController@addListingStore');
Route::delete('/ezee/group/listing/{groupId}', 'Admin\EZEEGroupController@listingDestroy');

// announcement
Route::post('/create/announcement', 'Admin\OwnerController@announcementCreate');
Route::post('/listing/utility/template', 'Admin\ListingController@downloadTemplate');

// approval utiulity
Route::post('/import/approval', 'Admin\ListingController@importApproval');
Route::post('/import/pdf/approval', 'Admin\ListingController@sendPdf');
Route::post('/send/approval', 'Admin\ListingController@sendApproval');
Route::delete('/utility/{id}', 'Admin\ListingController@destroyUtility')->name('destroyUtility');
Route::post('/utility/edit/{id}', 'Admin\ListingController@editUtility');
Route::put('/utility/update/{id}', 'Admin\ListingController@updateUtility')->name('admin.utility.update');
Route::post('/utility/store', 'Admin\ListingController@storeUtility')->name('utility.store');
Route::match(['get', 'post'], '/approval/month_wise', 'Admin\ListingController@approval_new')->name('approval.month');

// filemanager
// routes/web.php में निम्न routes add करें:

// File Manager Routes
Route::get('/filemanager', 'Admin\FileManagerController@index')->name('filemanager.index');
Route::get('/filemanager/active', 'Admin\FileManagerController@showActiveFiles')->name('get.active.excel.files');
Route::post('/filemanager/organize', 'Admin\FileManagerController@organizeFiles')->name('filemanager.organize');

// Existing filemanager routes को update करें:
Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth']], function () {
    \UniSharp\LaravelFilemanager\Lfm::routes();
});