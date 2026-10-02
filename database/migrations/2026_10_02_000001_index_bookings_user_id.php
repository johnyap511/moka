<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** The Guests page counts each guest's bookings; without this every count scanned the whole table. */
return new class extends Migration
{
    public function up(): void
    {
        if (!collect(DB::select("SHOW INDEX FROM bookings WHERE Column_name = 'user_id'"))->count()) {
            Schema::table('bookings', fn (Blueprint $t) => $t->index('user_id', 'bookings_user_id_index'));
        }
    }

    public function down(): void
    {
        Schema::table('bookings', fn (Blueprint $t) => $t->dropIndex('bookings_user_id_index'));
    }
};
