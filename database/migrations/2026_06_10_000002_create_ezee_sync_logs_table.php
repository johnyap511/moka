<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('ezee_sync_logs')) {
            return;
        }

        Schema::create('ezee_sync_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('from_date');
            $table->date('to_date');
            $table->unsignedInteger('new_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('unchanged_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);
            $table->decimal('duration_seconds', 8, 2)->nullable();
            $table->json('details')->nullable(); // array of per-booking actions
            $table->unsignedBigInteger('ran_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ezee_sync_logs');
    }
};
