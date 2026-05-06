<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('listing_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('listing_id');
            $table->string('year', 4);
            $table->string('month', 2);
            $table->string('excel_name')->nullable();
            $table->boolean('water')->default(0);
            $table->decimal('water_amount', 12, 2)->nullable();
            $table->string('water_receipt')->nullable();
            $table->boolean('internet')->default(0);
            $table->decimal('internet_amount', 12, 2)->nullable();
            $table->string('internet_receipt')->nullable();
            $table->boolean('electricity')->default(0);
            $table->decimal('electricity_amount', 12, 2)->nullable();
            $table->string('electricity_receipt')->nullable();
            $table->boolean('adjustment1')->default(0);
            $table->string('adjustment1_text')->nullable();
            $table->decimal('adjustment1_amount', 12, 2)->nullable();
            $table->string('adjustment1_receipt')->nullable();
            $table->boolean('adjustment2')->default(0);
            $table->string('adjustment2_text')->nullable();
            $table->decimal('adjustment2_amount', 12, 2)->nullable();
            $table->string('adjustment2_receipt')->nullable();
            $table->boolean('adjustment3')->default(0);
            $table->string('adjustment3_text')->nullable();
            $table->decimal('adjustment3_amount', 12, 2)->nullable();
            $table->string('adjustment3_receipt')->nullable();
            $table->decimal('owner_income', 12, 2)->nullable();
            $table->decimal('platform_income', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('listing_reports');
    }
};