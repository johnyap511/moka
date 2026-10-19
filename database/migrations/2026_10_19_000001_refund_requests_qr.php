<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Alipay and Touch 'n Go guests may upload a payment QR code instead of typing an
// account number (Sam, 19 Oct 2026). The image is kept privately under storage/app.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->string('qr_path', 160)->nullable()->after('bank_account');
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn('qr_path');
        });
    }
};
