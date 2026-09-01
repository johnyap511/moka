<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class update_excel extends Model
{
    protected $table = 'update_excels';
    protected $guarded = [];

    public function listing()
    {
        // withoutGlobalScope: an archived property disappears from lists and
        // pickers, but a record already pointing at one must still resolve it —
        // history should stay readable after a property is handed back.
        return $this->belongsTo(Listing::class)->withoutGlobalScope('notArchived');
    }
}
