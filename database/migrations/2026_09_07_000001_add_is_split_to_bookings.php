<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Split booking: the guest changed room during one eZee reservation.
            // Set by MOKA's split, or ticked by staff when only eZee shows it.
            $table->boolean('is_split')->default(false)->after('remark');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('is_split');
        });
    }
};
