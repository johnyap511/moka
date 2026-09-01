<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A reservation is identified by SubBookingId *and* TransactionId, not by
 * SubBookingId alone.
 *
 * EZEE numbers reservations per property: RES6103 exists at EkoCheras, Bell
 * Suites and Forum as three unrelated bookings, and 20,393 reservation numbers
 * are shared across properties this way. A unique key on SubBookingId alone
 * would have refused to store all but the first of them.
 *
 * On the existing databases that index never applied — duplicates were already
 * present, so the guard skipped it — but a database built from scratch would
 * have got it, and the sync would then have silently dropped bookings.
 */
return new class extends Migration
{
    public function up()
    {
        $name = 'ezee_bookings_sub_booking_id_unique';

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return; // fresh non-MySQL databases already build the correct key
        }

        $existing = collect(DB::select("SHOW INDEX FROM ezee_bookings WHERE Key_name = '{$name}'"));

        // Already correct if it covers two columns.
        if ($existing->count() === 2) {
            return;
        }

        if ($existing->isNotEmpty()) {
            DB::statement("ALTER TABLE ezee_bookings DROP INDEX {$name}");
        }

        $clashes = DB::table('ezee_bookings')
            ->select('SubBookingId', 'TransactionId')
            ->groupBy('SubBookingId', 'TransactionId')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($clashes > 0) {
            // Leave the data alone and say so; deciding which row survives is not
            // something a migration should guess at.
            echo "  skipped: {$clashes} SubBookingId/TransactionId pair(s) are duplicated; resolve those first\n";

            return;
        }

        Schema::table('ezee_bookings', function (Blueprint $table) use ($name) {
            $table->unique(['SubBookingId', 'TransactionId'], $name);
        });
    }

    public function down()
    {
        //
    }
};
