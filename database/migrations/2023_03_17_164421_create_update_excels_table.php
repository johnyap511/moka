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
        Schema::create('update_excels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('booking_id')->nullable();
            $table->date('excel_date');
            $table->decimal('waterOwner')->default(0.00);
            $table->decimal('waterHost')->default(0.00);
            $table->decimal('water')->default(0.00);
            $table->decimal('totalOwnerPrice')->default(0.00);
            $table->decimal('totalHostPrice')->default(0.00);
            $table->tinyInteger('status')->default(0);
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
        Schema::dropIfExists('update_excels');
    }
};