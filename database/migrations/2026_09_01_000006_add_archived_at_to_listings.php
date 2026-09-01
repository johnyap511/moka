<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Archiving a listing means the business no longer manages that property.
 *
 * Separate from `status`, which is the live/not-live switch: a listing can be
 * temporarily inactive and still be managed. Reusing status for both would make
 * "inactive" ambiguous and would drag archived properties back into every
 * screen that filters on status.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('listings', 'archived_at')) {
            return;
        }

        Schema::table('listings', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->index();
        });
    }

    public function down()
    {
        if (Schema::hasColumn('listings', 'archived_at')) {
            Schema::table('listings', function (Blueprint $table) {
                $table->dropColumn('archived_at');
            });
        }
    }
};
