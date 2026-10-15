<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tenancy settlement statements (Sam, 15 Oct 2026): Finance's utility-proration
// and deposit-settlement calculator moved into Homemoka. A statement is final
// once saved: the inputs, every computed line and the PDF bytes are kept here
// for safekeeping. Mistakes are voided (kept, flagged), never deleted.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenancy_statements', function (Blueprint $table) {
            $table->id();
            $table->string('no', 24)->unique();              // MKS-2026-0001
            $table->string('tenant_name', 120);
            $table->string('booking_ref', 80)->nullable();
            $table->string('property', 160)->nullable();
            $table->string('unit_names', 255)->nullable();   // "H-07-9C, H-30-3A"
            $table->string('prepared_by', 80)->nullable();
            $table->date('stay_from')->nullable();
            $table->date('stay_to')->nullable();
            $table->unsignedSmallInteger('stay_days')->default(0);
            $table->boolean('inclusive')->default(true);     // check-out day charged
            $table->decimal('bill_total', 10, 2)->default(0);
            $table->decimal('addon_total', 10, 2)->default(0);
            $table->decimal('arrears', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('deposit', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->nullable();   // deposit − total; null when no deposit
            $table->text('remarks')->nullable();
            $table->longText('input');                       // what Finance keyed in (JSON)
            $table->longText('lines');                       // what was computed from it (JSON)
            $table->string('pdf_path', 160)->nullable();
            $table->unsignedInteger('pdf_size')->nullable();
            $table->string('pdf_sha1', 40)->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->string('void_reason', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['tenant_name']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenancy_statements');
    }
};
