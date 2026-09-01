<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conflicts need a lifecycle.
 *
 * Without one a conflict is logged once and stays outstanding forever, so the
 * review queue only ever grows and still shows the same count after everything
 * in it has been dealt with — including entries written by a bug that has since
 * been fixed.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('ezee_assignment_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('ezee_assignment_logs', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->index();
            }
            if (!Schema::hasColumn('ezee_assignment_logs', 'resolved_by')) {
                $table->unsignedBigInteger('resolved_by')->nullable();
            }
            if (!Schema::hasColumn('ezee_assignment_logs', 'resolution_note')) {
                $table->string('resolution_note')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('ezee_assignment_logs', function (Blueprint $table) {
            $table->dropColumn(['resolved_at', 'resolved_by', 'resolution_note']);
        });
    }
};
