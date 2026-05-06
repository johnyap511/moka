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
        Schema::create('listing_price_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('listing_id');
            $table->boolean('discount_has')->nullable();
            $table->boolean('discount_fixed')->nullable();
            $table->decimal('discount_amount', 8,2)->default(0);
            $table->boolean('insurance_has')->nullable();
            $table->boolean('insurance_fixed')->nullable();
            $table->decimal('insurance_amount', 8,2)->default(0);
            $table->boolean('promo_has')->nullable();
            $table->string('promo_code', 100)->nullable();
            $table->decimal('promo_amount', 8,2)->default(0);
            $table->decimal('advance_rental', 8,2)->default(0);
            $table->decimal('security_deposit', 8,2)->default(0);
            $table->decimal('utility_deposit', 8,2)->default(0);
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
        Schema::dropIfExists('listing_price_details');
    }
};