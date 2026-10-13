<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Sam, 13 Oct 2026: deposit is always RM 200; Operations have a cut-off to record
// deductions (noon the next working day); Finance confirms the deposit was received
// (bank statement / SQL accounting) before paying.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->timestamp('ops_cutoff_at')->nullable()->after('deduction_at');
            $table->timestamp('received_at')->nullable()->after('ops_cutoff_at');
            $table->unsignedBigInteger('received_by')->nullable()->after('received_at');
        });
        DB::table('refund_requests')->whereNull('deposit_amount')->update(['deposit_amount' => 200.00]);
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn(['ops_cutoff_at', 'received_at', 'received_by']);
        });
    }
};
