<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the queries assignment runs constantly.
 *
 * bookings had no index beyond the primary key, so every check for "is this
 * unit already occupied over these dates" scanned all 64k rows — measured at
 * 91ms each, and the hourly reconcile performs one per candidate booking.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['listing_id', 'check_in', 'check_out'], 'bookings_listing_dates_index');
        });

        Schema::table('ezee_bookings', function (Blueprint $table) {
            // The reconcile selects on RoomName and End; the assignment lookup
            // and several screens filter on book_id.
            $table->index('RoomName', 'ezee_bookings_room_name_index');
            $table->index('End', 'ezee_bookings_end_index');
            $table->index('book_id', 'ezee_bookings_book_id_index');
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_listing_dates_index');
        });

        Schema::table('ezee_bookings', function (Blueprint $table) {
            $table->dropIndex('ezee_bookings_room_name_index');
            $table->dropIndex('ezee_bookings_end_index');
            $table->dropIndex('ezee_bookings_book_id_index');
        });
    }
};
