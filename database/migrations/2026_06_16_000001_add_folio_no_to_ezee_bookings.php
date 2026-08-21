<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddFolioNoToEzeeBookings extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('ezee_bookings', 'folio_no')) {
            return;
        }
        DB::statement('ALTER TABLE ezee_bookings ADD COLUMN folio_no VARCHAR(50) NULL AFTER SubBookingId');
    }

    public function down()
    {
        DB::statement('ALTER TABLE ezee_bookings DROP COLUMN folio_no');
    }
}
