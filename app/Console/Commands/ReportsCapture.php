<?php

namespace App\Console\Commands;

use App\Models\ReportRun;
use App\Support\OwnerReports;
use Illuminate\Console\Command;

/**
 * Capture (or re-capture) the per-unit results of finished owner-report runs into
 * report_unit_results. Runs automatically when a run finishes; this is for backfilling
 * the packs made before 6 Nov 2026 and for re-reading one after a fix.
 */
class ReportsCapture extends Command
{
    protected $signature = 'reports:capture {--id= : one report_runs id} {--all : every finished run}';
    protected $description = 'Store each finished report pack\'s per-unit payable and Moka net profit in report_unit_results';

    public function handle(): int
    {
        $runs = $this->option('id')
            ? ReportRun::where('id', (int) $this->option('id'))->get()
            : ($this->option('all') ? ReportRun::where('status', 'done')->orderBy('month')->get() : collect());
        if ($runs->isEmpty()) {
            $this->warn('Nothing to do: pass --id=<run> or --all.');
            return self::SUCCESS;
        }
        foreach ($runs as $run) {
            try {
                $n = OwnerReports::captureResults($run);
                $this->info(sprintf('Run #%d (%s): %d unit rows.', $run->id, $run->month, $n));
            } catch (\Throwable $e) {
                $this->error(sprintf('Run #%d (%s): %s', $run->id, $run->month, $e->getMessage()));
            }
        }
        return self::SUCCESS;
    }
}
