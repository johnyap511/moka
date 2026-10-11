<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Deposit refund portal (Sam, 11 Oct 2026): replaces the Google Form. A link
// valid for 48 hours is shown on the admin dashboard and pasted into the daily
// check-out message; guests fill in the same questions as the old form; each
// request is matched silently to its booking and kept for Finance. Records are
// never purged.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_links', function (Blueprint $table) {
            $table->id();
            $table->string('token', 40)->unique();
            $table->timestamp('expires_at')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refund_link_id')->nullable()->index();
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            // matched: booking found on property + unit + check-out; verify: no
            // or uncertain match, Finance checks; duplicate: a request for the
            // same booking already exists.
            $table->string('match', 12)->default('verify')->index();
            $table->string('match_note', 255)->nullable();
            $table->unsignedBigInteger('duplicate_of')->nullable();

            $table->string('name', 120);
            $table->string('email', 150)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('phone_digits', 20)->nullable()->index();
            $table->string('property', 40);
            $table->string('unit_no', 40);
            $table->date('check_out')->index();

            $table->string('bank_name', 80);
            $table->text('bank_holder');     // encrypted
            $table->text('bank_account');    // encrypted

            $table->unsignedTinyInteger('rating_clean')->nullable();
            $table->unsignedTinyInteger('rating_service')->nullable();
            $table->text('comments')->nullable();
            $table->unsignedBigInteger('staff_user_id')->nullable()->index();
            $table->unsignedTinyInteger('staff_rating')->nullable();
            $table->text('staff_comment')->nullable();

            $table->string('status', 12)->default('pending')->index(); // pending | paid | rejected
            $table->date('due_on')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->string('reference', 120)->nullable();
            $table->string('note', 255)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('refund_links');
    }
};
