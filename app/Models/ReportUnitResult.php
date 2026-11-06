<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One unit's line of a finished owner-report run: payable, Moka net profit, and the report file in the zip. */
class ReportUnitResult extends Model
{
    public $timestamps = false;

    protected $fillable = ['run_id', 'month', 'unit', 'listing_id', 'owner_id', 'category', 'payable', 'moka_np', 'pdf_path', 'xlsx_path', 'captured_at'];

    protected $casts = ['payable' => 'float', 'moka_np' => 'float', 'captured_at' => 'datetime'];

    public function run()
    {
        return $this->belongsTo(ReportRun::class, 'run_id');
    }

    public function listing()
    {
        return $this->belongsTo(\App\Listing::class, 'listing_id')->withoutGlobalScopes();
    }

    public function owner()
    {
        return $this->belongsTo(\App\User::class, 'owner_id');
    }
}
