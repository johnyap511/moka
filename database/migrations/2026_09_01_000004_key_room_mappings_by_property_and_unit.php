<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A unit name is only unique within a property. "Extra Room 1" to "Extra Room 5"
 * each exist in four different properties, so a unique index on room_name alone
 * allowed one mapping for a name that needs four — and every booking for those
 * units would have resolved to a single listing, putting guests on the wrong
 * owner's calendar.
 *
 * Existing mappings have no property recorded. Where the name belongs to exactly
 * one property it is filled in from the synced inventory; where it is ambiguous
 * it is left null and has to be mapped per property, which is the only honest
 * answer — nothing in the old data says which property was meant.
 */
return new class extends Migration
{
    public function up()
    {
        // Attribute existing mappings to a property where the name is unambiguous.
        if (Schema::hasTable('ezee_rooms')) {
            $unambiguous = DB::table('ezee_rooms')
                ->select('room_name', DB::raw('MIN(ezee_group_id) AS ezee_group_id'), DB::raw('COUNT(DISTINCT ezee_group_id) AS properties'))
                ->groupBy('room_name')
                ->havingRaw('COUNT(DISTINCT ezee_group_id) = 1')
                ->get();

            foreach ($unambiguous as $room) {
                DB::table('ezee_room_mappings')
                    ->where('room_name', $room->room_name)
                    ->whereNull('ezee_group_id')
                    ->update(['ezee_group_id' => $room->ezee_group_id]);
            }
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $hasOld = collect(DB::select("SHOW INDEX FROM ezee_room_mappings WHERE Key_name = 'ezee_room_mappings_room_name_unique'"))->isNotEmpty();
            if ($hasOld) {
                DB::statement('ALTER TABLE ezee_room_mappings DROP INDEX ezee_room_mappings_room_name_unique');
            }

            $hasNew = collect(DB::select("SHOW INDEX FROM ezee_room_mappings WHERE Key_name = 'ezee_room_mappings_group_room_unique'"))->isNotEmpty();
            if (!$hasNew) {
                DB::statement('ALTER TABLE ezee_room_mappings ADD UNIQUE INDEX ezee_room_mappings_group_room_unique (ezee_group_id, room_name)');
            }

            return;
        }

        Schema::table('ezee_room_mappings', function ($table) {
            $table->dropUnique('ezee_room_mappings_room_name_unique');
            $table->unique(['ezee_group_id', 'room_name'], 'ezee_room_mappings_group_room_unique');
        });
    }

    public function down()
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $hasNew = collect(DB::select("SHOW INDEX FROM ezee_room_mappings WHERE Key_name = 'ezee_room_mappings_group_room_unique'"))->isNotEmpty();
            if ($hasNew) {
                DB::statement('ALTER TABLE ezee_room_mappings DROP INDEX ezee_room_mappings_group_room_unique');
            }

            return;
        }

        Schema::table('ezee_room_mappings', function ($table) {
            $table->dropUnique('ezee_room_mappings_group_room_unique');
        });
    }
};
