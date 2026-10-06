<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportRun extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sheets' => 'array', 'prev_sheets' => 'array', 'overrides' => 'array', 'flags' => 'array',
        'started_at' => 'datetime', 'finished_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dir(): string
    {
        return storage_path('app/reports/' . $this->id);
    }

    public function file(string $name): string
    {
        return $this->dir() . '/' . $name;
    }

    public function monthLabel(): string
    {
        return \Carbon\Carbon::parse($this->month . '-01')->format('F Y');
    }
}
