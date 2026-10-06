<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Monthly approval of the sales commission (Sam, 6 Oct 2026): once approved the month is
// final — its figures are frozen as a snapshot, uploads no longer replace its rows, and
// adjustments go to the next open month.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_month_approvals', function (Blueprint $table) {
            $table->id();
            $table->char('ym', 7)->unique();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_month_approvals');
    }
};
