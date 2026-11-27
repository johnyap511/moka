<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guests brought in from outside Homemoka for the guest newsletter list (Sam, 27 Nov 2026),
 * first of all everyone who filled in the old Google deposit refund form from 2022 to 2026.
 * Kept in a table of its own so nothing here touches bookings or guest accounts. No bank
 * details: only who the guest is and where they last stayed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_guests', function (Blueprint $table) {
            $table->id();
            $table->string('email', 190)->unique();
            $table->string('name', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('building', 60)->nullable();
            $table->date('last_check_out')->nullable()->index();
            $table->string('source', 60);                 // where the record came from, e.g. "Old refund form (2022 to 2026)"
            $table->dateTime('unsubscribed_at')->nullable();   // set when the guest asks to stop; never emailed again
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_guests');
    }
};
