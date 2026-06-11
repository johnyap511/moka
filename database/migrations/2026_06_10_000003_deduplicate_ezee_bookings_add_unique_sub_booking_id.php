<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Remove duplicates — keep the lowest id per SubBookingId
        DB::statement('
            DELETE e1 FROM ezee_bookings e1
            INNER JOIN ezee_bookings e2
            WHERE e1.SubBookingId = e2.SubBookingId
              AND e1.id > e2.id
        ');

        // Add unique index on SubBookingId
        $exists = collect(DB::select("SHOW INDEX FROM ezee_bookings WHERE Key_name = 'ezee_bookings_sub_booking_id_unique'"))->count() > 0;
        if (!$exists) {
            DB::statement('ALTER TABLE ezee_bookings ADD UNIQUE INDEX ezee_bookings_sub_booking_id_unique (SubBookingId)');
        }
    }

    public function down()
    {
        DB::statement('ALTER TABLE ezee_bookings DROP INDEX ezee_bookings_sub_booking_id_unique');
    }
};
