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
        Schema::create('booking_apis', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('book_id')->nullable();
            $table->unsignedBigInteger('listing_id')->nullable();
            $table->string('Rateplan_Id')->nullable();
            $table->string('Ratetype_Id')->nullable();
            $table->string('Roomtype_Id')->nullable();
            $table->string('ReservationNo')->nullable();
            $table->dateTime('last_try')->nullable();
            $table->boolean('success_call')->default(0);
            $table->text('error')->nullable();
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
        Schema::dropIfExists('booking_apis');
    }
};