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
        Schema::create('rate_non_linears', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('listing_id');
            $table->foreign('listing_id')->references('id')->on('listings')->onDelete('cascade');
            $table->string('room_type_id', 30)->nullable();
            $table->string('rate_type_id', 30)->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->decimal('rate_adult_1', 12, 2)->default(0);
            $table->decimal('rate_adult_2', 12, 2)->default(0);
            $table->decimal('rate_adult_3', 12, 2)->default(0);
            $table->decimal('rate_adult_4', 12, 2)->default(0);
            $table->decimal('rate_adult_5', 12, 2)->default(0);
            $table->decimal('rate_adult_6', 12, 2)->default(0);
            $table->decimal('rate_adult_7', 12, 2)->default(0);
            $table->decimal('rate_child_1', 12, 2)->default(0);
            $table->decimal('rate_child_2', 12, 2)->default(0);
            $table->decimal('rate_child_3', 12, 2)->default(0);
            $table->decimal('rate_child_4', 12, 2)->default(0);
            $table->decimal('rate_child_5', 12, 2)->default(0);
            $table->decimal('rate_child_6', 12, 2)->default(0);
            $table->decimal('rate_child_7', 12, 2)->default(0);
            $table->tinyInteger('status')->default(1);
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
        Schema::dropIfExists('rate_non_linears');
    }
};