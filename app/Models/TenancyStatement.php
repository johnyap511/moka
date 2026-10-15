<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * A final tenancy settlement statement: inputs, computed lines and the PDF
 * as issued. Rows are never deleted; a wrong statement is voided and a new
 * one issued (Sam, 15 Oct 2026).
 */
class TenancyStatement extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'input'     => 'array',
        'lines'     => 'array',
        'stay_from' => 'date',
        'stay_to'   => 'date',
        'inclusive' => 'boolean',
        'voided_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voider()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isVoid(): bool
    {
        return $this->voided_at !== null;
    }

    /** Refund due (positive) or still owed (negative); null when no deposit was keyed in. */
    public function verdict(): ?string
    {
        if ($this->balance === null) {
            return null;
        }
        return (float) $this->balance >= 0 ? 'refund' : 'owed';
    }
}
