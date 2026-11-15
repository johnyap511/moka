<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Follow-up record on owner estimate requests: when it was followed up and by whom.
// status stays as it is: 1 = new, 2 = followed up. No existing row is changed.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_estimates', function (Blueprint $table) {
            if (! Schema::hasColumn('property_estimates', 'followed_up_at')) {
                $table->timestamp('followed_up_at')->nullable();
            }
            if (! Schema::hasColumn('property_estimates', 'followed_up_by')) {
                $table->unsignedBigInteger('followed_up_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_estimates', function (Blueprint $table) {
            $table->dropColumn(['followed_up_at', 'followed_up_by']);
        });
    }
};
