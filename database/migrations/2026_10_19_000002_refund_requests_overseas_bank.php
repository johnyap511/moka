<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Foreign guests are refunded through Wise, which needs the bank's country, SWIFT/BIC,
// sometimes a routing / sort / IFSC / BSB code and the bank address (Sam, 19 Oct 2026).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->string('bank_country', 60)->nullable()->after('qr_path');
            $table->string('bank_swift', 20)->nullable()->after('bank_country');
            $table->string('bank_extra_code', 40)->nullable()->after('bank_swift');
            $table->string('bank_address', 200)->nullable()->after('bank_extra_code');
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn(['bank_country', 'bank_swift', 'bank_extra_code', 'bank_address']);
        });
    }
};
