<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Only add unique index if there are no duplicate SubBookingIds remaining.
        // Use the "Remove Duplicates" button on the EZEE Bookings page to clean up first.
        $dupeCount = DB::table('ezee_bookings')
            ->select('SubBookingId')
            ->whereNotNull('SubBookingId')
            ->groupBy('SubBookingId')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($dupeCount > 0) {
            // Can't add unique index yet — duplicates still exist.
            // Run "Remove Duplicates" from the EZEE Bookings page, then re-run migrate.
            \Illuminate\Support\Facades\Log::warning("ezee_bookings: skipped unique index on SubBookingId — {$dupeCount} duplicate groups still exist. Use the Remove Duplicates button first.");
            return;
        }

        $exists = collect(DB::select("SHOW INDEX FROM ezee_bookings WHERE Key_name = 'ezee_bookings_sub_booking_id_unique'"))->count() > 0;
        if (!$exists) {
            DB::statement('ALTER TABLE ezee_bookings ADD UNIQUE INDEX ezee_bookings_sub_booking_id_unique (SubBookingId)');
        }
    }

    public function down()
    {
        $exists = collect(DB::select("SHOW INDEX FROM ezee_bookings WHERE Key_name = 'ezee_bookings_sub_booking_id_unique'"))->count() > 0;
        if ($exists) {
            DB::statement('ALTER TABLE ezee_bookings DROP INDEX ezee_bookings_sub_booking_id_unique');
        }
    }
};
