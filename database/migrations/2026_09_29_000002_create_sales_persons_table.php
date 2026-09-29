<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Sales Person names as eZee writes them, tied to the staff login that may see that person's commission. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_persons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_persons');
    }
};
