<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guests' phone numbers arrive from eZee in every shape ("60 12-588 0263", "0198239529",
 * "+6015...(823959)"). The original stays as typed; phone_e164 holds one clean
 * international number per guest for WhatsApp and deposit refunds. The backup table keeps
 * the country_code values the tidy replaces (eZee's country NAME had been stored there).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_e164', 20)->nullable()->after('phone')->index();
        });
        Schema::create('users_contact_backup', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->string('country_code', 20)->nullable();
            $table->string('phone', 60)->nullable();
            $table->timestamp('backed_up_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_contact_backup');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('phone_e164'));
    }
};
