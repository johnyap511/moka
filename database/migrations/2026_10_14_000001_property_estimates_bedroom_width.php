<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The estimate form sends "2 Bedrooms" but the column held 5 characters, so every
// such request failed with a 500 (two from one visitor on 14 Oct 2026). Widen it.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE property_estimates MODIFY bedroom VARCHAR(60) NULL');
    }

    public function down(): void {}
};
