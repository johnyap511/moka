<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ezee_room_mappings', function (Blueprint $table) {
            // Drop old unique constraint on room_type_name
            $table->dropUnique(['room_type_name']);
            // room_type_name is now optional context only
            $table->string('room_type_name')->nullable()->change();
            // ezee_group_id is optional
            $table->unsignedBigInteger('ezee_group_id')->nullable()->change();
        });

        // Add unique index on room_name only if not already present
        if (!collect(\DB::select("SHOW INDEX FROM ezee_room_mappings WHERE Key_name = 'ezee_room_mappings_room_name_unique'"))->count()) {
            Schema::table('ezee_room_mappings', function (Blueprint $table) {
                $table->unique('room_name');
            });
        }
    }

    public function down()
    {
        Schema::table('ezee_room_mappings', function (Blueprint $table) {
            $table->dropUnique(['room_name']);
            $table->string('room_type_name')->nullable(false)->change();
            $table->unique('room_type_name');
        });
    }
};
