<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class update_excel extends Model
{
    protected $table = 'update_excels';
    protected $guarded = [];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
