<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Drop unique constraint on room_type_name
        DB::statement('ALTER TABLE ezee_room_mappings DROP INDEX ezee_room_mappings_room_type_name_unique');

        // Make room_type_name nullable (just metadata now)
        DB::statement('ALTER TABLE ezee_room_mappings MODIFY COLUMN room_type_name VARCHAR(255) NULL');

        // Make ezee_group_id nullable
        DB::statement('ALTER TABLE ezee_room_mappings MODIFY COLUMN ezee_group_id BIGINT UNSIGNED NULL');

        // Add unique index on room_name (only if not already present)
        $indexExists = collect(DB::select("SHOW INDEX FROM ezee_room_mappings WHERE Key_name = 'ezee_room_mappings_room_name_unique'"))->count() > 0;
        if (!$indexExists) {
            DB::statement('ALTER TABLE ezee_room_mappings ADD UNIQUE INDEX ezee_room_mappings_room_name_unique (room_name)');
        }
    }

    public function down()
    {
        DB::statement('ALTER TABLE ezee_room_mappings DROP INDEX ezee_room_mappings_room_name_unique');
        DB::statement('ALTER TABLE ezee_room_mappings MODIFY COLUMN room_type_name VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE ezee_room_mappings ADD UNIQUE INDEX ezee_room_mappings_room_type_name_unique (room_type_name)');
    }
};
