<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // Pool profit sharing (ground rule 23): a unit's share of its pool is its
            // weight over the pool's total weight. Weight follows unit size, e.g. 900
            // for a 3-bedroom and 600 for a studio; 1 everywhere means equal shares.
            $table->decimal('pool_weight', 8, 2)->default(1)->after('profit');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('pool_weight');
        });
    }
};
