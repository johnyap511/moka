<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Front Desk Commission, Bonus & KPI SOP v2.0 (effective 1 Sep 2026): the HR inputs
 * the eZee report does not carry (confirmation, attendance, punctuality), adjustments
 * such as clawbacks, and the 30% deferred commission paid after the year's audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_persons', function (Blueprint $table) {
            $table->date('confirmed_on')->nullable()->after('user_id');   // null = still on probation
            $table->date('left_on')->nullable()->after('confirmed_on');
        });
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->timestamp('clawed_back_at')->nullable()->after('payable');
        });
        Schema::table('sales_report_uploads', function (Blueprint $table) {
            $table->unsignedInteger('clawbacks')->default(0)->after('rows_replaced');
        });
        Schema::create('sales_kpis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_person_id');
            $table->char('ym', 7);
            $table->boolean('employed_full_month')->default(true);
            $table->unsignedSmallInteger('absences')->default(0);           // recorded absences after any medical exception
            $table->boolean('unapproved_absence')->default(false);
            $table->unsignedSmallInteger('lateness_min')->default(0);
            $table->boolean('disciplinary')->default(false);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['sales_person_id', 'ym']);
        });
        Schema::create('sales_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_person_id')->index();
            $table->char('ym', 7)->index();
            $table->decimal('amount', 12, 2);
            $table->string('kind', 20)->default('manual');                  // manual | clawback
            $table->string('reason');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('sales_deferred_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_person_id')->index();
            $table->unsignedSmallInteger('year');
            $table->decimal('amount', 12, 2);
            $table->date('paid_on');
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_deferred_payouts');
        Schema::dropIfExists('sales_adjustments');
        Schema::dropIfExists('sales_kpis');
        Schema::table('sales_transactions', fn (Blueprint $t) => $t->dropColumn('clawed_back_at'));
        Schema::table('sales_report_uploads', fn (Blueprint $t) => $t->dropColumn('clawbacks'));
        Schema::table('sales_persons', fn (Blueprint $t) => $t->dropColumn(['confirmed_on', 'left_on']));
    }
};
