<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        // SHOW INDEX is MySQL-only and made migrations unrunnable on SQLite.
        $exists = self::hasUniqueIndex();
        if (!$exists) {
            Schema::table('ezee_bookings', function (Blueprint $table) {
                // EZEE numbers reservations per property, so SubBookingId alone is not
                // unique: RES6103 exists at EkoCheras, Bell Suites and Forum as three
                // different bookings. The reservation is identified by the pair.
                $table->unique(['SubBookingId', 'TransactionId'], 'ezee_bookings_sub_booking_id_unique');
            });
        }
    }

    public function down()
    {
        // SHOW INDEX is MySQL-only and made migrations unrunnable on SQLite.
        $exists = self::hasUniqueIndex();
        if ($exists) {
            Schema::table('ezee_bookings', function (Blueprint $table) {
                $table->dropUnique('ezee_bookings_sub_booking_id_unique');
            });
        }
    }

    /** Index introspection has no portable form, so ask each driver its own way. */
    private static function hasUniqueIndex(): bool
    {
        $name = 'ezee_bookings_sub_booking_id_unique';

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            return collect(DB::select("SHOW INDEX FROM ezee_bookings WHERE Key_name = '{$name}'"))->isNotEmpty();
        }

        // SQLite records indexes in sqlite_master.
        return collect(DB::select(
            "SELECT name FROM sqlite_master WHERE type = 'index' AND name = ?",
            [$name]
        ))->isNotEmpty();
    }
};
