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
        Schema::create('bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('listing_id')->nullable();
            $table->string('folio_no')->nullable();
            $table->date('check_in');
            $table->date('check_out');
            $table->decimal('adult', 2, 0)->default(1);
            $table->decimal('infant', 2, 0)->default(0);
            $table->string('remark')->nullable();
            $table->string('source', 30)->nullable('Website');
            $table->string('category', 30)->default('Accommodation');
            $table->decimal('nights', 8, 0)->default(1);
            $table->decimal('price_night', 9, 2)->nullable();
            $table->decimal('cleaning_fee', 9, 2)->nullable();
            $table->decimal('ota_fee', 9, 2)->nullable();
            $table->decimal('sst', 9, 2)->nullable();
            $table->decimal('price', 9, 2)->nullable();
            $table->tinyInteger('status')->default(3);
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
        Schema::dropIfExists('bookings');
    }
};