<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revenue desk, Market tab (Sam, 25 Nov 2026): the competitors the manager watches
 * near each building, and the nightly rates keyed in for them each week. New tables
 * only; nothing here touches bookings.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('market_competitors', function (Blueprint $t) {
            $t->id();
            $t->string('property', 60);                 // our building, as the Revenue desk names it ("EkoCheras")
            $t->string('room_type', 80)->default('');   // the room type it is compared with
            $t->string('name', 120);                    // the competitor; "Our rate on sale" for our own row
            $t->boolean('is_own')->default(false);
            $t->string('link', 500)->nullable();
            $t->boolean('active')->default(true);       // removed competitors are kept, switched off
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->index(['property', 'active']);
        });

        Schema::create('market_rates', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('competitor_id');
            $t->date('stay_date');                      // the night the price is for
            $t->decimal('rate', 10, 2);                 // price for that night, RM
            $t->date('checked_on');                     // the day it was looked up
            $t->unsignedBigInteger('entered_by')->nullable();
            $t->timestamps();
            $t->index(['competitor_id', 'stay_date']);
            $t->index('stay_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('market_rates');
        Schema::dropIfExists('market_competitors');
    }
};
