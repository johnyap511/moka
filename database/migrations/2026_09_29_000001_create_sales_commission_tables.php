<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sales commission from eZee's Transaction Detail Report (Sam, 29 Sep 2026).
 * The report is the only place eZee exposes the Sales Person; the API never
 * sends it. Each upload replaces the rows of its property and date range, so
 * re-uploading a week never double counts and the latest report always wins.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_report_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->string('filename');
            $table->date('period_from');
            $table->date('period_to');
            $table->unsignedInteger('rows_in_file');
            $table->unsignedInteger('rows_stored');
            $table->unsignedInteger('rows_skipped_locked')->default(0);
            $table->unsignedInteger('rows_replaced')->default(0);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('upload_id')->index();
            $table->string('hotel_code', 10);
            $table->string('res_no', 40)->nullable();
            $table->string('folio_no', 40);
            $table->string('guest_name', 160)->nullable();
            $table->string('business_source', 80)->nullable();
            $table->string('sales_person', 80)->index();
            $table->string('room_no', 80)->nullable();
            $table->date('arrival')->nullable();
            $table->date('departure')->nullable();
            $table->string('charge', 80);
            $table->date('tran_date');
            $table->decimal('net_amount', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->string('booking_status', 40)->nullable();
            $table->string('folio_status', 20)->nullable();
            $table->boolean('payable')->default(true);
            $table->timestamps();
            $table->index(['hotel_code', 'tran_date']);
            $table->index(['hotel_code', 'folio_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_transactions');
        Schema::dropIfExists('sales_report_uploads');
    }
};
