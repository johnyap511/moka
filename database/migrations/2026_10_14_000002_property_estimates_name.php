<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The estimate form asks for the owner's name and emails it, but the table never had
// a name column, so the stored enquiry had no name. Keep it with the rest.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_estimates', function (Blueprint $table) {
            $table->string('name', 120)->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('property_estimates', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
