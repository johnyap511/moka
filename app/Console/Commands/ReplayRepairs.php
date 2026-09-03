<?php

namespace App\Console\Commands;

use App\Booking;
use App\EzeeAssignmentLog;
use App\EzeeRoomMapping;
use App\Listing;
use App\OtherModel\EzeeBooking;
use App\Support\BookingSplitter;
use App\Support\EzeeAutoAssign;
use App\Support\EzeeUnitMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Replays the data repairs made on staging on 3 Sep 2026 so production gets
 * exactly the same treatment. Every step is a rule, not a list of row ids, so
 * it is safe to run on a database that has moved on since the staging copy
 * was taken, and safe to run twice: a row already in the repaired state is
 * skipped. --dry-run prints every row the command would change and writes
 * nothing. Every write is appended to storage/logs/repair-replay-<date>.csv.
 *
 * Steps, in order:
 *   price       month-split segments stamped with the whole stay's room total
 *               (Repair A) and auto-assigned stays held as one row across a
 *               month boundary (Repair B)
 *   sst         8% SST on tenancy rows keyed without it since Nov 2025; the two
 *               tenancies keyed tax-inclusive keep their total with SST inside
 *   extra-rooms the "Extra Room" units in EZEE become company listings under
 *               the owner given by --extra-room-owner, wired to their mappings
 *   mappings    AL-, C1/C2-, G-/F- units booked under a second EZEE hotel get a
 *               mapping there, unless EZEE's own inventory does not list them
 *   decisions   the per-reservation decisions the business gave from the EZEE
 *               calendar (cancel, unit, room history), applied only when the
 *               row is still in the state those decisions were made against
 *   reconcile   ezee:auto-assign from --from, which links, month-splits and
 *               marks overwritten rows
 */
class ReplayRepairs extends Command
{
    protected $signature = 'moka:replay-repairs
                            {--dry-run : print what would change, write nothing}
                            {--step=all : all, or a comma list of price,sst,extra-rooms,mappings,decisions,reconcile}
                            {--from=2026-08-01 : earliest check-out for the reconcile step}
                            {--extra-room-owner= : user id that owns the extra-room listings (required for extra-rooms)}';

    protected $description = 'Replay the 3 Sep 2026 staging data repairs, rule by rule, with a dry run';

    private bool $dry = false;
    private $log = null;
    private int $writes = 0;

    // Tenancies staff keyed with SST already inside the amount, folio => listing name.
    private const SST_INCLUSIVE = ['FN29630' => 'EkoCheras H-10-01', 'FN31268' => 'EkoCheras H-23-21'];

    // Decisions given by the business from the EZEE calendar on 3 Sep 2026.
    //   cancel                       reservation cancelled in EZEE; cancel our booking
    //   assign  <unit>               whole stay on that unit
    //   split   <final> <from> <to> <other>   stay reported on <final>; nights from..to were in <other>
    //   no-unit                      EZEE row is not a stay of its own; leave it unassigned
    //   accept-dates                 our booking should carry EZEE's dates
    //   unlink-if-later              row points at a booking that starts after the row; unlink it
    private const DECISIONS = [
        ['20320', 'RES4179', 'cancel'],
        ['20320', 'RES4120', 'cancel'],
        ['20317', 'RES21851', 'cancel'],
        ['20317', 'RES21915', 'cancel'],
        ['20319', 'RES4130', 'cancel'],
        ['20320', 'RES4186', 'assign', 'Alinea 12-13'],
        ['20320', 'RES4137', 'assign', 'Alinea 12-10'],
        ['20317', 'RES21956', 'assign', 'Bell Suites 01-28'],
        ['20317', 'RES21963', 'assign', 'Bell Suites 05-03'],
        ['20318', 'RES6329', 'assign', 'Forum RS-35-11'],
        ['20318', 'RES6403', 'split', 'Forum RS-35-11', '2026-08-28', '2026-08-29', 'Forum SS-22-05'],
        ['19676', 'RES31103', 'split', 'EkoCheras H-21-15', '2026-08-30', '2026-08-31', 'EkoCheras H-23-06'],
        ['19676', 'RES31005', 'split', 'EkoCheras J-3A-07', '2026-08-23', '2026-08-24', 'EkoCheras H-05-06'],
        ['19676', 'RES30823', 'assign', 'EkoCheras J-3A-07'],
        ['19676', 'RES31013', 'assign', 'EkoCheras Extra Room 2'],
        ['20320', 'RES3858', 'split', 'Alinea 14-20', '2026-07-15', '2026-07-16', 'Alinea Extra Room 1'],
        ['19676', 'RES30941', 'split', 'EkoCheras H-17-13', '2026-08-12', '2026-08-13', 'EkoCheras H-13A-3A'],
        ['19676', 'RES30451', 'no-unit'],
        ['20320', 'RES3983', 'unlink-if-later'],
        ['19676', 'RES30960-1', 'accept-dates'],
        ['19676', 'RES30960-2', 'accept-dates'],
        ['19676', 'RES30960-3', 'accept-dates'],
        ['19676', 'RES29951', 'accept-dates'],
        ['20320', 'RES3413', 'accept-dates'],
    ];

    public function handle()
    {
        set_time_limit(0);
        $this->dry = (bool) $this->option('dry-run');
        $steps = $this->option('step') === 'all'
            ? ['price', 'sst', 'extra-rooms', 'mappings', 'decisions', 'reconcile']
            : array_map('trim', explode(',', $this->option('step')));

        $path = storage_path('logs/repair-replay-' . date('Y-m-d') . '.csv');
        if (!$this->dry) {
            $new = !file_exists($path);
            $this->log = fopen($path, 'a');
            if ($new) {
                fputcsv($this->log, ['step', 'table', 'id', 'field', 'old', 'new', 'note', 'at']);
            }
        }

        $this->info(($this->dry ? 'DRY RUN. ' : '') . 'Steps: ' . implode(', ', $steps));
        $this->revenueTable('before');

        foreach ($steps as $step) {
            $this->line('');
            $this->info("== $step");
            match ($step) {
                'price'       => $this->price(),
                'sst'         => $this->sst(),
                'extra-rooms' => $this->extraRooms(),
                'mappings'    => $this->mappings(),
                'decisions'   => $this->decisions(),
                'reconcile'   => $this->reconcile(),
                default       => $this->error("unknown step $step"),
            };
        }

        $this->line('');
        $this->revenueTable('after');
        $this->checks();
        if ($this->log) {
            fclose($this->log);
            $this->info("{$this->writes} write(s); audit file $path");
        } else {
            $this->comment('Dry run: nothing was written.');
        }

        return 0;
    }

    // ---- price ------------------------------------------------------------

    private function price(): void
    {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS repair_bug');
        DB::statement("CREATE TEMPORARY TABLE repair_bug AS
            SELECT b.id, b.price, b.ota_fee, b.nights, g.tot_nights,
                   ROUND(b.price_night*g.tot_nights + IFNULL(b.sst,0)+IFNULL(b.cleaning_fee,0)+IFNULL(b.sst_cf,0)-IFNULL(b.discount_fee,0),2) buggy,
                   ROUND(b.price_night*b.nights     + IFNULL(b.sst,0)+IFNULL(b.cleaning_fee,0)+IFNULL(b.sst_cf,0)-IFNULL(b.discount_fee,0),2) fixed
            FROM bookings b
            JOIN (SELECT folio_no, listing_id, SUM(nights) tot_nights FROM bookings WHERE status<>1 AND folio_no<>'' GROUP BY 1,2 HAVING COUNT(*)>1) g
              ON g.folio_no=b.folio_no AND g.listing_id=b.listing_id
            WHERE b.status<>1 AND b.price_night>0");
        DB::statement('DELETE FROM repair_bug WHERE ABS(price - buggy) > 1 OR ABS(price - fixed) <= 1');
        $rows = DB::select('SELECT * FROM repair_bug ORDER BY id');

        $before = 0; $after = 0;
        foreach ($rows as $r) {
            $fee = round($r->ota_fee * $r->nights / max(1, $r->tot_nights), 2);
            $before += $r->price; $after += $r->fixed;
            if ($this->output->isVerbose()) {
                $this->line(sprintf('  A #%d %s nights of %s: RM %s -> %s, fee %s -> %s', $r->id, $r->nights, $r->tot_nights, $r->price, $r->fixed, $r->ota_fee, $fee));
            }
            $this->write('A-segment-price', 'bookings', $r->id, 'price', $r->price, $r->fixed, "fee {$r->ota_fee}->{$fee}",
                fn () => DB::table('bookings')->where('id', $r->id)->update(['price' => $r->fixed, 'ota_fee' => $fee, 'updated_at' => now()]));
        }
        $this->line(sprintf('  A: %d segment(s) priced at the whole stay: RM %s -> RM %s', count($rows), number_format($before), number_format($after)));

        $ids = DB::table('bookings')->where('status', '<>', 1)->where('remark', 'like', 'Auto-assigned from EZEE%')
            ->whereRaw("DATE_FORMAT(check_in,'%Y-%m') <> DATE_FORMAT(DATE_SUB(check_out, INTERVAL 1 DAY),'%Y-%m')")
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('bookings as s')->whereColumn('s.folio_no', 'bookings.folio_no')
                ->whereColumn('s.listing_id', 'bookings.listing_id')->whereColumn('s.id', '<>', 'bookings.id')->where('s.status', '<>', 1)->where('s.folio_no', '<>', ''))
            ->orderBy('check_in')->pluck('id');
        $n = 0;
        foreach ($ids as $id) {
            $b = Booking::find($id);
            if ($this->output->isVerbose() || $this->dry) {
                $this->line(sprintf('  B #%d %s..%s RM %s: split by calendar month', $b->id, $b->check_in, $b->check_out, $b->price));
            }
            if (!$this->dry) {
                $segs = (new BookingSplitter)->splitByMonth($b, null);
                if (count($segs) > 1) {
                    $n++;
                    $this->audit('B-month-split', 'bookings', $id, 'check_out', $b->check_out, $segs[0]->check_out, 'segments ' . implode('|', array_map(fn ($s) => $s->id, $segs)));
                }
            }
        }
        $this->line(sprintf('  B: %d auto-assigned stay(s) across a month boundary%s', count($ids), $this->dry ? ' would be split' : ", $n split"));
    }

    // ---- sst --------------------------------------------------------------

    private function sst(): void
    {
        $rows = DB::select("SELECT id, folio_no, listing_id, price, price_night, nights, sst, cleaning_fee, sst_cf, discount_fee FROM bookings
            WHERE status<>1 AND source IN ('Long Term Rental','Monthly Rental') AND IFNULL(sst,0)=0 AND price_night>0 AND check_in>='2025-11-01' ORDER BY id");
        $added = 0; $n = 0; $inc = 0;
        foreach ($rows as $r) {
            $inclusive = isset(self::SST_INCLUSIVE[$r->folio_no])
                && strcasecmp((string) DB::table('listings')->where('id', $r->listing_id)->value('name'), self::SST_INCLUSIVE[$r->folio_no]) === 0;
            if ($inclusive) {
                $sst = round($r->price * 8 / 108, 2);
                $rate = round(($r->price - $sst - $r->cleaning_fee - $r->sst_cf + $r->discount_fee) / max(1, $r->nights), 2);
                $inc++;
                $this->write('H-sst-inclusive', 'bookings', $r->id, 'sst', $r->sst, $sst, "keyed tax-inclusive; rate {$r->price_night}->{$rate}, total unchanged",
                    fn () => DB::table('bookings')->where('id', $r->id)->update(['sst' => $sst, 'tourism_tax' => $sst, 'price_night' => $rate, 'updated_at' => now()]));
                continue;
            }
            $sst = round($r->price_night * $r->nights * 0.08, 2);
            $new = round($r->price + $sst, 2);
            $added += $sst; $n++;
            if ($this->output->isVerbose()) {
                $this->line(sprintf('  C #%d FN%s RM %s -> %s (sst %s)', $r->id, $r->folio_no, $r->price, $new, $sst));
            }
            $this->write('C-ltr-sst', 'bookings', $r->id, 'price', $r->price, $new, "sst $sst added on top",
                fn () => DB::table('bookings')->where('id', $r->id)->update(['sst' => $sst, 'tourism_tax' => $sst, 'price' => $new, 'updated_at' => now()]));
        }
        $this->line(sprintf('  C: SST on %d tenancy row(s), RM %s added; %d tax-inclusive row(s) re-expressed', $n, number_format($added, 2), $inc));
    }

    // ---- extra rooms ------------------------------------------------------

    private function extraRooms(): void
    {
        $owner = (int) $this->option('extra-room-owner');
        if (!$owner || !DB::table('users')->where('id', $owner)->exists()) {
            $this->error('  --extra-room-owner=<user id> is required and must exist (the company account that owns extra-guest rooms). Step skipped.');
            return;
        }
        $props = [];
        foreach (DB::table('ezee_groups')->get() as $g) {
            $props[$g->id] = match ((string) $g->hotel_code) {
                '19676' => 'EkoCheras', '20317' => 'Bell Suites', '20318' => 'Forum', '20319' => 'Arte Cheras', '20320' => 'Alinea', default => null,
            };
        }
        $made = 0; $wired = 0;
        foreach (DB::select("SELECT id, room_name, ezee_group_id, listing_id, archived_at FROM ezee_room_mappings WHERE room_name LIKE '%Extra%' AND ezee_group_id IS NOT NULL ORDER BY ezee_group_id, room_name") as $m) {
            $prefix = $props[$m->ezee_group_id] ?? null;
            if (!$prefix) {
                $this->warn("  mapping #{$m->id} {$m->room_name}: group {$m->ezee_group_id} is not one of the five hotels, skipped");
                continue;
            }
            $name = "$prefix Extra Room " . (int) preg_replace('/\D/', '', $m->room_name);
            $tpl = DB::table('listings')->whereRaw('LOWER(name) LIKE ?', [strtolower($prefix) . ' %'])->whereNotNull('address')->first();
            $id = DB::table('listings')->where('name', $name)->value('id');
            if (!$id) {
                $made++;
                $this->line("  create listing \"$name\" under user $owner");
                if (!$this->dry) {
                    $id = DB::table('listings')->insertGetId(['user_id' => $owner, 'name' => $name, 'title' => $name, 'address' => $tpl->address ?? '', 'agent_code' => $tpl->agent_code ?? '', 'type' => 'individual',
                        'default_price' => 0, 'cleaning_fee' => 0, 'profit' => 100, 'status' => 1, 'tourism_tax_type' => 'fixed', 'tourism_tax_amount' => 0, 'key' => sha1($name . microtime()),
                        'water' => $tpl->water ?? 'A', 'internet' => $tpl->internet ?? 'A', 'electricity' => $tpl->electricity ?? 'A', 'mfsf' => $tpl->mfsf ?? 'C', 'created_at' => now(), 'updated_at' => now()]);
                    $this->audit('D-extra-room', 'listings', $id, 'name', '', $name, "owner $owner");
                }
            }
            if ((int) $m->listing_id !== (int) $id || $m->archived_at) {
                $wired++;
                $this->write('D-extra-room', 'ezee_room_mappings', $m->id, 'listing_id', $m->listing_id, $id ?? '(new)', "$m->room_name -> $name, unarchived",
                    fn () => DB::table('ezee_room_mappings')->where('id', $m->id)->update(['listing_id' => $id, 'archived_at' => null, 'updated_at' => now()]));
            }
        }
        $loose = DB::table('ezee_room_mappings')->where('room_name', 'like', '%Extra%')->whereNull('ezee_group_id')->whereNull('archived_at')->pluck('id');
        foreach ($loose as $lid) {
            $this->write('D-extra-room', 'ezee_room_mappings', $lid, 'archived_at', '', 'now', 'group-less Extra mapping would make the name fallback ambiguous',
                fn () => DB::table('ezee_room_mappings')->where('id', $lid)->update(['archived_at' => now(), 'updated_at' => now()]));
        }
        $this->line("  D: $made listing(s) to create, $wired mapping(s) to wire, " . count($loose) . ' group-less mapping(s) to archive');
    }

    // ---- mappings ---------------------------------------------------------

    private function mappings(): void
    {
        $map = EzeeUnitMap::make();
        $owners = \App\EzeeRoom::get(['room_name', 'hotel_code'])->groupBy(fn ($r) => EzeeUnitMap::key($r->room_name))
            ->map(fn ($rs) => $rs->pluck('hotel_code')->map(fn ($c) => (string) $c)->unique()->values());
        $groupByCode = \App\EzeeGroup::pluck('id', 'hotel_code');
        $plan = [];
        foreach (EzeeBooking::whereNull('book_id')->where('status', '<>', 1)->where('Start', '>=', '2026-07-01')->get() as $b) {
            if (!trim((string) $b->RoomName) || $map->resolve($b)) {
                continue;
            }
            $unit = EzeeUnitMap::key($b->RoomName);
            $own = $owners->get($unit); $code = $map->hotelCodeFor($b);
            if (!$own || $own->isEmpty() || $code === null || $own->contains($code)) {
                continue;
            }
            if (!preg_match('/^(AL|C1|C2|G|F)-/i', trim($b->RoomName))) {
                $this->line("  skip (prefix not confirmed by the business): {$b->RoomName} under $code");
                continue;
            }
            // EZEE's own inventory decides: a unit not listed under this hotel is
            // an overwritten room name, not a unit that moved hotels.
            $inInventory = DB::table('ezee_rooms')->where('hotel_code', $code)->whereRaw('LOWER(TRIM(room_name)) = ?', [$unit])->exists();
            if (!$inInventory) {
                $this->line("  skip (EZEE does not list {$b->RoomName} under $code)");
                continue;
            }
            $src = EzeeRoomMapping::whereNull('archived_at')->whereNotNull('listing_id')->whereRaw('LOWER(TRIM(room_name)) = ?', [$unit])->first();
            $gid = $groupByCode[$code] ?? null;
            if (!$src || !$gid) {
                $this->line("  skip (no source mapping or group): {$b->RoomName} under $code");
                continue;
            }
            $plan[$gid . '|' . $unit] = ['gid' => $gid, 'code' => $code, 'room' => trim($b->RoomName), 'type' => $src->room_type_name, 'listing' => $src->listing_id];
        }
        $n = 0;
        foreach ($plan as $p) {
            if (EzeeRoomMapping::where('ezee_group_id', $p['gid'])->whereRaw('LOWER(TRIM(room_name)) = ?', [EzeeUnitMap::key($p['room'])])->exists()) {
                continue;
            }
            $n++;
            $lname = Listing::withoutGlobalScope('notArchived')->find($p['listing'])->name ?? '?';
            $this->write('E-mapping', 'ezee_room_mappings', 'new', 'room_name', '', $p['room'], "hotel {$p['code']} -> listing #{$p['listing']} $lname",
                fn () => EzeeRoomMapping::create(['ezee_group_id' => $p['gid'], 'room_name' => $p['room'], 'room_type_name' => $p['type'], 'listing_id' => $p['listing']]));
        }
        $this->line("  E: $n mapping(s) to create");
    }

    // ---- decisions --------------------------------------------------------

    private function decisions(): void
    {
        $aa = new EzeeAutoAssign($this->dry, null);
        foreach (self::DECISIONS as $d) {
            [$hotel, $res, $action] = $d;
            $rows = EzeeBooking::where('TransactionId', 'like', $hotel . '%')->where('SubBookingId', $res)->orderByDesc('id')->get();
            $tag = sprintf('  %-11s@%s %-14s', $res, $hotel, $action);
            if ($rows->isEmpty()) {
                $this->warn("$tag no EZEE row here; skipped");
                continue;
            }
            $e = $rows->first();
            $state = sprintf('row #%d st%d %s %s..%s ptr=%s', $e->id, $e->status, $e->RoomName, substr($e->Start, 0, 10), substr($e->End, 0, 10), $e->book_id ?: '-');
            $booking = $e->book_id ? Booking::find($e->book_id) : null;

            try {
                switch ($action) {
                    case 'cancel':
                        if ((int) $e->status === 1 && (!$booking || (int) $booking->status === 1)) {
                            $this->line("$tag already cancelled ($state)");
                            break;
                        }
                        $this->line("$tag CANCEL row" . ($booking && (int) $booking->status !== 1 ? " and booking #{$booking->id} ({$booking->check_in}..{$booking->check_out} RM {$booking->price})" : '') . " | $state");
                        $this->write('F-decision', 'ezee_bookings', $e->id, 'status', $e->status, 1, "$res cancelled in EZEE (business decision 3 Sep 2026)", function () use ($e, $booking, $res) {
                            EzeeBooking::where('id', $e->id)->update(['status' => 1]);
                            if ($booking && (int) $booking->status !== 1) {
                                DB::table('bookings')->where('id', $booking->id)->update(['status' => 1, 'updated_at' => now(),
                                    'remark' => DB::raw("LEFT(CONCAT(IFNULL(remark,''), ' | cancelled in EZEE (confirmed from the EZEE calendar, 3 Sep 2026)'), 255)")]);
                                $this->audit('F-decision', 'bookings', $booking->id, 'status', $booking->status, 1, "$res cancelled");
                            }
                        });
                        break;

                    case 'assign':
                        $unit = $this->listing($d[3]);
                        if ($booking && (int) $booking->status !== 1) {
                            $on = DB::table('listings')->where('id', $booking->listing_id)->value('name');
                            $this->line("$tag already on $on #{$booking->id} {$booking->check_in}..{$booking->check_out}" . (strcasecmp($on, $unit->name) ? '  <-- NOT the decided unit, left for staff' : ''));
                            break;
                        }
                        $this->line("$tag ASSIGN to {$unit->name} | $state");
                        $this->write('F-decision', 'ezee_bookings', $e->id, 'book_id', $e->book_id, "new on {$unit->name}", "$res assigned per EZEE calendar",
                            fn () => $aa->assignTo($this->fresh($e), $unit));
                        break;

                    case 'split':
                        [$final, $from, $to, $other] = [$this->listing($d[3]), $d[4], $d[5], $this->listing($d[6])];
                        if ($booking && (int) $booking->status !== 1) {
                            $chain = (new BookingSplitter)->stayChain($booking, $hotel);
                            $this->line("$tag already assigned: " . implode('; ', array_map(fn ($s) => sprintf('#%d %s %s..%s', $s->id, DB::table('listings')->where('id', $s->listing_id)->value('name'), $s->check_in, $s->check_out), $chain)));
                            break;
                        }
                        $this->line("$tag ASSIGN to {$final->name}, $from..$to in {$other->name} | $state");
                        $this->write('F-decision', 'ezee_bookings', $e->id, 'book_id', $e->book_id, "new on {$final->name} + {$other->name}", "$res room history per EZEE calendar",
                            fn () => $aa->assignSplit($this->fresh($e), $final, $from, $to, $other->id));
                        break;

                    case 'no-unit':
                        if ((int) $e->status === EzeeAutoAssign::NO_UNIT) {
                            $this->line("$tag already marked ($state)");
                            break;
                        }
                        $this->line("$tag MARK no-unit (its pointer, if any, is left on the real stay) | $state");
                        $this->write('F-decision', 'ezee_bookings', $e->id, 'status', $e->status, EzeeAutoAssign::NO_UNIT, "$res is not a stay of its own",
                            fn () => EzeeBooking::where('id', $e->id)->update(['status' => EzeeAutoAssign::NO_UNIT]));
                        break;

                    case 'unlink-if-later':
                        if ($booking && $booking->check_in >= substr($e->End, 0, 10)) {
                            $this->line("$tag UNLINK: points at #{$booking->id} {$booking->check_in}..{$booking->check_out}, after the stay | $state");
                            $this->write('F-decision', 'ezee_bookings', $e->id, 'book_id', $e->book_id, null, "$res pointed at a later stay",
                                fn () => EzeeBooking::where('id', $e->id)->update(['book_id' => null, 'status' => 5]));
                        } else {
                            $this->line("$tag fine ($state)");
                        }
                        break;

                    case 'accept-dates':
                        if (!$booking || (int) $booking->status === 1) {
                            $this->line("$tag not linked to a live booking; the reconcile will place it ($state)");
                            break;
                        }
                        $chain = (new BookingSplitter)->stayChain($booking, $hotel);
                        $first = $chain[0]->check_in; $last = end($chain)->check_out;
                        if ($first === substr($e->Start, 0, 10) && $last === substr($e->End, 0, 10)) {
                            $this->line("$tag already $first..$last");
                            break;
                        }
                        $this->line("$tag RETIME $first..$last -> " . substr($e->Start, 0, 10) . '..' . substr($e->End, 0, 10) . " | $state");
                        $this->write('F-decision', 'bookings', $booking->id, 'dates', "$first..$last", substr($e->Start, 0, 10) . '..' . substr($e->End, 0, 10), "$res EZEE dates accepted",
                            fn () => $aa->acceptEzeeDates($this->fresh($e)));
                        break;
                }
            } catch (\Throwable $t) {
                $this->error("$tag FAILED: " . substr($t->getMessage(), 0, 160) . " | $state");
            }
        }
    }

    private function listing(string $name): Listing
    {
        $l = Listing::withoutGlobalScope('notArchived')->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if (!$l) {
            throw new \RuntimeException("listing \"$name\" does not exist here");
        }
        return $l;
    }

    private function fresh(EzeeBooking $e): EzeeBooking
    {
        return EzeeBooking::findOrFail($e->id);
    }

    // ---- reconcile --------------------------------------------------------

    private function reconcile(): void
    {
        $args = ['--from' => $this->option('from')];
        if ($this->dry) {
            $args['--dry-run'] = true;
        }
        Artisan::call('ezee:auto-assign', $args, $this->output);
    }

    // ---- shared -----------------------------------------------------------

    private function write(string $step, string $table, $id, string $field, $old, $new, string $note, \Closure $do): void
    {
        if ($this->dry) {
            return;
        }
        $do();
        $this->audit($step, $table, $id, $field, $old, $new, $note);
    }

    private function audit(string $step, string $table, $id, string $field, $old, $new, string $note): void
    {
        $this->writes++;
        if ($this->log) {
            fputcsv($this->log, [$step, $table, $id, $field, $old, $new, $note, now()->toDateTimeString()]);
        }
    }

    private function revenueTable(string $label): void
    {
        $rows = [];
        foreach (DB::select("SELECT DATE_FORMAT(check_in,'%Y-%m') m, COUNT(*) n, ROUND(SUM(price)) rm FROM bookings WHERE status<>1 AND check_in>='2026-06-01' AND check_in<'2026-11-01' GROUP BY 1 ORDER BY 1") as $r) {
            $rows[] = [$r->m, $r->n, number_format($r->rm)];
        }
        $this->line("Revenue by check-in month ($label):");
        $this->table(['month', 'bookings', 'RM'], $rows);
    }

    private function checks(): void
    {
        $fix = '(b.price_night*b.nights + IFNULL(b.sst,0)+IFNULL(b.cleaning_fee,0)+IFNULL(b.sst_cf,0)-IFNULL(b.discount_fee,0))';
        $this->line('Checks: segments still priced at the whole stay ' . DB::selectOne("SELECT COUNT(*) n FROM bookings b JOIN (SELECT folio_no, listing_id, SUM(nights) tn FROM bookings WHERE status<>1 AND folio_no<>'' GROUP BY 1,2 HAVING COUNT(*)>1) g ON g.folio_no=b.folio_no AND g.listing_id=b.listing_id WHERE b.status<>1 AND b.price_night>0 AND ABS(b.price - (b.price_night*g.tn + IFNULL(b.sst,0)+IFNULL(b.cleaning_fee,0)+IFNULL(b.sst_cf,0)-IFNULL(b.discount_fee,0))) <= 1 AND ABS(b.price - $fix) > 1")->n
            . ' | unsplit auto rows ' . DB::selectOne("SELECT COUNT(*) n FROM bookings WHERE status<>1 AND remark LIKE 'Auto-assigned from EZEE%' AND DATE_FORMAT(check_in,'%Y-%m') <> DATE_FORMAT(DATE_SUB(check_out, INTERVAL 1 DAY),'%Y-%m')")->n
            . ' | tenancies without SST ' . DB::selectOne("SELECT COUNT(*) n FROM bookings WHERE status<>1 AND source IN ('Long Term Rental','Monthly Rental') AND IFNULL(sst,0)=0 AND price_night>0 AND check_in>='2025-11-01'")->n
            . ' | overlaps Jul-Oct ' . DB::selectOne("SELECT COUNT(*) n FROM bookings a JOIN bookings b ON b.listing_id=a.listing_id AND b.id>a.id AND b.status<>1 AND b.check_in<a.check_out AND b.check_out>a.check_in WHERE a.status<>1 AND a.check_out>'2026-07-01' AND a.check_in<'2026-11-01'")->n
            . ' | double pointers ' . DB::selectOne("SELECT COUNT(*) n FROM bookings b WHERE b.status<>1 AND b.check_out>'2026-07-01' AND (SELECT COUNT(*) FROM ezee_bookings e WHERE e.book_id=b.id AND e.status<>1)>1")->n
            . ' | open review ' . DB::table('ezee_assignment_logs')->where('method', 'conflict')->whereNull('resolved_at')->count());
    }
}
