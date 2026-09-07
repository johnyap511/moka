<?php

namespace App\Console\Commands;

use App\DataLog;
use App\EzeeAssignmentLog;
use App\OtherModel\EzeeBooking;
use App\Support\Lock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Daily: the same stay recorded twice (same hotel, overlapping dates, same folio)
 * in unlocked months goes to Needs Review. Nothing is changed.
 */
class FindDuplicateBookings extends Command
{
    protected $signature = 'moka:duplicates {--dry-run : list only}';
    protected $description = 'Raise same-stay duplicates for review';

    public function handle(): int
    {
        $from = Lock::cutoff()->toDateString();
        $pairs = DB::select("select a.id a_id, b.id b_id, a.folio_no, la.name a_unit, lb.name b_unit, a.check_in, a.check_out, b.check_in b_in, b.check_out b_out,
                TRIM(CONCAT(IFNULL(ua.name,''),' ',IFNULL(ua.last_name,''))) guest
            from bookings a join bookings b on b.id > a.id and b.status = 5 and b.check_in < a.check_out and b.check_out > a.check_in
            join listings la on la.id = a.listing_id join listings lb on lb.id = b.listing_id
            left join users ua on ua.id = a.user_id left join users ub on ub.id = b.user_id
            where a.status = 5 and a.check_in >= ? and LOWER(SUBSTRING_INDEX(la.name,' ',1)) = LOWER(SUBSTRING_INDEX(lb.name,' ',1))
              and a.folio_no like 'FN%' and a.folio_no = b.folio_no", [$from]);
        $raised = 0;
        foreach ($pairs as $p) {
            $this->line(sprintf('#%d %s %s..%s  <->  #%d %s %s..%s  folio %s  %s', $p->a_id, $p->a_unit, $p->check_in, $p->check_out, $p->b_id, $p->b_unit, $p->b_in, $p->b_out, $p->folio_no, $p->guest));
            if ($this->option('dry-run')) continue;
            $eb = EzeeBooking::whereIn('book_id', [$p->a_id, $p->b_id])->where('status', '<>', 1)->first();
            $note = sprintf('Possible duplicate: booking #%d (%s, %s → %s) and #%d (%s, %s → %s), same hotel, %s. One stay should be recorded once: cancel the wrong one, or Mark done if both are real.',
                $p->a_id, $p->a_unit, $p->check_in, $p->check_out, $p->b_id, $p->b_unit, $p->b_in, $p->b_out, $p->folio_no ? 'folio ' . $p->folio_no : 'guest ' . $p->guest);
            if ($eb && !EzeeAssignmentLog::where('ezee_booking_id', $eb->id)->where('method', 'conflict')->whereNull('resolved_at')->where('note', 'like', 'Possible duplicate%')->exists()) {
                EzeeAssignmentLog::create(['ezee_booking_id' => $eb->id, 'listing_id' => DB::table('bookings')->where('id', $eb->book_id)->value('listing_id'), 'old_listing_id' => null, 'assigned_by' => null, 'method' => 'conflict', 'note' => $note]);
                $raised++;
            }
            DataLog::create(['related_id' => $p->a_id, 'title' => 'Duplicate check', 'status' => 'needs review', 'data' => json_encode(['bookings' => [$p->a_id, $p->b_id], 'note' => $note])]);
        }
        $this->info(count($pairs) . ' pair(s) found since ' . $from . ($this->option('dry-run') ? ' (dry run)' : ", {$raised} raised for review"));

        return 0;
    }
}
