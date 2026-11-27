<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contact-form messages are listed under Sales in the admin (Sam, 27 Nov 2026), so the
 * team can mark each one once the sender has been answered. Adds two empty columns; no
 * existing row changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dateTime('followed_up_at')->nullable();
            $table->unsignedBigInteger('followed_up_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', fn (Blueprint $t) => $t->dropColumn(['followed_up_at', 'followed_up_by']));
    }
};
