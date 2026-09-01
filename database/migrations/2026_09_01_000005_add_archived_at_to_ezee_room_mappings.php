<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Archiving marks a unit as no longer managed, so it drops out of the mapping
 * screen and stops being assigned to.
 *
 * It lives on the mapping rather than on ezee_rooms because a unit can be
 * archivable without being in the synced inventory — retired units that still
 * carry bookings are exactly the ones most likely to want archiving, and they
 * exist only in the booking history.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('ezee_room_mappings', 'archived_at')) {
            return;
        }

        Schema::table('ezee_room_mappings', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->index();
        });
    }

    public function down()
    {
        if (Schema::hasColumn('ezee_room_mappings', 'archived_at')) {
            Schema::table('ezee_room_mappings', function (Blueprint $table) {
                $table->dropColumn('archived_at');
            });
        }
    }
};
