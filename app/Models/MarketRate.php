<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One price looked up for one night. Every check is kept, so the page can show how a rate moved. */
class MarketRate extends Model
{
    protected $fillable = ['competitor_id', 'stay_date', 'rate', 'checked_on', 'entered_by'];
}
