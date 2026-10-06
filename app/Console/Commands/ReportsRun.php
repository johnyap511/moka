<?php

namespace App\Console\Commands;

use App\Models\ReportRun;
use App\Support\OwnerReports;
use Illuminate\Console\Command;

class ReportsRun extends Command
{
    protected $signature = 'reports:run {--id= : run a specific report_runs id}';
    protected $description = 'Send the oldest queued owner-report run to the report service and store the result';

    public function handle(): int
    {
        // A run the process died on (deploy, reboot) would otherwise sit at "running" for ever.
        ReportRun::where('status', 'running')->where('started_at', '<', now()->subMinutes(25))
            ->update(['status' => 'error', 'finished_at' => now(), 'error' => 'The run did not finish within 25 minutes. Try again.']);

        $run = $this->option('id')
            ? ReportRun::findOrFail($this->option('id'))
            : ReportRun::where('status', 'queued')->orderBy('id')->first();
        if (!$run) {
            return 0;
        }
        $this->line("Running report #{$run->id} for {$run->month} …");
        OwnerReports::run($run);
        $run->refresh();
        $this->line("#{$run->id}: {$run->status}" . ($run->error ? ' — ' . $run->error : ''));
        return $run->status === 'done' ? 0 : 1;
    }
}
