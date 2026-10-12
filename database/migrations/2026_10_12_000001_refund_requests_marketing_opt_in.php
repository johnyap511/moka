<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sam (12 Oct 2026): the phone and email collected on the refund form will be used
// for email and message blasts, so the guest ticks consent on the form and the
// answer is kept with the request.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->boolean('marketing_opt_in')->default(false)->after('comments');
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn('marketing_opt_in');
        });
    }
};
