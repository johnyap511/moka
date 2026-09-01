<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Written originally as a raw MySQL ALTER, which meant migrations could not run
 * on SQLite and so no local or CI database could be built. The schema builder
 * does the same thing on every driver; only MySQL honours column ordering, and
 * nothing depends on it.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('ezee_bookings', 'folio_no')) {
            return;
        }

        Schema::table('ezee_bookings', function (Blueprint $table) {
            $column = $table->string('folio_no', 50)->nullable();

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $column->after('SubBookingId');
            }
        });
    }

    public function down()
    {
        if (Schema::hasColumn('ezee_bookings', 'folio_no')) {
            Schema::table('ezee_bookings', function (Blueprint $table) {
                $table->dropColumn('folio_no');
            });
        }
    }
};
