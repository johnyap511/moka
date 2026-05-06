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
        Schema::create('listings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('ezee_hotel_code', 30)->nullable();
            $table->string('ezee_auth_code')->nullable();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('address')->nullable();
            $table->decimal('profit', 6,2)->nullable();
            $table->string('agent_code')->nullable();
            $table->string('video', 150)->nullable();
            $table->string('banner', 50)->nullable();
            $table->string('type', 15)->nullable();
            $table->decimal('default_price', 10 ,2)->default(0);
            $table->decimal('cleaning_fee', 10 ,2)->default(0);
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
        Schema::dropIfExists('listings');
    }
};