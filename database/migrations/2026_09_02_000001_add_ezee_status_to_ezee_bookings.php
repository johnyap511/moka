<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EZEE states on every reservation whether it is new or amended, where the guest
 * is in their stay, and when it was last changed. None of it was stored.
 *
 * Modifydatetime is the useful one. The booking API reports only the final room
 * on a reservation, so a guest moved mid-stay looks like they were in the last
 * room the whole time — RES3413 spent 17 nights in AL-23-11 and one in Extra
 * Room 1, and the API shows only Extra Room 1. The room history cannot be
 * recovered, but the fact that a reservation changed, and when, can be. That
 * turns an invisible edit into a review item.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('ezee_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('ezee_bookings', 'ezee_status')) {
                $table->string('ezee_status', 32)->nullable()->after('IsConfirmed');
            }
            if (!Schema::hasColumn('ezee_bookings', 'ezee_current_status')) {
                $table->string('ezee_current_status', 64)->nullable()->after('ezee_status');
            }
            if (!Schema::hasColumn('ezee_bookings', 'ezee_modified_at')) {
                $table->dateTime('ezee_modified_at')->nullable()->after('ezee_current_status');
                $table->index('ezee_modified_at');
            }
        });
    }

    public function down()
    {
        Schema::table('ezee_bookings', function (Blueprint $table) {
            $table->dropIndex(['ezee_modified_at']);
            $table->dropColumn(['ezee_status', 'ezee_current_status', 'ezee_modified_at']);
        });
    }
};
