<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** A cancellation EZEE reports by name is its own kind of log entry. */
return new class extends Migration
{
    public function up()
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE ezee_assignment_logs MODIFY COLUMN method
            ENUM('auto','manual','reassign','move','conflict','modified','cancelled') NOT NULL DEFAULT 'manual'");
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("UPDATE ezee_assignment_logs SET method = 'manual' WHERE method = 'cancelled'");
        DB::statement("ALTER TABLE ezee_assignment_logs MODIFY COLUMN method
            ENUM('auto','manual','reassign','move','conflict','modified') NOT NULL DEFAULT 'manual'");
    }
};
