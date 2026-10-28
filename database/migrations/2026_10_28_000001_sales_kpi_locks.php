<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// KPI inputs lock when the team saves them (Sam, 28 Oct 2026); only a super admin can unlock
// to correct the record. Approval of the month then makes everything final.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_kpi_locks', function (Blueprint $table) {
            $table->char('ym', 7)->primary();
            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_kpi_locks');
    }
};
