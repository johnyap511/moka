<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Refund form (Sam, 13 Nov 2026): overseas guests give their own address, not the bank's,
// and, if they use Wise, their Wise tag / email / phone. Both are personal data and are
// stored encrypted, so the columns are text. The old bank_address column keeps what
// earlier guests entered.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->text('guest_address')->nullable()->after('bank_address');
            $table->text('wise_id')->nullable()->after('guest_address');
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn(['guest_address', 'wise_id']);
        });
    }
};
