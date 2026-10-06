<?php

namespace App\Console\Commands;

use App\Support\SalesCommission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SalesReflag extends Command
{
    protected $signature = 'sales:reflag';
    protected $description = 'Recompute the payable flag of every stored sales transaction after a rule change';

    public function handle(): int
    {
        $on = $off = 0;
        DB::table('sales_transactions')->orderBy('id')->lazyById(1000)->each(function ($row) use (&$on, &$off) {
            $now = SalesCommission::isPayable((array) $row) ? 1 : 0;
            if ((int) $row->payable !== $now) {
                DB::table('sales_transactions')->where('id', $row->id)->update(['payable' => $now]);
                $now ? $on++ : $off++;
            }
        });
        $this->line("{$on} row(s) became payable, {$off} row(s) became non-payable.");
        return 0;
    }
}
