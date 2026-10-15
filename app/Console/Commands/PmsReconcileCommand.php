<?php

namespace App\Console\Commands;

use App\Support\PmsReconcile;
use Illuminate\Console\Command;

/** Compare eZee with Homemoka and keep the differences (daily 06:30; --month=YYYY-MM at month-end). */
class PmsReconcileCommand extends Command
{
    protected $signature = 'pms:reconcile {--days=60 : stays touching the last N days} {--month= : check one month instead (YYYY-MM)}';
    protected $description = 'Compare eZee reservations with Homemoka bookings and record the differences';

    public function handle(): int
    {
        $month = (string) $this->option('month');
        $run = $month !== '' ? PmsReconcile::month($month) : PmsReconcile::daily((int) $this->option('days'));
        $this->info(sprintf('%s %s → %s: %d reservations, %d bookings, %d new, %d cleared, %d open (%d ms)',
            $run->scope, $run->from->toDateString(), $run->to->toDateString(), $run->reservations, $run->bookings, $run->new, $run->cleared, $run->open, $run->duration_ms));
        return self::SUCCESS;
    }
}
