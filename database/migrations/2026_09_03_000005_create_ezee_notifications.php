<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every event EZEE's notification queue delivers, kept before it is
 * acknowledged, so a cancellation is never lost the way it was when the
 * queue was read without being recorded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ezee_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->string('res_no', 40)->index();
            $table->string('status', 20);              // New, Modify, Cancel
            $table->dateTime('event_at')->nullable();   // Canceldatetime for cancellations
            $table->string('remark', 255)->nullable();
            $table->string('voucher_no', 100)->nullable();
            $table->json('payload');
            $table->string('applied', 40)->nullable();  // what was done: cancelled, review, refreshed, created, ignored
            $table->text('applied_note')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
            $table->unique(['hotel_code', 'res_no', 'status', 'event_at'], 'ezee_notifications_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ezee_notifications');
    }
};
