<?php

namespace App\Console\Commands;

use App\Support\SalesCommission;
use Illuminate\Console\Command;

class SalesImport extends Command
{
    protected $signature = 'sales:import {file : eZee Transaction Detail Report (.xlsx)} {--hotel= : property code, otherwise detected from the reservations}';
    protected $description = 'Store one eZee Transaction Detail Report for the sales commission page';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (!is_file($path)) {
            $this->error("No such file: $path");

            return 1;
        }
        try {
            $u = SalesCommission::import($path, basename($path), $this->option('hotel') ?: null, null);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return 1;
        }
        $this->info(sprintf('%s: %s to %s, %d rows in file, %d stored with a sales person, %d in reported months skipped, %d earlier rows replaced.',
            SalesCommission::HOTELS[$u->hotel_code], $u->period_from, $u->period_to, $u->rows_in_file, $u->rows_stored, $u->rows_skipped_locked, $u->rows_replaced));

        return 0;
    }
}
