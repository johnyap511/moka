<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ezee_bookings', function (Blueprint $table) {
            $table->string('RoomName')->nullable()->after('RoomTypeName');
            $table->unsignedBigInteger('ezee_group_id')->nullable()->after('book_id');
        });
    }

    public function down()
    {
        Schema::table('ezee_bookings', function (Blueprint $table) {
            $table->dropColumn(['RoomName', 'ezee_group_id']);
        });
    }
};
