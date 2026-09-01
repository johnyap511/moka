<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The log needs to record two outcomes it could not before: a room move applied
 * automatically, and a move that was refused because the target unit was
 * already occupied. The second is the review queue — a conflict is the only
 * case where the system stops and asks for a person.
 */
return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE ezee_assignment_logs MODIFY COLUMN method ENUM('auto','manual','reassign','move','conflict') NOT NULL DEFAULT 'manual'");
    }

    public function down()
    {
        DB::statement("DELETE FROM ezee_assignment_logs WHERE method IN ('move','conflict')");
        DB::statement("ALTER TABLE ezee_assignment_logs MODIFY COLUMN method ENUM('auto','manual','reassign') NOT NULL DEFAULT 'manual'");
    }
};
