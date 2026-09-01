<?php

namespace App\Console\Commands;

use App\Support\EzeeAutoAssign;
use Illuminate\Console\Command;

class EzeeAutoAssignCommand extends Command
{
    protected $signature = 'ezee:auto-assign
                            {--dry-run : report what would change without writing}
                            {--from= : earliest check-out to consider (default: today)}
                            {--close-stale : close conflicts that no longer apply, without assigning anything}';

    protected $description = 'Assign EZEE bookings to listings from the room mapping, and follow room moves';

    public function handle()
    {
        set_time_limit(0);

        $dryRun = (bool) $this->option('dry-run');

        if (!$dryRun && !config('ezee.auto_assign')) {
            $this->warn('Automatic assignment is off (EZEE_AUTO_ASSIGN). Running as a dry run instead.');
            $dryRun = true;
        }

        $closeStale = (bool) $this->option('close-stale');

        if ($closeStale) {
            $this->info('Closing conflicts that no longer apply. No bookings will be assigned.');
        }

        $result = (new EzeeAutoAssign($dryRun, null, $closeStale))->reconcile($this->option('from'));

        if ($result['message']) {
            $this->warn($result['message']);
        }

        $this->table(
            ['Assigned', 'Adopted', 'Moved', 'Conflicts', 'Unmapped room', 'Already correct', 'Failed'],
            [[$result['assigned'], $result['adopted'], $result['moved'], $result['conflicts'], $result['unmapped'], $result['unchanged'], $result['failed']]]
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
            $this->comment($closeStale
                ? "Dry run for assignment; {$result['resolved']} stale conflict(s) closed."
                : 'Dry run — nothing was written.');
        }

        return 0;
    }
}
