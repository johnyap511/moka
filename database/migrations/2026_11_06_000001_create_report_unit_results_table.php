<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-unit results of every finished owner-report run (Sam, 6 Nov 2026): what each
 * landlord is paid and what Moka keeps, month by month, with the report file inside
 * the run's zip. The basis for owner statements in the owner portal, yearly
 * statements for tax, and profit analysis across units and owners.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_unit_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('run_id');
            $table->string('month', 7);                       // YYYY-MM, the report month
            $table->string('unit', 120);                      // the unit name as the report engine prints it
            $table->unsignedBigInteger('listing_id')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable(); // listings.user_id at capture time
            $table->string('category', 40);                   // Individual, Individual (Rent), Long Term, Long Term (Rent), Group BS (Studio), …
            $table->decimal('payable', 12, 2);                // payable to landlord
            $table->decimal('moka_np', 12, 2)->nullable();    // Moka net profit
            $table->string('pdf_path', 255)->nullable();      // path inside the run's zip
            $table->string('xlsx_path', 255)->nullable();
            $table->timestamp('captured_at');
            $table->unique(['run_id', 'unit']);
            $table->index(['month']);
            $table->index(['owner_id', 'month']);
            $table->index(['listing_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_unit_results');
    }
};
