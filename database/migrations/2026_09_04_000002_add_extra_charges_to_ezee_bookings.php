<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// EZEE's per-charge breakdown of a reservation's extras (name, before tax,
// after tax). TotalExtraCharge lumps cleaning, deposits and incidentals
// together; the breakdown lets MOKA take only the cleaning fee as the
// cleaning fee, and exactly the tax EZEE applied to it.
return new class extends Migration {
    public function up(): void
    {
        Schema::table('ezee_bookings', function (Blueprint $table) {
            $table->json('extra_charges')->nullable()->after('extra_charge_tax');
        });
    }

    public function down(): void
    {
        Schema::table('ezee_bookings', function (Blueprint $table) {
            $table->dropColumn('extra_charges');
        });
    }
};
