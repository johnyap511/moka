<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A guest-facing refund form link. Valid for 48 hours; see App\Support\Refunds. */
class RefundLink extends Model
{
    protected $fillable = ['token', 'expires_at', 'created_by'];

    protected $casts = ['expires_at' => 'datetime'];

    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }

    public function url(): string
    {
        return url('/refund/' . $this->token);
    }
}
