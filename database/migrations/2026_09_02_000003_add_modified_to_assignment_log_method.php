<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A reservation amended in EZEE after we assigned it needs a person to look:
 * the dates may have moved, or the guest may have been shifted between rooms
 * mid-stay, which the booking API cannot express. Giving it its own method puts
 * it in the review queue staff already work from, beside conflicts.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return; // other drivers store this as a plain string
        }

        DB::statement("ALTER TABLE ezee_assignment_logs MODIFY COLUMN method
            ENUM('auto','manual','reassign','move','conflict','modified') NOT NULL DEFAULT 'manual'");
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE ezee_assignment_logs SET method = 'manual' WHERE method = 'modified'");
        DB::statement("ALTER TABLE ezee_assignment_logs MODIFY COLUMN method
            ENUM('auto','manual','reassign','move','conflict') NOT NULL DEFAULT 'manual'");
    }
};
