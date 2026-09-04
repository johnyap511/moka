<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The tax EZEE actually applied to a reservation's extra charges (cleaning,
// channel surcharge, late check-out). Null when the pull did not carry the
// per-charge breakdown; then the 8% assumption still applies.
return new class extends Migration {
    public function up(): void
    {
        Schema::table('ezee_bookings', function (Blueprint $table) {
            $table->decimal('extra_charge_tax', 10, 2)->nullable()->after('TotalExtraCharge');
        });
    }

    public function down(): void
    {
        Schema::table('ezee_bookings', function (Blueprint $table) {
            $table->dropColumn('extra_charge_tax');
        });
    }
};
