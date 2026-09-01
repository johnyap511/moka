<?php

namespace App\Console\Commands;

use App\Support\EzeeAutoAssign;
use Illuminate\Console\Command;

class EzeeAutoAssignCommand extends Command
{
    protected $signature = 'ezee:auto-assign
                            {--dry-run : report what would change without writing}
                            {--from= : earliest check-out to consider (default: today)}';

    protected $description = 'Assign EZEE bookings to listings from the room mapping, and follow room moves';

    public function handle()
    {
        set_time_limit(0);

        $dryRun = (bool) $this->option('dry-run');

        if (!$dryRun && !config('ezee.auto_assign')) {
            $this->warn('Automatic assignment is off (EZEE_AUTO_ASSIGN). Running as a dry run instead.');
            $dryRun = true;
        }

        $result = (new EzeeAutoAssign($dryRun))->reconcile($this->option('from'));

        if ($result['message']) {
            $this->warn($result['message']);
        }

        $this->table(
            ['Assigned', 'Moved', 'Conflicts', 'Unmapped room', 'Already correct', 'Failed'],
            [[$result['assigned'], $result['moved'], $result['conflicts'], $result['unmapped'], $result['unchanged'], $result['failed']]]
        );

        foreach ($result['detail'] as $row) {
            if ($row['action'] === 'failed') {
                $this->error("  failed: {$row['room']} — {$row['error']}");
            }

            if ($row['action'] === 'conflict') {
                $this->warn("  conflict: {$row['room']} → {$row['listing']} ({$row['dates']}) blocked by booking #{$row['blocked_by']}");
            }
        }

        if ($dryRun) {
            $this->comment('Dry run — nothing was written.');
        }

        return 0;
    }
}
