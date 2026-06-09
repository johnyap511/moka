<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('update_excels', function (Blueprint $table) {
            if (!Schema::hasColumn('update_excels', 'listing_id'))
                $table->unsignedBigInteger('listing_id')->nullable()->after('id');
            if (!Schema::hasColumn('update_excels', 'listing_name'))
                $table->string('listing_name')->nullable()->after('listing_id');
            if (!Schema::hasColumn('update_excels', 'excel_name'))
                $table->string('excel_name')->nullable()->after('listing_name');
            if (!Schema::hasColumn('update_excels', 'internet'))
                $table->decimal('internet', 10, 2)->nullable()->after('water');
            if (!Schema::hasColumn('update_excels', 'electricity'))
                $table->decimal('electricity', 10, 2)->nullable()->after('internet');
            if (!Schema::hasColumn('update_excels', 'mfsf'))
                $table->decimal('mfsf', 10, 2)->nullable()->after('electricity');
            if (!Schema::hasColumn('update_excels', 'adjustment1'))
                $table->decimal('adjustment1', 10, 2)->nullable()->after('mfsf');
            if (!Schema::hasColumn('update_excels', 'adjustment2'))
                $table->decimal('adjustment2', 10, 2)->nullable()->after('adjustment1');
            if (!Schema::hasColumn('update_excels', 'adjustment3'))
                $table->decimal('adjustment3', 10, 2)->nullable()->after('adjustment2');
            if (!Schema::hasColumn('update_excels', 'adjustment1_text'))
                $table->string('adjustment1_text')->nullable()->after('adjustment3');
            if (!Schema::hasColumn('update_excels', 'adjustment2_text'))
                $table->string('adjustment2_text')->nullable()->after('adjustment1_text');
            if (!Schema::hasColumn('update_excels', 'adjustment3_text'))
                $table->string('adjustment3_text')->nullable()->after('adjustment2_text');
            if (!Schema::hasColumn('update_excels', 'water_option'))
                $table->string('water_option')->nullable()->after('adjustment3_text');
            if (!Schema::hasColumn('update_excels', 'internet_option'))
                $table->string('internet_option')->nullable()->after('water_option');
            if (!Schema::hasColumn('update_excels', 'electricity_option'))
                $table->string('electricity_option')->nullable()->after('internet_option');
            if (!Schema::hasColumn('update_excels', 'mfsf_option'))
                $table->string('mfsf_option')->nullable()->after('electricity_option');
            if (!Schema::hasColumn('update_excels', 'adjustment1_option'))
                $table->string('adjustment1_option')->nullable()->after('mfsf_option');
            if (!Schema::hasColumn('update_excels', 'adjustment2_option'))
                $table->string('adjustment2_option')->nullable()->after('adjustment1_option');
            if (!Schema::hasColumn('update_excels', 'adjustment3_option'))
                $table->string('adjustment3_option')->nullable()->after('adjustment2_option');
            if (!Schema::hasColumn('update_excels', 'adjustment1_name'))
                $table->string('adjustment1_name')->nullable()->after('adjustment3_option');
            if (!Schema::hasColumn('update_excels', 'adjustment2_name'))
                $table->string('adjustment2_name')->nullable()->after('adjustment1_name');
            if (!Schema::hasColumn('update_excels', 'adjustment3_name'))
                $table->string('adjustment3_name')->nullable()->after('adjustment2_name');
            if (!Schema::hasColumn('update_excels', 'old_profit'))
                $table->decimal('old_profit', 10, 2)->nullable()->after('adjustment3_name');
        });
    }

    public function down()
    {
        Schema::table('update_excels', function (Blueprint $table) {
            $table->dropColumn([
                'listing_id', 'listing_name', 'excel_name',
                'internet', 'electricity', 'mfsf',
                'adjustment1', 'adjustment2', 'adjustment3',
                'adjustment1_text', 'adjustment2_text', 'adjustment3_text',
                'water_option', 'internet_option', 'electricity_option', 'mfsf_option',
                'adjustment1_option', 'adjustment2_option', 'adjustment3_option',
                'adjustment1_name', 'adjustment2_name', 'adjustment3_name',
                'old_profit',
            ]);
        });
    }
};
