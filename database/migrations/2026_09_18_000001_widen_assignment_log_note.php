<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ezee_assignment_logs.note was varchar(255). Rule-25 review notes (10 Sep 2026)
 * are longer than that, and the failed insert aborted every auto-assign run
 * from 10 to 18 Sep, leaving September stays unassigned. TEXT ends that.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE ezee_assignment_logs MODIFY note TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE ezee_assignment_logs MODIFY note VARCHAR(255) NULL');
    }
};
