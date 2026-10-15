<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PMS reconciliation (Sam, 15 Oct 2026, after Bell Suites 07-30): every eZee
// reservation is compared with its Homemoka booking on unit, dates, status and
// amount, daily and at month-end. Differences are kept here until a person
// resolves them or the data clears them; the owner-report run is gated on it.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pms_differences', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 80)->unique();   // kind + reservation or booking
            $table->char('kind', 1);                         // A..H, see App\Support\PmsReconcile::KINDS
            $table->string('sub_booking_id', 40)->nullable()->index();
            $table->unsignedBigInteger('ezee_booking_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->unsignedBigInteger('listing_id')->nullable();
            $table->string('unit', 120)->nullable();
            $table->date('stay_from')->nullable();
            $table->date('stay_to')->nullable();
            $table->char('month', 7)->index();               // check-in month, for the month-end gate
            $table->string('detail', 255);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->string('resolution', 255)->nullable();
            $table->boolean('auto_resolved')->default(false);
            $table->timestamps();
        });

        Schema::create('pms_reconcile_runs', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 20);                     // daily | month
            $table->char('month', 7)->nullable()->index();
            $table->date('from');
            $table->date('to');
            $table->unsignedInteger('reservations')->default(0);
            $table->unsignedInteger('bookings')->default(0);
            $table->unsignedInteger('open')->default(0);
            $table->unsignedInteger('new')->default(0);
            $table->unsignedInteger('cleared')->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('ran_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pms_differences');
        Schema::dropIfExists('pms_reconcile_runs');
    }
};
