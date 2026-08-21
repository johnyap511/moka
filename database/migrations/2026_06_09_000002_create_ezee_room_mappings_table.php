<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('ezee_room_mappings')) {
            return;
        }

        Schema::create('ezee_room_mappings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ezee_group_id');
            $table->string('room_type_name');     // RoomTypeName from EZEE (e.g. "Studio")
            $table->string('room_name')->nullable(); // RoomName if available (more specific)
            $table->unsignedBigInteger('listing_id')->nullable();
            $table->timestamps();

            $table->unique('room_type_name');
        });

        Schema::create('ezee_assignment_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ezee_booking_id');
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('old_listing_id')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable(); // user_id, null = auto
            $table->enum('method', ['auto', 'manual', 'reassign'])->default('manual');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ezee_assignment_logs');
        Schema::dropIfExists('ezee_room_mappings');
    }
};
