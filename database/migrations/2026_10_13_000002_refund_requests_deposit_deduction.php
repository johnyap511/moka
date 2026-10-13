<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Deductions (Sam, 13 Oct 2026): the Operation Team records the deposit held and
// anything deducted (with the reason) on the request, so Finance knows how much
// to refund. Finance records the amount actually paid.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('check_out');
            $table->decimal('deduction_amount', 10, 2)->default(0)->after('deposit_amount');
            $table->string('deduction_reason', 255)->nullable()->after('deduction_amount');
            $table->unsignedBigInteger('deduction_by')->nullable()->after('deduction_reason');
            $table->timestamp('deduction_at')->nullable()->after('deduction_by');
            $table->decimal('amount_paid', 10, 2)->nullable()->after('paid_by');
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn(['deposit_amount', 'deduction_amount', 'deduction_reason', 'deduction_by', 'deduction_at', 'amount_paid']);
        });
    }
};
