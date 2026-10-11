<?php

namespace App\Console\Commands;

use App\DataLog;
use App\Support\Refunds;
use Illuminate\Console\Command;

/**
 * Issue the day's deposit refund link. Scheduled 09:00 Kuala Lumpur, when the
 * front desk starts sending check-out messages (Sam, 12 Oct 2026). Each link is
 * valid until 09:00 two days later, so yesterday's message still works today.
 */
class RefundsIssueLink extends Command
{
    protected $signature = 'refunds:issue-link';
    protected $description = 'Issue a fresh 48-hour deposit refund link (daily at 09:00)';

    public function handle(): int
    {
        $link = Refunds::link(null, true);
        $this->info('Refund link issued, valid until ' . $link->expires_at->format('D d M Y H:i') . ': ' . $link->url());
        try {
            DataLog::create(['related_id' => 0, 'title' => 'Refund link issued', 'data' => json_encode(['token' => $link->token, 'expires_at' => $link->expires_at->toDateTimeString(), 'by' => 'scheduler 09:00']), 'status' => 1]);
        } catch (\Throwable $e) {
            report($e);
        }
        return self::SUCCESS;
    }
}
