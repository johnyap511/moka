<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VoucherNo is the channel's own reference for the reservation — the number
 * printed on an OTA statement ("Booking number 6105742553" on Booking.com's
 * payout, "Reservation #2459195245" on Expedia's). Without it, reconciling the
 * export against a statement means matching on guest name and dates.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('ezee_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('ezee_bookings', 'VoucherNo')) {
                $table->string('VoucherNo', 64)->nullable()->after('SubBookingId');
                $table->index('VoucherNo');
            }
        });
    }

    public function down()
    {
        Schema::table('ezee_bookings', function (Blueprint $table) {
            $table->dropIndex(['VoucherNo']);
            $table->dropColumn('VoucherNo');
        });
    }
};
