<?php

namespace App\Support;

use App\Booking;
use App\DataLog;
use App\EzeeAssignmentLog;
use App\Listing;
use App\OtherModel\EzeeBooking;
use App\Role;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Assigns EZEE bookings to listings from the room mapping, and keeps them
 * following EZEE when a guest moves unit.
 *
 * Deliberately runs as a reconcile pass over the current state rather than as a
 * reaction to each booking as it arrives. EZEE sends a room move as a changed
 * eZeePMSRoomid on an existing reservation, and within one sync the moves can
 * arrive in any order: guest A into a unit guest B has not yet been recorded as
 * leaving. Reconciling after the whole sync has landed means those orderings
 * resolve themselves, instead of being reported as conflicts that were never
 * real.
 *
 * A genuine conflict — EZEE showing two live bookings in one unit on one night —
 * is never resolved by guessing a different unit. The existing assignment is
 * left alone and the clash is logged for a person, because inventing a
 * placement would put a guest on an owner's calendar where EZEE says they are
 * not.
 */
class EzeeAutoAssign
{
    /**
     * ezee_bookings.status for a reservation a person has marked as needing
     * no unit of its own — an extra-guest room. 1 is cancelled, 5 unassigned,
     * 8 assigned.
     */
    public const NO_UNIT = 7;

    private bool $dryRun;
    private ?int $actorId;

    /** @var array<string,int> */
    private array $tally = [
        'assigned'  => 0,
        'moved'     => 0,
        'conflicts' => 0,
        'unmapped'  => 0,
        'overwritten' => 0,
        'unchanged' => 0,
        'adopted'   => 0,
        'resolved'  => 0,
        'failed'    => 0,
    ];

    /** @var array<int,array<string,mixed>> */
    private array $detail = [];

    /**
     * Bookings that have moved off their previous unit during this run. They no
     * longer occupy it, so they must not block anything moving in behind them.
     * Held in memory rather than read back from the database so a dry run
     * reports the same conflicts a real run would.
     *
     * @var array<int,true>
     */
    private array $vacated = [];

    /**
     * Reservations that conflicted during this run. Anything outstanding that is
     * not in here no longer conflicts and is closed automatically, which is what
     * keeps the queue short instead of growing forever.
     *
     * @var array<int,true>
     */
    private array $conflictedNow = [];

    /** @var array<int,true> reservations this run looked at */
    private array $examined = [];

    /**
     * Closing a conflict that no longer applies is housekeeping, not an
     * assignment, so it can be done while assignment itself is switched off.
     */
    private bool $closeStale;

    public function __construct(bool $dryRun = false, ?int $actorId = null, bool $closeStale = false)
    {
        $this->dryRun     = $dryRun;
        $this->actorId    = $actorId;
        $this->closeStale = $closeStale;
    }

    /**
     * @param  string|null  $from  earliest check-out to consider; defaults to today
     * @return array<string,mixed>
     */
    public function reconcile(?string $from = null): array
    {
        $units = EzeeUnitMap::make();

        if ($units->isEmpty()) {
            return $this->summary('No unit is mapped to a listing yet.');
        }

        $from = $from ?: date('Y-m-d');

        $candidates = EzeeBooking::whereNotNull('RoomName')
            ->where('RoomName', '!=', '')
            ->where('End', '>=', $from)
            // Skip reservations EZEE has dropped. Without this the weekly sweep
            // retires a cancelled booking and the daily assignment puts it
            // straight back, so the two scheduled jobs undo each other and a
            // cancelled guest reappears on an owner calendar.
            //
            // Also skip reservations a person has marked as needing no unit:
            // an extra-guest "room" that EZEE nevertheless reports under a real
            // unit name, which would otherwise be raised as a conflict against
            // the genuine occupant on every run.
            ->whereNotIn('status', [1, self::NO_UNIT])
            ->orderBy('Start')
            ->get();

        $pending = [];

        // Moves are applied before new assignments, and for the same reason the
        // reconcile runs after the sync: a unit is often only free because the
        // guest in it is moving out in this same run. Assigning first would see
        // the outgoing booking still in place and report a conflict that
        // resolves itself moments later.
        foreach ($candidates as $ezeeBooking) {
            $listing = $units->resolve($ezeeBooking);

            $this->examined[$ezeeBooking->id] = true;

            if (!$listing) {
                $this->tally['unmapped']++;
                continue;
            }

            if ($this->overwrittenByOtherHotel($ezeeBooking)) {
                $this->tally['overwritten']++;
                continue;
            }

            $booking = $ezeeBooking->book_id ? Booking::find($ezeeBooking->book_id) : null;

            if (!$booking) {
                $pending[] = [$ezeeBooking, $listing];
                continue;
            }

            // A reservation whose booking has been cancelled on our side is not
            // assigned, whatever the pointer says. It is neither recreated (the
            // cancellation may have been deliberate) nor counted as correct
            // (76 such rows silently dropped RM53k of September revenue once).
            if ((int) $booking->status === 1) {
                $this->review($ezeeBooking, $listing, sprintf(
                    'Linked booking #%d (%s to %s) is cancelled on our side but EZEE still reports the stay for %s to %s. Restore it, or reassign.',
                    $booking->id, $booking->check_in, $booking->check_out, $ezeeBooking->Start, $ezeeBooking->End));
                continue;
            }

            // An extra-guest room is not a place a real stay moves to, and one
            // extra room is as good as another: a booking already on a real
            // unit stays there, and one already on any extra room of the
            // property stays where it is.
            if (self::isExtraRoom($listing) && (int) $booking->listing_id !== (int) $listing->id) {
                $this->tally['unchanged']++;
                continue;
            }

            if ((int) $booking->listing_id === (int) $listing->id) {
                // EZEE may have shortened or extended the stay since we captured
                // it. Our dates never follow silently: the drift is raised for a
                // person, who accepts it from the review row.
                if ($drift = $this->dateDrift($ezeeBooking, $booking)) {
                    $this->review($ezeeBooking, $listing, $drift);
                    continue;
                }

                $this->tally['unchanged']++;
                continue;
            }

            // EZEE reports only the final room for a whole stay. A guest who
            // moved rooms mid-stay is held here as two bookings on one folio,
            // and the reservation's pointer sits on the first of them. That is
            // not a booking on the wrong unit, and moving it would put both
            // halves of the stay on the final room. Leave it where it is.
            if ($this->roomMoveSplit($booking, $listing->id)) {
                $this->tally['unchanged']++;
                continue;
            }

            $this->guard(fn () => $this->move($ezeeBooking, $booking, $listing), $ezeeBooking);
        }

        foreach ($pending as [$ezeeBooking, $listing]) {
            $this->guard(fn () => $this->assign($ezeeBooking, $listing), $ezeeBooking);
        }

        $this->closeStaleConflicts();

        return $this->summary();
    }

    /**
     * One unusable booking must not abort a run that is assigning many others,
     * so each is isolated and its failure recorded rather than thrown.
     */
    private function guard(callable $work, EzeeBooking $ezeeBooking): void
    {
        try {
            $work();
        } catch (\Throwable $e) {
            $this->tally['failed']++;
            $this->detail[] = [
                'action' => 'failed',
                'room'   => $ezeeBooking->RoomName,
                'error'  => $e->getMessage(),
            ];
            Log::error('EZEE assign failed for booking ' . $ezeeBooking->SubBookingId . ': ' . $e->getMessage());
        }
    }

    private function assign(EzeeBooking $ezeeBooking, Listing $listing): void
    {
        // A booking for this exact stay may already exist — assigned by hand on
        // the EZEE Bookings screen without the link being recorded. That is the
        // same reservation, not a clash with a different guest, so it is adopted
        // rather than reported as a conflict for someone to puzzle over.
        if ($existing = $this->sameStay($listing->id, $ezeeBooking)) {
            $this->adopt($ezeeBooking, $listing, $existing);

            return;
        }

        if ($clash = $this->clash($listing->id, $ezeeBooking)) {
            // EZEE lets two guests share "Extra Room 1" on one night; a listing
            // cannot. Any free extra room of the same property will do.
            $spill = self::isExtraRoom($listing) ? $this->freeExtraRoom($listing, $ezeeBooking) : null;
            if (!$spill && !self::isExtraRoom($listing)) {
                $this->conflict($ezeeBooking, $listing, null, $clash, 'assign');

                return;
            }
            // Extra rooms are the company's virtual rooms: when every one is
            // taken, the stay goes on the room EZEE named, overlapping, exactly
            // as EZEE holds it. No owner's calendar is involved.
            $overlap = !$spill;
            $listing = $spill ?: $listing;
        }

        $this->tally['assigned']++;
        $this->detail[] = [
            'action'  => 'assign',
            'room'    => $ezeeBooking->RoomName,
            'listing' => $listing->name,
            'dates'   => $ezeeBooking->Start . ' → ' . $ezeeBooking->End,
        ];

        if ($this->dryRun) {
            return;
        }

        $how = 'Matched on EZEE room ' . $ezeeBooking->RoomName;
        if (!empty($overlap)) {
            Booking::withoutOverlapCheck(fn () => $this->create($ezeeBooking, $listing, $how . ' (extra room, shared night)'));

            return;
        }
        $this->create($ezeeBooking, $listing, $how);
    }

    /**
     * Assign a reservation whose guest spent part of the stay in another unit
     * or in an extra-guest room. EZEE reports only the final room; the person
     * reading the EZEE calendar supplies the nights spent elsewhere and where.
     * The booking is created from EZEE's amounts on the reported unit and the
     * nights are carved out, so each unit carries exactly the nights it had.
     *
     * @param  int|null  $otherListingId  the unit those nights were in; null for an extra room
     * @return Booking[] every piece of the stay, in date order
     */
    public function assignSplit(EzeeBooking $ezeeBooking, Listing $final, string $from, string $to, ?int $otherListingId): array
    {
        if ($ezeeBooking->book_id) {
            throw new \InvalidArgumentException("{$ezeeBooking->SubBookingId} is already assigned to booking #{$ezeeBooking->book_id}.");
        }

        $start = substr((string) $ezeeBooking->Start, 0, 10);
        $end   = substr((string) $ezeeBooking->End, 0, 10);

        // The nights that stay on the reported unit must be free there.
        foreach ([[$start, $from], [$to, $end]] as [$a, $b]) {
            if ($a >= $b) {
                continue;
            }
            $clash = Booking::where('listing_id', $final->id)->where('status', '!=', 1)
                ->where('check_in', '<', $b)->where('check_out', '>', $a)->first();
            if ($clash) {
                throw new \InvalidArgumentException("{$final->name} already has booking #{$clash->id} over {$a} to {$b}.");
            }
        }

        return DB::transaction(function () use ($ezeeBooking, $final, $from, $to, $otherListingId) {
            $booking = Booking::withoutOverlapCheck(fn () => $this->create($ezeeBooking, $final,
                'Matched on EZEE room ' . $ezeeBooking->RoomName . ' with room history entered by hand', false));

            if (!$booking) {
                throw new \InvalidArgumentException("{$ezeeBooking->SubBookingId} was assigned by someone else meanwhile.");
            }

            return (new BookingSplitter)->carve($booking, $from, $to, $otherListingId, $this->actorId);
        });
    }

    /**
     * Bring back a booking that was cancelled on our side while EZEE still
     * reports the stay. Refused if another live booking now holds the unit.
     */
    public function restoreLinked(EzeeBooking $ezeeBooking): Booking
    {
        $booking = $ezeeBooking->book_id ? Booking::find($ezeeBooking->book_id) : null;

        if (!$booking || (int) $booking->status !== 1) {
            throw new \InvalidArgumentException("{$ezeeBooking->SubBookingId} has no cancelled booking to restore.");
        }

        if ($clash = $this->clash($booking->listing_id, $ezeeBooking, $booking->id)) {
            throw new \InvalidArgumentException(sprintf('Booking #%d (%s to %s, %s) now holds the unit. Decide which stay is real on the EZEE calendar, then reassign or cancel the other.',
                $clash->id, $clash->check_in, $clash->check_out, $clash->source));
        }

        $booking->status = 5;
        $booking->remark = trim(($booking->remark ?? '') . ' | restored ' . date('Y-m-d'));
        $booking->save();

        if ($listing = Listing::withoutGlobalScope('notArchived')->find($booking->listing_id)) {
            $this->record($ezeeBooking, $listing, null, 'manual', "Restored booking #{$booking->id}: EZEE still reports the stay.");
        }

        return $booking;
    }

    /**
     * Assign an unassigned reservation to a unit a person has chosen, with the
     * same pricing, month split and link the automatic path produces. Refused
     * if the unit is occupied for those nights; a person choosing the unit is
     * not a reason to stack two guests.
     */
    public function assignTo(EzeeBooking $ezeeBooking, Listing $listing): Booking
    {
        if ($ezeeBooking->book_id) {
            throw new \InvalidArgumentException("{$ezeeBooking->SubBookingId} is already assigned to booking #{$ezeeBooking->book_id}.");
        }

        if (($clash = $this->clash($listing->id, $ezeeBooking)) && !self::isExtraRoom($listing)) {
            throw new \InvalidArgumentException("{$listing->name} already has booking #{$clash->id} from {$clash->check_in} to {$clash->check_out}.");
        }

        // An extra room may hold two guests on one night, as it does in EZEE.
        $booking = $clash
            ? Booking::withoutOverlapCheck(fn () => $this->create($ezeeBooking, $listing, 'Assigned by hand to ' . $listing->name . ' (extra room, shared night)'))
            : $this->create($ezeeBooking, $listing, 'Assigned by hand to ' . $listing->name);

        if (!$booking) {
            throw new \InvalidArgumentException("{$ezeeBooking->SubBookingId} was assigned by someone else meanwhile.");
        }

        return $booking;
    }

    /**
     * A reservation for an extra-guest room needs no unit. It is marked so the
     * reconcile stops raising it, and any open conflict for it is closed.
     */
    public function markNoUnit(EzeeBooking $ezeeBooking, ?string $note = null): void
    {
        if ($ezeeBooking->book_id) {
            throw new \InvalidArgumentException("{$ezeeBooking->SubBookingId} is assigned to booking #{$ezeeBooking->book_id}; unlink it first.");
        }

        DB::transaction(function () use ($ezeeBooking, $note) {
            EzeeBooking::where('id', $ezeeBooking->id)->update(['status' => self::NO_UNIT]);

            EzeeAssignmentLog::where('ezee_booking_id', $ezeeBooking->id)
                ->where('method', 'conflict')->whereNull('resolved_at')
                ->update(['resolved_at' => now(), 'resolved_by' => $this->actorId,
                          'resolution_note' => 'No unit needed: ' . ($note ?: 'extra-guest room')]);

            // The log needs a unit to hang on; use the one EZEE's room name maps
            // to, which is the unit this reservation would otherwise have taken.
            if ($listing = EzeeUnitMap::make()->resolve($ezeeBooking)) {
                $this->record($ezeeBooking, $listing, null, 'manual',
                    'Marked as needing no unit (extra-guest room). ' . ($note ?: ''));
            }
        });
    }

    /**
     * Bring a linked booking's dates in line with what EZEE now reports. The
     * stamped nightly rate stands and the amount follows the nights. For a
     * stay held as several segments, segments that fall entirely outside the
     * new range are cancelled, the boundary segment is retimed, and the result
     * is cut by month again.
     *
     * @return Booking[] the live segments of the stay afterwards
     */
    public function acceptEzeeDates(EzeeBooking $ezeeBooking): array
    {
        $anchor = $ezeeBooking->book_id ? Booking::find($ezeeBooking->book_id) : null;

        if (!$anchor) {
            throw new \InvalidArgumentException("{$ezeeBooking->SubBookingId} is not linked to a booking.");
        }

        $start = substr((string) $ezeeBooking->Start, 0, 10);
        $end   = substr((string) $ezeeBooking->End, 0, 10);

        $segments = $this->stayRows($ezeeBooking, $anchor);

        $splitter = new BookingSplitter;

        return DB::transaction(function () use ($segments, $start, $end, $splitter, $ezeeBooking) {
            $kept = [];

            foreach ($segments as $seg) {
                if ($seg->check_out <= $start || $seg->check_in >= $end) {
                    $seg->status = 1;
                    $seg->remark = trim(($seg->remark ?? '') . " | cancelled: EZEE now reports {$start} to {$end}");
                    $seg->save();
                    continue;
                }
                $from = max($seg->check_in, $start);
                $to   = min($seg->check_out, $end);
                if ($from !== $seg->check_in || $to !== $seg->check_out) {
                    foreach ($splitter->retime($seg, $from, $to, $this->actorId) as $piece) {
                        $kept[] = $piece;
                    }
                } else {
                    $kept[] = $seg;
                }
            }

            usort($kept, fn ($a, $b) => strcmp($a->check_in, $b->check_in));

            // Every segment fell outside the new range (a check-in keyed on
            // the wrong day): bring the first one back and retime it.
            if (!$kept) {
                $first = $segments->first();
                $first->status = 5;
                $first->save();
                $kept = $splitter->retime($first, $start, $end, $this->actorId);
            }

            // Nights EZEE added beyond what any segment covered.
            $last = end($kept);
            if ($last && $last->check_out < $end) {
                $extra = $splitter->retime($last, $last->check_in, $end, $this->actorId);
                array_pop($kept);
                foreach ($extra as $piece) {
                    $kept[] = $piece;
                }
            }
            $first = $kept[0] ?? null;
            if ($first && $first->check_in > $start) {
                $extra = $splitter->retime($first, $start, $first->check_out, $this->actorId);
                array_shift($kept);
                $kept = array_merge($extra, $kept);
            }

            // The linked segment may be one that fell outside the new range and
            // was cancelled above; the reservation must point at a live segment.
            $keptIds = array_map(fn ($b) => $b->id, $kept);
            if ($kept && !in_array($ezeeBooking->book_id, $keptIds, true)) {
                EzeeBooking::where('id', $ezeeBooking->id)->update(['book_id' => $kept[0]->id, 'status' => 8]);
            }

            EzeeAssignmentLog::create([
                'ezee_booking_id' => $ezeeBooking->id,
                'listing_id'      => $kept[0]->listing_id ?? null,
                'old_listing_id'  => null,
                'assigned_by'     => $this->actorId,
                'method'          => 'modified',
                'note'            => "Dates accepted from EZEE: {$start} to {$end}. Rate unchanged; amount follows the nights.",
            ]);

            return $kept;
        });
    }

    /**
     * Create the guest, the booking and the link for a reservation. Returns
     * null when the reservation was linked by someone else in the meantime.
     */
    private function create(EzeeBooking $ezeeBooking, Listing $listing, string $how, bool $byMonth = true): ?Booking
    {
        $breakdown = EzeePricing::breakdown($ezeeBooking);
        $actorId   = $this->actorId;

        return DB::transaction(function () use ($ezeeBooking, $listing, $breakdown, $actorId, $how, $byMonth) {
            // Someone assigning the same reservation by hand on the EZEE
            // Bookings screen races this. Re-read under a lock: if that has
            // happened since the candidate list was built, the reservation is
            // already assigned and creating another booking would leave a
            // phantom stay on an owner's calendar with nothing behind it.
            $fresh = EzeeBooking::where('id', $ezeeBooking->id)->lockForUpdate()->first();

            if (!$fresh || $fresh->book_id) {
                return null;
            }

            $user = User::create([
                // EZEE does not always send a name, and users.name is NOT NULL.
                'name'      => $ezeeBooking->FirstName ?: 'EZEE Guest',
                'last_name' => $ezeeBooking->LastName ?: '',
                'phone'     => $ezeeBooking->Mobile,
                'email'     => $ezeeBooking->Email,
                'ezee_tmp'  => 1,
            ]);

            if ($role = Role::find(2)) {
                $user->attachRole($role);
            }

            $booking = Booking::create([
                'listing_id'   => $listing->id,
                'user_id'      => $user->id,
                'folio_no'     => $ezeeBooking->folio_no ?: 'FN' . substr((string) $ezeeBooking->TransactionId, -4),
                // eZee's folio as staff key it by hand, so the Bookings export shows it.
                'server_folio_no' => $ezeeBooking->folio_no ?: null,
                'check_in'     => $ezeeBooking->Start,
                'check_out'    => $ezeeBooking->End,
                'adult'        => 2,
                'infant'       => 0,
                'nights'       => $breakdown['nights'],
                'price_night'  => $breakdown['price_night'],
                'cleaning_fee' => $breakdown['cleaning_fee'],
                'ota_fee'      => $breakdown['ota_fee'],
                'sst'          => $breakdown['sst'],
                'sst_cf'       => $breakdown['sst_cf'],
                'price'        => $breakdown['total'],
                'tourism_tax'  => $breakdown['sst'],
                'discount_fee' => $ezeeBooking->TotalDiscount ?? 0,
                'source'       => preg_replace('/[^A-Za-z\. ]/', '', (string) $ezeeBooking->Source),
                'status'       => 5,
                'remark'       => 'Auto-assigned from EZEE room ' . $ezeeBooking->RoomName,
            ]);

            // status 8 marks the EZEE record assigned; without it the list still
            // shows "Unassigned" despite book_id being set.
            EzeeBooking::where('id', $ezeeBooking->id)->update([
                'book_id' => $booking->id,
                'status'  => 8,
            ]);

            $this->record($ezeeBooking->fresh(), $listing, null, 'auto', $how . ', created booking #' . $booking->id);

            // Revenue is reported by calendar month. A stay crossing a month end
            // is held as one row per month, the same way the other two paths
            // store it; the original row stays the first segment and keeps the
            // link above.
            if ($byMonth) {
                (new BookingSplitter)->splitByMonth($booking, $actorId);
            }

            return $booking->fresh();
        });
    }

    /**
     * Record an action in both trails.
     *
     * ezee_assignment_logs is the detailed, per-booking record the assignment
     * log screen reads. DataLog is the system-wide log, so an assignment shows
     * up alongside everything else that happened without having to know which
     * screen to look at.
     */
    private function record(EzeeBooking $ezeeBooking, Listing $listing, ?int $oldListingId, string $method, string $note): void
    {
        EzeeAssignmentLog::create([
            'ezee_booking_id' => $ezeeBooking->id,
            'listing_id'      => $listing->id,
            'old_listing_id'  => $oldListingId,
            'assigned_by'     => $this->actorId,
            'method'          => $method,
            'note'            => $note,
        ]);

        DataLog::create([
            'related_id' => $ezeeBooking->book_id ?: $ezeeBooking->id,
            'title'      => 'EZEE ' . $method,
            'data'       => json_encode([
                'sub_booking_id' => $ezeeBooking->SubBookingId,
                'room'           => $ezeeBooking->RoomName,
                'listing'        => $listing->name,
                'listing_id'     => $listing->id,
                'from_listing'   => $oldListingId,
                'stay'           => $ezeeBooking->Start . ' to ' . $ezeeBooking->End,
                'note'           => $note,
                'by'             => $this->actorId ? 'user #' . $this->actorId : 'scheduled sync',
            ], JSON_UNESCAPED_SLASHES),
            'status'     => $method === 'conflict' ? 'needs review' : 'done',
        ]);
    }

    /**
     * Close conflicts that no longer apply.
     *
     * A reservation this run examined and did not flag is no longer in conflict:
     * it has been assigned, the blocking booking was cancelled, or the clash was
     * reported by a bug since fixed. Leaving those open makes the queue grow
     * without bound and hides the ones that still need a person.
     */
    private function closeStaleConflicts(): void
    {
        $examined = array_keys($this->examined);

        if (!$examined || ($this->dryRun && !$this->closeStale)) {
            return;
        }

        // Only items this reconcile raises are its to close. A cancellation the
        // notification queue put up for review is not "no longer conflicting"
        // just because the unit is free.
        $stale = EzeeAssignmentLog::needsReview()
            ->whereIn('ezee_booking_id', $examined)
            ->whereNotIn('ezee_booking_id', array_keys($this->conflictedNow) ?: [0])
            ->where(fn ($q) => $q->where('note', 'like', 'Could not %')->orWhere('note', 'like', 'Dates changed%')->orWhere('note', 'like', 'Linked booking%'))
            ->get();

        foreach ($stale as $log) {
            $log->update([
                'resolved_at'     => now(),
                'resolved_by'     => null,
                'resolution_note' => 'Closed automatically: this booking no longer conflicts.',
            ]);

            $this->tally['resolved']++;
        }
    }

    /** A live booking already recording this exact stay on this unit. */
    /**
     * True when the booking a reservation points at is one half of a mid-stay
     * room move: a live booking on the same folio already sits on the unit
     * EZEE now reports. Folios repeat across properties, so the sibling must
     * also be a different row and not cancelled; the listing itself is
     * property-specific, which keeps this to one hotel.
     */
    private function roomMoveSplit(Booking $booking, int $targetListingId): bool
    {
        if (!$booking->folio_no) {
            return false;
        }

        return Booking::where('listing_id', $targetListingId)
            ->where('folio_no', $booking->folio_no)
            ->where('id', '!=', $booking->id)
            ->where('status', '!=', 1)
            ->exists();
    }

    /**
     * Unit and dates alone are not identity: two properties share reservation
     * numbers, and back-to-back one-night guests on one unit share dates
     * with nobody but still produced a wrong link when a different
     * reservation was adopted onto their booking and the two then fought
     * over it on every run. A booking is the same stay only when its folio
     * agrees (where the reservation carries one) and no other live
     * reservation already owns it.
     */
    private function sameStay(int $listingId, EzeeBooking $ezeeBooking): ?Booking
    {
        return Booking::where('listing_id', $listingId)
            ->where('status', '!=', 1)
            ->where('check_in', $ezeeBooking->Start)
            ->where('check_out', $ezeeBooking->End)
            ->when($ezeeBooking->folio_no, fn ($q, $folio) => $q->where('folio_no', $folio))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('ezee_bookings')
                ->whereColumn('ezee_bookings.book_id', 'bookings.id')
                ->where('ezee_bookings.status', '<>', 1)
                ->where('ezee_bookings.id', '<>', $ezeeBooking->id))
            ->first();
    }

    /**
     * Before lookups were scoped by property, a pull for one hotel overwrote
     * the End and RoomName of every other hotel's row carrying the same
     * reservation number. Those rows now describe another property's stay
     * and would be assigned, moved or reported as conflicts on every run.
     * The row with the latest Start is the one the values belong to; the
     * rest are left out of the reconcile.
     */
    private function overwrittenByOtherHotel(EzeeBooking $ezeeBooking): bool
    {
        if (!$ezeeBooking->SubBookingId) {
            return false;
        }

        return EzeeBooking::where('SubBookingId', $ezeeBooking->SubBookingId)
            ->where('id', '<>', $ezeeBooking->id)
            ->whereRaw('SUBSTR(TransactionId, 1, 5) <> ?', [substr((string) $ezeeBooking->TransactionId, 0, 5)])
            ->where('End', $ezeeBooking->End)
            ->where('RoomName', $ezeeBooking->RoomName)
            ->where('Start', '>', $ezeeBooking->Start)
            ->exists();
    }

    /** Link the reservation to a booking that already exists for it. */
    private function adopt(EzeeBooking $ezeeBooking, Listing $listing, Booking $existing): void
    {
        $this->tally['adopted']++;
        $this->detail[] = [
            'action'  => 'adopt',
            'room'    => $ezeeBooking->RoomName,
            'listing' => $listing->name,
            'dates'   => $ezeeBooking->Start . ' → ' . $ezeeBooking->End,
            'booking' => $existing->id,
        ];

        if ($this->dryRun) {
            return;
        }

        DB::transaction(function () use ($ezeeBooking, $listing, $existing) {
            $fresh = EzeeBooking::where('id', $ezeeBooking->id)->lockForUpdate()->first();

            if (!$fresh || $fresh->book_id) {
                return;
            }

            EzeeBooking::where('id', $ezeeBooking->id)->update([
                'book_id' => $existing->id,
                'status'  => 8,
            ]);

            $this->record($ezeeBooking->fresh(), $listing, null, 'auto',
                'Linked to existing booking #' . $existing->id . ' for the same stay');
        });
    }

    private function move(EzeeBooking $ezeeBooking, Booking $booking, Listing $listing): void
    {
        if ($clash = $this->clash($listing->id, $ezeeBooking, $booking->id)) {
            $this->conflict($ezeeBooking, $listing, $booking->listing_id, $clash, 'move');

            return;
        }

        $from = (int) $booking->listing_id;

        $this->vacated[$booking->id] = true;

        $this->tally['moved']++;
        $this->detail[] = [
            'action'  => 'move',
            'room'    => $ezeeBooking->RoomName,
            'listing' => $listing->name,
            'from_id' => $from,
            'dates'   => $ezeeBooking->Start . ' → ' . $ezeeBooking->End,
        ];

        if ($this->dryRun) {
            return;
        }

        $actorId = $this->actorId;

        DB::transaction(function () use ($ezeeBooking, $booking, $listing, $from, $actorId) {
            $booking->listing_id = $listing->id;
            $booking->save();

            $this->record($ezeeBooking, $listing, $from, 'move',
                'EZEE moved this booking to room ' . $ezeeBooking->RoomName . ' (booking #' . $booking->id . ')');
        });
    }

    /**
     * A live booking already occupying the unit over these dates. Cancelled
     * bookings (status 1) do not block. The booking being moved is excluded so
     * it never blocks itself.
     */
    private function clash(int $listingId, EzeeBooking $ezeeBooking, ?int $ignoreBookingId = null): ?Booking
    {
        return Booking::where('listing_id', $listingId)
            ->where('status', '!=', 1)
            ->when($ignoreBookingId, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
            ->when($this->vacated, fn ($q) => $q->whereNotIn('id', array_keys($this->vacated)))
            // Plain comparisons, not whereDate(): check_in and check_out are
            // DATE columns, so wrapping them in DATE() changes nothing except to
            // make the index on (listing_id, check_in, check_out) unusable. That
            // turned each of these into a full scan of the bookings table.
            ->where('check_in', '<', $ezeeBooking->End)
            ->where('check_out', '>', $ezeeBooking->Start)
            ->first();
    }

    private function conflict(EzeeBooking $ezeeBooking, Listing $listing, ?int $fromListingId, Booking $clash, string $intent): void
    {
        $this->conflictedNow[$ezeeBooking->id] = true;

        $this->tally['conflicts']++;
        $this->detail[] = [
            'action'  => 'conflict',
            'room'    => $ezeeBooking->RoomName,
            'listing' => $listing->name,
            'dates'   => $ezeeBooking->Start . ' → ' . $ezeeBooking->End,
            'blocked_by' => $clash->id,
        ];

        if ($this->dryRun) {
            return;
        }

        // An unresolved conflict is seen again on every hourly run. Logging it
        // each time would bury the review queue in duplicates of one problem.
        //
        // Keyed on the booking alone, not the booking and listing: the unit a
        // reservation resolves to changes as EZEE data is refreshed, and keying
        // on both raised a second row for the same problem every time it did.
        // One open item per booking is what a person needs to work from.
        $alreadyLogged = EzeeAssignmentLog::where('ezee_booking_id', $ezeeBooking->id)
            ->where('method', 'conflict')
            ->whereNull('resolved_at')
            ->exists();

        if ($alreadyLogged) {
            return;
        }

        $this->record($ezeeBooking, $listing, $fromListingId, 'conflict', sprintf(
                'Could not %s to %s for %s → %s: booking #%d already occupies it. Left unchanged for review.',
                $intent,
                $ezeeBooking->RoomName,
                $ezeeBooking->Start,
                $ezeeBooking->End,
            $clash->id
        ));
    }

    /**
     * The span our live rows cover for this stay: the linked booking and its
     * same-folio siblings on the same unit. Returns a note when EZEE's dates
     * differ from it, null when they agree.
     */
    private function dateDrift(EzeeBooking $ezeeBooking, Booking $booking): ?string
    {
        $rows = $this->stayRows($ezeeBooking, $booking);
        $span = (object) ['a' => $rows->min('check_in'), 'z' => $rows->max('check_out')];

        $start = substr((string) $ezeeBooking->Start, 0, 10);
        $end   = substr((string) $ezeeBooking->End, 0, 10);

        if (!$span || !$span->a || ($span->a === $start && $span->z === $end)) {
            return null;
        }

        return sprintf('Dates changed in EZEE: ours %s to %s, EZEE now %s to %s. Accept EZEE dates from the review row, or correct EZEE.',
            $span->a, $span->z, $start, $end);
    }

    /**
     * Every live row of the stay: the linked booking and the same-folio rows
     * on any unit of the same property within the stay's dates, so a mid-stay
     * room move counts as one stay. Another property's guest sharing the
     * folio number is excluded by the date window and the property.
     */
    private function stayRows(EzeeBooking $ezeeBooking, Booking $anchor)
    {
        return BookingSplitter::stayChain($anchor, substr((string) $ezeeBooking->TransactionId, 0, 5));
    }

    private static function isExtraRoom(Listing $listing): bool
    {
        return stripos((string) $listing->name, 'Extra Room') !== false;
    }

    /** Another extra-room listing of the same property with these nights free, if any. */
    private function freeExtraRoom(Listing $listing, EzeeBooking $ezeeBooking): ?Listing
    {
        $prefix = trim(preg_replace('/Extra Room.*$/i', '', (string) $listing->name));
        foreach (Listing::where('name', 'like', $prefix . ' Extra Room %')->where('id', '<>', $listing->id)->orderBy('name')->get() as $other) {
            if (!$this->clash($other->id, $ezeeBooking)) {
                return $other;
            }
        }

        return null;
    }

    /**
     * Raise a review item that is not a unit clash: the same dedup and log
     * path as a conflict, with a note that says what a person must decide.
     */
    private function review(EzeeBooking $ezeeBooking, Listing $listing, string $note): void
    {
        $this->conflictedNow[$ezeeBooking->id] = true;
        $this->tally['conflicts']++;
        $this->detail[] = [
            'action'  => 'review',
            'room'    => $ezeeBooking->RoomName,
            'listing' => $listing->name,
            'dates'   => $ezeeBooking->Start . ' → ' . $ezeeBooking->End,
            'note'    => $note,
        ];

        if ($this->dryRun) {
            return;
        }

        $alreadyLogged = EzeeAssignmentLog::where('ezee_booking_id', $ezeeBooking->id)
            ->where('method', 'conflict')->whereNull('resolved_at')->exists();

        if (!$alreadyLogged) {
            $this->record($ezeeBooking, $listing, null, 'conflict', $note);
        }
    }

    /** @return array<string,mixed> */
    private function summary(?string $message = null): array
    {
        return array_merge($this->tally, [
            'dry_run' => $this->dryRun,
            'message' => $message,
            'detail'  => array_slice($this->detail, 0, 100),
        ]);
    }
}
