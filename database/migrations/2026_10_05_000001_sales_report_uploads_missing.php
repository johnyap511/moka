<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** How many stays an uploaded eZee report showed that Homemoka did not have (rule 26 check). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_report_uploads', fn (Blueprint $t) => $t->unsignedInteger('missing')->default(0)->after('clawbacks'));
    }

    public function down(): void
    {
        Schema::table('sales_report_uploads', fn (Blueprint $t) => $t->dropColumn('missing'));
    }
};
