<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// One guest, two units (Sam, 13 Oct 2026): one request per unit, tied together as a
// group ("1 of 2", "2 of 2") so Finance pays them together and each reconciles to
// its own eZee deposit.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->string('group_key', 24)->nullable()->index()->after('duplicate_of');
            $table->unsignedTinyInteger('group_index')->nullable()->after('group_key');
            $table->unsignedTinyInteger('group_size')->nullable()->after('group_index');
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn(['group_key', 'group_index', 'group_size']);
        });
    }
};
