<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The EZEE unit identifier for this listing, e.g. "C2-07-10".
     *
     * EZEE sends it on every booking as eZeePMSRoomid. Recording the same value
     * against the listing is what lets a booking be matched to its unit
     * automatically, without a separate mapping table.
     */
    public function up()
    {
        if (Schema::hasColumn('listings', 'ezee_room_id')) {
            return;
        }

        Schema::table('listings', function (Blueprint $table) {
            $table->string('ezee_room_id', 60)->nullable()->after('ezee_auth_code');
            $table->index('ezee_room_id');
        });
    }

    public function down()
    {
        if (! Schema::hasColumn('listings', 'ezee_room_id')) {
            return;
        }

        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['ezee_room_id']);
            $table->dropColumn('ezee_room_id');
        });
    }
};
