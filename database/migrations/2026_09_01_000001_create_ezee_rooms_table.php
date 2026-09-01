<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The authoritative list of units EZEE holds for each property.
 *
 * Until now the only way to learn a unit's name was to see it referenced by a
 * booking, which meant a unit nobody had stayed in was invisible and could not
 * be mapped. This table is filled from EZEE's housekeeping room list, which
 * reports every unit whether or not it has ever been booked.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('ezee_rooms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ezee_group_id')->nullable();
            $table->string('hotel_code', 32)->index();

            // EZEE's own identifiers for the unit.
            $table->string('ezee_room_id', 64)->nullable();
            $table->string('ezee_unit_id', 64)->nullable();

            // The unit as EZEE names it (e.g. "H-10-02"). This is the value that
            // arrives on a booking as eZeePMSRoomid and is stored on
            // ezee_bookings.RoomName, so it is what mappings are keyed on.
            $table->string('room_name');
            $table->string('room_type_name')->nullable();

            $table->boolean('is_blocked')->default(false);

            // When EZEE last reported this unit. A unit that stops appearing has
            // been removed from the property, so it is kept and marked rather
            // than deleted — mappings pointing at it must not vanish silently.
            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();

            $table->unique(['hotel_code', 'room_name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ezee_rooms');
    }
};
