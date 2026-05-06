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
        Schema::create('ezee_bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('book_id')->nullable();
            $table->string('SubBookingId');
            $table->string('TransactionId')->nullable();
            $table->string('IsConfirmed',5)->nullable();
            $table->string('RateplanName')->nullable();
            $table->string('RoomTypeName')->nullable();
            $table->string('Start')->nullable();
            $table->string('End')->nullable();
            $table->string('CurrencyCode')->nullable();
            $table->string('TotalAmountAfterTax')->nullable();
            $table->string('TotalAmountBeforeTax')->nullable();
            $table->string('TotalDiscount')->nullable();
            $table->string('TotalExtraCharge')->nullable();
            $table->string('TotalPayment')->nullable();
            $table->string('TACommision')->nullable();
            $table->string('FirstName')->nullable();
            $table->string('LastName')->nullable();
            $table->string('Mobile')->nullable();
            $table->string('Email')->nullable();
            $table->string('Country')->nullable();
            $table->string('Source')->nullable();
            $table->tinyInteger('status')->default(5);
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
        Schema::dropIfExists('ezee_bookings');
    }
};