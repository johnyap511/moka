<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The log needs to record two outcomes it could not before: a room move applied
 * automatically, and a move refused because the target unit was occupied. The
 * second is the review queue — a conflict is the only case where the system
 * stops and asks for a person.
 *
 * MySQL widens the enum in place. Other drivers rebuild the table with a plain
 * string column, because an enum cannot be altered on SQLite and leaving this
 * MySQL-only would put migrations back to being unrunnable there.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ezee_assignment_logs MODIFY COLUMN method ENUM('auto','manual','reassign','move','conflict') NOT NULL DEFAULT 'manual'");

            return;
        }

        $this->rebuild();
    }

    public function down()
    {
        DB::table('ezee_assignment_logs')->whereIn('method', ['move', 'conflict'])->delete();

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ezee_assignment_logs MODIFY COLUMN method ENUM('auto','manual','reassign') NOT NULL DEFAULT 'manual'");
        }
    }

    /** SQLite enforces an enum as a CHECK constraint, which it cannot alter. */
    private function rebuild(): void
    {
        if (!Schema::hasTable('ezee_assignment_logs')) {
            return;
        }

        Schema::dropIfExists('ezee_assignment_logs_rebuild');

        Schema::create('ezee_assignment_logs_rebuild', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ezee_booking_id');
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('old_listing_id')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->string('method')->default('manual');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        DB::statement(
            'INSERT INTO ezee_assignment_logs_rebuild (id, ezee_booking_id, listing_id, old_listing_id, assigned_by, method, note, created_at, updated_at)
             SELECT id, ezee_booking_id, listing_id, old_listing_id, assigned_by, method, note, created_at, updated_at FROM ezee_assignment_logs'
        );

        Schema::drop('ezee_assignment_logs');
        Schema::rename('ezee_assignment_logs_rebuild', 'ezee_assignment_logs');
    }
};
