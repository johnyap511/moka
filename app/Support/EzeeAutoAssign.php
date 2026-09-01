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
    private bool $dryRun;
    private ?int $actorId;

    /** @var array<string,int> */
    private array $tally = [
        'assigned'  => 0,
        'moved'     => 0,
        'conflicts' => 0,
        'unmapped'  => 0,
        'unchanged' => 0,
        'adopted'   => 0,
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

    public function __construct(bool $dryRun = false, ?int $actorId = null)
    {
        $this->dryRun  = $dryRun;
        $this->actorId = $actorId;
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

            if (!$listing) {
                $this->tally['unmapped']++;
                continue;
            }

            $booking = $ezeeBooking->book_id ? Booking::find($ezeeBooking->book_id) : null;

            if (!$booking) {
                $pending[] = [$ezeeBooking, $listing];
                continue;
            }

            if ((int) $booking->listing_id === (int) $listing->id) {
                $this->tally['unchanged']++;
                continue;
            }

            $this->guard(fn () => $this->move($ezeeBooking, $booking, $listing), $ezeeBooking);
        }

        foreach ($pending as [$ezeeBooking, $listing]) {
            $this->guard(fn () => $this->assign($ezeeBooking, $listing), $ezeeBooking);
        }

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
            $this->conflict($ezeeBooking, $listing, null, $clash, 'assign');

            return;
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

        $breakdown = EzeePricing::breakdown($ezeeBooking);
        $actorId   = $this->actorId;

        DB::transaction(function () use ($ezeeBooking, $listing, $breakdown, $actorId) {
            // Someone assigning the same reservation by hand on the EZEE
            // Bookings screen races this. Re-read under a lock: if that has
            // happened since the candidate list was built, the reservation is
            // already assigned and creating another booking would leave a
            // phantom stay on an owner's calendar with nothing behind it.
            $fresh = EzeeBooking::where('id', $ezeeBooking->id)->lockForUpdate()->first();

            if (!$fresh || $fresh->book_id) {
                return;
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

            $this->record($ezeeBooking->fresh(), $listing, null, 'auto',
                'Matched on EZEE room ' . $ezeeBooking->RoomName . ', created booking #' . $booking->id);
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

    /** A live booking already recording this exact stay on this unit. */
    private function sameStay(int $listingId, EzeeBooking $ezeeBooking): ?Booking
    {
        return Booking::where('listing_id', $listingId)
            ->where('status', '!=', 1)
            ->where('check_in', $ezeeBooking->Start)
            ->where('check_out', $ezeeBooking->End)
            ->first();
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
        // each time would bury the review queue in duplicates of one problem,
        // so it is recorded once and only re-recorded if something changes.
        $alreadyLogged = EzeeAssignmentLog::where('ezee_booking_id', $ezeeBooking->id)
            ->where('method', 'conflict')
            ->where('listing_id', $listing->id)
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
