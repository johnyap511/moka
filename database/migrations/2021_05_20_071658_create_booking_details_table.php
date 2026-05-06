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
        Schema::create('booking_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('booking_id');
            $table->decimal('insurance', 8,2)->default(0);
            $table->decimal('discount', 8,2)->default(0);
            $table->decimal('promo', 8,2)->default(0);
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
        Schema::dropIfExists('booking_details');
    }
};