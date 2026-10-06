<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Inactive listings are archived listings (Sam, 6 Oct 2026): align the two flags once.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('listings')->where('status', 0)->whereNull('archived_at')->update(['archived_at' => now()]);
        DB::table('listings')->whereNotNull('archived_at')->where('status', 1)->update(['status' => 0]);
    }

    public function down(): void {}
};
