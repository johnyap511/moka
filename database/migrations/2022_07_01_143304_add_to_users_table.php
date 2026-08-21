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
        // Guarded: production already carries this column while its migrations
        // table does not record this migration, so an unguarded add fails on
        // any production import.
        if (Schema::hasColumn('users', 'ezee_tmp')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('ezee_tmp')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ezee_tmp');
        });
    }
};