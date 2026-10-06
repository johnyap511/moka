<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Owner reports (6 Oct 2026): one row per run of the landlord-payout report
    // generator. Files live in storage/app/reports/<id>/; done runs are the archive
    // of what was sent to owners and are never deleted from the page.
    public function up(): void
    {
        Schema::create('report_runs', function (Blueprint $table) {
            $table->id();
            $table->char('month', 7);                       // YYYY-MM of the report
            $table->string('status', 10)->default('draft'); // draft | queued | running | done | error
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('master_name', 190);
            $table->string('recon_name', 190);
            $table->string('prev_name', 190)->nullable();
            $table->json('sheets')->nullable();             // tab names inside the master
            $table->json('prev_sheets')->nullable();
            $table->string('master_sheet', 120)->nullable();
            $table->string('prev_sheet', 120)->nullable();
            $table->json('overrides')->nullable();          // the six judgment tables, or null = engine defaults
            $table->string('zip_name', 120)->nullable();
            $table->string('object', 190)->nullable();      // path inside the GCS bucket
            $table->unsignedBigInteger('size')->nullable();
            $table->decimal('total_payable', 12, 2)->nullable();
            $table->decimal('total_moka_np', 12, 2)->nullable();
            $table->json('flags')->nullable();
            $table->longText('log')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'id']);
            $table->index('month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_runs');
    }
};
