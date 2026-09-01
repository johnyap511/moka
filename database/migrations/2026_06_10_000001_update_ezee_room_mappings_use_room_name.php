<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mappings are keyed on the unit (room_name), not the room type, so the unique
 * index moves and the two columns that are no longer always known become
 * nullable.
 *
 * The MySQL path is unchanged. Everything else rebuilds the table instead,
 * because SQLite cannot alter a column's nullability in place and the raw
 * ALTER/SHOW statements this migration used to contain made it impossible to
 * build a database on any other driver — which meant no local development
 * environment and no CI.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->rebuild(roomNameUnique: true);

            return;
        }

        $hasOldUnique = collect(DB::select("SHOW INDEX FROM ezee_room_mappings WHERE Key_name = 'ezee_room_mappings_room_type_name_unique'"))->count() > 0;
        if ($hasOldUnique) {
            DB::statement('ALTER TABLE ezee_room_mappings DROP INDEX ezee_room_mappings_room_type_name_unique');
        }

        DB::statement('ALTER TABLE ezee_room_mappings MODIFY COLUMN room_type_name VARCHAR(255) NULL');

        DB::statement('ALTER TABLE ezee_room_mappings MODIFY COLUMN ezee_group_id BIGINT UNSIGNED NULL');

        $hasRoomNameUnique = collect(DB::select("SHOW INDEX FROM ezee_room_mappings WHERE Key_name = 'ezee_room_mappings_room_name_unique'"))->count() > 0;
        if (!$hasRoomNameUnique) {
            DB::statement('ALTER TABLE ezee_room_mappings ADD UNIQUE INDEX ezee_room_mappings_room_name_unique (room_name)');
        }
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->rebuild(roomNameUnique: false);

            return;
        }

        $hasRoomNameUnique = collect(DB::select("SHOW INDEX FROM ezee_room_mappings WHERE Key_name = 'ezee_room_mappings_room_name_unique'"))->count() > 0;
        if ($hasRoomNameUnique) {
            DB::statement('ALTER TABLE ezee_room_mappings DROP INDEX ezee_room_mappings_room_name_unique');
        }
        DB::statement('ALTER TABLE ezee_room_mappings MODIFY COLUMN room_type_name VARCHAR(255) NOT NULL');
        $hasOldUnique = collect(DB::select("SHOW INDEX FROM ezee_room_mappings WHERE Key_name = 'ezee_room_mappings_room_type_name_unique'"))->count() > 0;
        if (!$hasOldUnique) {
            DB::statement('ALTER TABLE ezee_room_mappings ADD UNIQUE INDEX ezee_room_mappings_room_type_name_unique (room_type_name)');
        }
    }

    /**
     * Recreate the table with the wanted shape and carry the rows across. Only
     * used off MySQL, where an in-place column change is not available.
     */
    private function rebuild(bool $roomNameUnique): void
    {
        if (!Schema::hasTable('ezee_room_mappings')) {
            return;
        }

        Schema::dropIfExists('ezee_room_mappings_rebuild');

        Schema::create('ezee_room_mappings_rebuild', function (Blueprint $table) use ($roomNameUnique) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ezee_group_id')->nullable();
            $table->string('room_type_name')->nullable();
            $table->string('room_name')->nullable();
            $table->unsignedBigInteger('listing_id')->nullable();
            $table->timestamps();

            $roomNameUnique
                ? $table->unique('room_name', 'ezee_room_mappings_room_name_unique')
                : $table->unique('room_type_name', 'ezee_room_mappings_room_type_name_unique');
        });

        DB::statement(
            'INSERT INTO ezee_room_mappings_rebuild (id, ezee_group_id, room_type_name, room_name, listing_id, created_at, updated_at)
             SELECT id, ezee_group_id, room_type_name, room_name, listing_id, created_at, updated_at FROM ezee_room_mappings'
        );

        Schema::drop('ezee_room_mappings');
        Schema::rename('ezee_room_mappings_rebuild', 'ezee_room_mappings');
    }
};
