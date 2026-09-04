<?php

namespace App\Http\Controllers\Admin;

use App\DataLog;
use App\EzeeAssignmentLog;
use App\Booking;
use App\EzeeGroup;
use App\EzeeRoom;
use App\EzeeRoomMapping;
use App\Http\Controllers\Controller;
use App\Listing;
use App\OtherModel\EzeeBooking;
use App\Support\EzeeAutoAssign;
use App\Support\EzeeUnitMap;
use App\Support\BookingSplitter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EzeeRoomMappingController extends Controller
{
    public function index(Request $request)
    {
        $showArchived = $request->boolean('archived');

        // Archived properties are no longer managed, so they are not offered
        // as somewhere to map a unit.
        $listings = Listing::active()->orderBy('name')->get();
        $groups   = EzeeGroup::all()->keyBy('id');

        // One row per property AND unit. A unit name is only unique within a
        // property — "Extra Room 1" exists in four of them — so a single row per
        // name would offer one mapping where four are needed.
        $rooms = EzeeRoom::orderBy('room_name')->get()->map(function ($room) use ($groups) {
            return (object) [
                'Key'          => $room->ezee_group_id . '|' . $room->room_name,
                'GroupId'      => $room->ezee_group_id,
                'PropertyName' => optional($groups->get($room->ezee_group_id))->name ?? '—',
                'RoomName'     => $room->room_name,
                'RoomTypeName' => $room->room_type_name,
            ];
        });

        // Matched on name, not on property. A unit's historical bookings do not
        // reliably carry its property: AL-11-08 is an Alinea unit but its
        // TransactionIds are prefixed with EkoCheras and Bell Suites codes.
        // Keying this against the property would list the same unit once per
        // code it has ever appeared under.
        $knownNames = $rooms->pluck('RoomName')->map(fn ($n) => strtolower(trim($n)))->flip();

        // Units seen only in bookings — retired ones, and anything present
        // before the inventory sync. The property comes from the hotel code that
        // prefixes TransactionId, since ezee_group_id is not set on bookings.
        $codeToGroup = $groups->mapWithKeys(fn ($g) => [(string) $g->hotel_code => $g->id]);

        $fromBookings = EzeeBooking::selectRaw('SUBSTR(TransactionId, 1, 5) AS code, RoomName, MIN(RoomTypeName) AS RoomTypeName')
            ->whereNotNull('RoomName')
            ->where('RoomName', '!=', '')
            ->groupBy('code', 'RoomName')
            ->get()
            ->map(function ($row) use ($codeToGroup, $groups) {
                $groupId = $codeToGroup[(string) $row->code] ?? null;

                return (object) [
                    'Key'          => $groupId . '|' . $row->RoomName,
                    'GroupId'      => $groupId,
                    'PropertyName' => optional($groups->get($groupId))->name ?? '—',
                    'RoomName'     => $row->RoomName,
                    'RoomTypeName' => $row->RoomTypeName,
                ];
            })
            ->reject(fn ($r) => $knownNames->has(strtolower(trim($r->RoomName))))
            ->unique(fn ($r) => strtolower(trim($r->RoomName)))
            ->values();

        $rooms = $rooms->concat($fromBookings)
            ->sortBy([['PropertyName', 'asc'], ['RoomName', 'asc']])
            ->values();

        $mappings = EzeeRoomMapping::all()
            ->keyBy(fn ($m) => $m->ezee_group_id . '|' . $m->room_name);

        // Archived units are ones no longer managed. They stay in the database
        // with their mapping intact so the decision is reversible, but they are
        // kept off the working list.
        $archivedKeys = $mappings->filter(fn ($m) => $m->archived_at !== null)->keys()->flip();
        $archivedCount = $archivedKeys->count();

        $rooms = $rooms->filter(
            fn ($room) => $showArchived ? $archivedKeys->has($room->Key) : ! $archivedKeys->has($room->Key)
        )->values();

        // Booking counts per property and unit, keyed the same way as the rows.
        $stats = EzeeBooking::selectRaw(
                'SUBSTR(TransactionId, 1, 5) AS code, RoomName, COUNT(*) as total, SUM(CASE WHEN book_id IS NOT NULL THEN 1 ELSE 0 END) as assigned'
            )
            ->whereNotNull('RoomName')
            ->where('RoomName', '!=', '')
            ->groupBy('code', 'RoomName')
            ->get()
            ->keyBy(fn ($row) => ($codeToGroup[(string) $row->code] ?? null) . '|' . $row->RoomName);

        // Suggest a listing whose name matches the unit exactly.
        $byName      = $listings->keyBy(fn ($l) => strtolower(trim($l->name)));
        $suggestions = [];

        foreach ($rooms as $room) {
            $existing = $mappings[$room->Key] ?? null;

            if ($existing && $existing->listing_id) {
                continue;
            }

            if ($match = $byName->get(strtolower(trim($room->RoomName)))) {
                $suggestions[$room->Key] = $match->id;
            }
        }

        return view('admin.ezee.room_mapping', compact(
            'listings', 'rooms', 'mappings', 'stats', 'suggestions', 'showArchived', 'archivedCount'
        ));
    }

    /**
     * Archive or restore a unit.
     *
     * A mapping row is created if none exists, because a unit can be archived
     * before anyone has mapped it — retired units carrying old bookings are
     * exactly the ones most likely to be archived first.
     */
    public function setArchived(Request $request)
    {
        $request->validate([
            'keys'     => 'required|array|min:1',
            'keys.*'   => 'string',
            'archived' => 'required|boolean',
        ]);

        $archived = $request->boolean('archived');
        $stamp    = $archived ? now() : null;
        $done     = 0;

        foreach ($request->input('keys') as $key) {
            // Keys are "<ezee_group_id>|<unit>": a unit name only identifies a
            // unit within one property.
            [$groupId, $roomName] = array_pad(explode('|', (string) $key, 2), 2, null);

            if ($roomName === null || $roomName === '') {
                continue;
            }

            // A mapping row is created if none exists, because a unit can be
            // archived before anyone has mapped it — which is the common case
            // when clearing out units that are no longer managed.
            $mapping = EzeeRoomMapping::firstOrNew([
                'ezee_group_id' => $groupId !== '' ? $groupId : null,
                'room_name'     => $roomName,
            ]);

            $mapping->archived_at = $stamp;
            $mapping->save();

            $done++;
        }

        return response()->json([
            'ok'      => true,
            'count'   => $done,
            'message' => $archived
                ? "Archived {$done} unit(s). They will not be assigned to."
                : "Restored {$done} unit(s).",
        ]);
    }

    public function saveAll(Request $request)
    {
        $data  = $request->input('mappings', []);
        $saved = 0;

        foreach ($data as $key => $listingId) {
            // Keys arrive as "<ezee_group_id>|<unit>", because a unit name only
            // identifies a unit within one property.
            [$groupId, $roomName] = array_pad(explode('|', (string) $key, 2), 2, null);

            if ($roomName === null || $roomName === '') {
                continue;
            }

            EzeeRoomMapping::updateOrCreate(
                ['ezee_group_id' => $groupId !== '' ? $groupId : null, 'room_name' => $roomName],
                ['listing_id' => $listingId ?: null]
            );
            $saved++;
        }

        $message = 'Saved ' . $saved . ' ' . ($saved === 1 ? 'mapping' : 'mappings') . '.';

        // Answering JSON lets the screen save without reloading, which on a list
        // this long otherwise throws the reader back to the top every time.
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'count' => $saved, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    /**
     * Assign EZEE bookings to listings, and follow room moves.
     *
     * The work itself lives in EzeeAutoAssign so this button and the hourly
     * sync cannot drift apart — both must create bookings with the same pricing
     * and statuses, and refuse a move the same way.
     */
    public function autoAssign(Request $request)
    {
        set_time_limit(0);

        $result = (new EzeeAutoAssign($request->boolean('dry_run'), Auth::id()))
            ->reconcile($request->input('from'));

        if ($result['message'] !== null) {
            return response()->json(['ok' => false, 'message' => $result['message']], 422);
        }

        return response()->json([
            'ok'         => true,
            'dry_run'    => $result['dry_run'],
            'assigned'   => $result['assigned'],
            'moved'      => $result['moved'],
            'conflicts'  => $result['conflicts'],
            'no_listing' => $result['unmapped'],
            'message'    => sprintf(
                '%s %d booking(s); %d room move(s) followed; %d conflict(s) left for review; %d had no mapped unit.',
                $result['dry_run'] ? 'Would assign' : 'Assigned',
                $result['assigned'],
                $result['moved'],
                $result['conflicts'],
                $result['unmapped']
            ),
            'detail'     => $result['detail'],
        ]);
    }

    /**
     * Split one stay across two units.
     *
     * Distinct from reassign, which moves a whole booking. A guest who changed
     * room mid-stay occupied two units, and EZEE reports only the last one, so
     * the division has to be entered by someone who can see its calendar.
     */
    /**
     * Move one booking (a whole stay or one piece of it) to another unit, from
     * the Edit Booking page. The overlap check runs on save; the move is logged
     * against the eZee record when there is one, otherwise in the data log.
     */
    public function reassignBooking(Request $request, $bookingId)
    {
        $request->validate(['listing_id' => 'required|exists:listings,id']);

        $booking = Booking::withoutGlobalScopes()->findOrFail($bookingId);
        $listing = Listing::withoutGlobalScope('notArchived')->findOrFail($request->listing_id);
        $from    = $booking->listing_id;

        if ((int) $from === (int) $listing->id) {
            return response()->json(['ok' => false, 'message' => 'The booking is already in ' . $listing->name . '.'], 422);
        }

        try {
            $booking->listing_id = $listing->id;
            $booking->remark     = mb_substr(trim((string) $booking->remark . ' | moved to ' . $listing->name . ' by staff ' . now()->format('d M')), 0, 255);
            $booking->save();
        } catch (\InvalidArgumentException | \App\Exceptions\OverlappingBookingException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $eb = EzeeBooking::where('book_id', $booking->id)->where('status', '<>', 1)->first();
        if ($eb) {
            EzeeAssignmentLog::create([
                'ezee_booking_id' => $eb->id, 'listing_id' => $listing->id, 'old_listing_id' => $from, 'assigned_by' => Auth::id(), 'method' => 'reassign',
                'note' => sprintf('Booking #%d (%s to %s) moved to %s from the Edit Booking page.', $booking->id, $booking->check_in, $booking->check_out, $listing->name),
            ]);
        }
        \App\DataLog::create(['related_id' => $booking->id, 'title' => 'Booking moved', 'status' => 'done',
            'data' => json_encode(['booking' => $booking->id, 'from_listing_id' => $from, 'to_listing_id' => $listing->id, 'stay' => $booking->check_in . ' to ' . $booking->check_out, 'by' => 'user #' . Auth::id()])]);

        return response()->json(['ok' => true, 'message' => 'Moved to ' . $listing->name . '.']);
    }

    /**
     * Cancel a booking from the Edit Booking page, the way the sync cancels one
     * eZee reports: the booking is cancelled (never deleted), the unit is freed,
     * and the eZee record is retired so no job re-creates the stay. The reason
     * and the staff member are kept on the booking and in the assignment log.
     */
    public function cancelBooking(Request $request, $bookingId)
    {
        $request->validate(['reason' => 'required|string|max:160']);

        $booking = Booking::withoutGlobalScopes()->findOrFail($bookingId);
        if ((int) $booking->status === 1) {
            return response()->json(['ok' => false, 'message' => 'This booking is already cancelled.'], 422);
        }

        $reason = trim($request->input('reason'));
        $by     = Auth::user()->name ?? ('user #' . Auth::id());
        $stamp  = now()->format('d M Y H:i');

        DB::table('bookings')->where('id', $booking->id)->update([
            'status'     => 1,
            'remark'     => mb_substr(trim((string) $booking->remark . ' | cancelled by ' . $by . ' ' . $stamp . ': ' . $reason), 0, 255),
            'updated_at' => now(),
        ]);

        $eb = EzeeBooking::where('book_id', $booking->id)->where('status', '<>', 1)->first();
        if ($eb) {
            EzeeBooking::where('id', $eb->id)->update(['status' => 1]);
            EzeeAssignmentLog::create([
                'ezee_booking_id' => $eb->id, 'listing_id' => $booking->listing_id, 'old_listing_id' => null, 'assigned_by' => Auth::id(), 'method' => 'cancelled',
                'note' => sprintf('Cancelled by %s on %s: %s. Booking #%d cancelled; %s retired so it is not re-created.', $by, $stamp, $reason, $booking->id, $eb->SubBookingId),
            ]);
            EzeeAssignmentLog::where('ezee_booking_id', $eb->id)->where('method', 'conflict')->whereNull('resolved_at')
                ->update(['resolved_at' => now(), 'resolved_by' => Auth::id(), 'resolution_note' => 'Cancelled by ' . $by . ': ' . $reason]);
        }
        DataLog::create(['related_id' => $booking->id, 'title' => 'Booking cancelled', 'status' => 'done',
            'data' => json_encode(['booking' => $booking->id, 'listing_id' => $booking->listing_id, 'stay' => $booking->check_in . ' to ' . $booking->check_out, 'reason' => $reason, 'ezee' => $eb->SubBookingId ?? null, 'by' => $by])]);

        return response()->json(['ok' => true, 'message' => 'Booking #' . $booking->id . ' cancelled. The unit is free' . ($eb ? ' and ' . $eb->SubBookingId . ' will not be re-created.' : '.')]);
    }

    /**
     * A reservation staff have confirmed is voided or cancelled in eZee, from the
     * review row. eZee sends no event for a void, so this is the human saying so:
     * the eZee record is retired (never re-created), its booking, if any, is
     * cancelled and the unit freed, and the reason and staff member are logged.
     */
    public function voidedInEzee(Request $request, $ezeeBookingId)
    {
        $request->validate(['reason' => 'required|string|max:160']);

        $eb = EzeeBooking::findOrFail($ezeeBookingId);
        if ((int) $eb->status === 1) {
            return response()->json(['ok' => false, 'message' => $eb->SubBookingId . ' is already retired.'], 422);
        }

        $reason  = trim($request->input('reason'));
        $by      = Auth::user()->name ?? ('user #' . Auth::id());
        $stamp   = now()->format('d M Y H:i');
        $booking = $eb->book_id ? Booking::withoutGlobalScopes()->find($eb->book_id) : null;

        EzeeBooking::where('id', $eb->id)->update(['status' => 1]);
        if ($booking && (int) $booking->status !== 1) {
            DB::table('bookings')->where('id', $booking->id)->update([
                'status'     => 1,
                'remark'     => mb_substr(trim((string) $booking->remark . ' | voided in EZEE, cancelled by ' . $by . ' ' . $stamp . ': ' . $reason), 0, 255),
                'updated_at' => now(),
            ]);
        }
        EzeeAssignmentLog::create([
            'ezee_booking_id' => $eb->id, 'listing_id' => $booking->listing_id ?? null, 'old_listing_id' => null, 'assigned_by' => Auth::id(), 'method' => 'cancelled',
            'note' => sprintf('Voided in EZEE, confirmed by %s on %s: %s. %s retired%s.', $by, $stamp, $reason, $eb->SubBookingId, $booking ? '; booking #' . $booking->id . ' cancelled' : ''),
        ]);
        EzeeAssignmentLog::where('ezee_booking_id', $eb->id)->where('method', 'conflict')->whereNull('resolved_at')
            ->update(['resolved_at' => now(), 'resolved_by' => Auth::id(), 'resolution_note' => 'Voided in EZEE, confirmed by ' . $by . ': ' . $reason]);
        DataLog::create(['related_id' => $booking->id ?? $eb->id, 'title' => 'EZEE voided', 'status' => 'done',
            'data' => json_encode(['sub_booking_id' => $eb->SubBookingId, 'booking' => $booking->id ?? null, 'reason' => $reason, 'by' => $by])]);

        return response()->json(['ok' => true, 'message' => $eb->SubBookingId . ' retired' . ($booking ? ', booking #' . $booking->id . ' cancelled and the unit freed.' : '. It will not be assigned again.')]);
    }

    /**
     * Swap the units of two bookings in one step. Done by hand this takes three
     * moves (park one in an extra room, move the other, move the first back) and
     * can be left half done. Here both move inside one transaction, the clash
     * check runs on the end state, and one log entry records the swap.
     */
    public function swapUnits(Request $request, $bookingId)
    {
        $request->validate(['other_id' => 'required|integer|different:bookingId']);

        $a = Booking::withoutGlobalScopes()->with('listing')->findOrFail($bookingId);
        $b = Booking::withoutGlobalScopes()->with('listing')->findOrFail($request->input('other_id'));
        if ($a->id === $b->id || (int) $a->status !== 5 || (int) $b->status !== 5) {
            return response()->json(['ok' => false, 'message' => 'Both bookings must be live, and different.'], 422);
        }
        if ((int) $a->listing_id === (int) $b->listing_id) {
            return response()->json(['ok' => false, 'message' => 'Both bookings are already in the same unit.'], 422);
        }

        $unitA = $a->listing; $unitB = $b->listing;
        $by    = Auth::user()->name ?? ('user #' . Auth::id());
        $stamp = now()->format('d M');

        try {
            DB::transaction(function () use ($a, $b, $unitA, $unitB, $by, $stamp) {
                Booking::withoutOverlapCheck(function () use ($a, $b, $unitA, $unitB, $by, $stamp) {
                    $a->listing_id = $unitB->id; $a->remark = mb_substr(trim((string) $a->remark . ' | swapped with #' . $b->id . ' into ' . $unitB->name . ' by ' . $by . ' ' . $stamp), 0, 255); $a->save();
                    $b->listing_id = $unitA->id; $b->remark = mb_substr(trim((string) $b->remark . ' | swapped with #' . $a->id . ' into ' . $unitA->name . ' by ' . $by . ' ' . $stamp), 0, 255); $b->save();
                });
                // The clash check on the end state: each booking against everything else in its new unit.
                foreach ([[$a, $unitB], [$b, $unitA]] as [$bk, $unit]) {
                    $clash = Booking::withoutGlobalScopes()->where('listing_id', $unit->id)->where('status', 5)->whereNotIn('id', [$a->id, $b->id])
                        ->where('check_in', '<', $bk->check_out)->where('check_out', '>', $bk->check_in)->first();
                    if ($clash) {
                        throw new \InvalidArgumentException(sprintf('%s is taken %s to %s by booking #%d, so #%d cannot go there.', $unit->name, $clash->check_in, $clash->check_out, $clash->id, $bk->id));
                    }
                }
            });
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        foreach ([[$a, $unitA, $unitB], [$b, $unitB, $unitA]] as [$bk, $from, $to]) {
            if ($eb = EzeeBooking::where('book_id', $bk->id)->where('status', '<>', 1)->first()) {
                EzeeAssignmentLog::create(['ezee_booking_id' => $eb->id, 'listing_id' => $to->id, 'old_listing_id' => $from->id, 'assigned_by' => Auth::id(), 'method' => 'reassign',
                    'note' => sprintf('Swap: booking #%d moved %s → %s, exchanging units with #%d.', $bk->id, $from->name, $to->name, $bk->id === $a->id ? $b->id : $a->id)]);
            }
        }
        DataLog::create(['related_id' => $a->id, 'title' => 'Booking swapped', 'status' => 'done',
            'data' => json_encode(['bookings' => [$a->id, $b->id], 'units' => [$unitA->name, $unitB->name], 'by' => $by])]);

        return response()->json(['ok' => true, 'message' => sprintf('Swapped: #%d is now in %s, #%d is now in %s.', $a->id, $unitB->name, $b->id, $unitA->name)]);
    }

    public function split(Request $request, $bookingId, BookingSplitter $splitter)
    {
        $request->validate([
            'from'       => 'required|date_format:Y-m-d',
            'to'         => 'required|date_format:Y-m-d|after:from',
            'listing_id' => 'nullable|exists:listings,id',
        ]);

        $booking = Booking::findOrFail($bookingId);

        try {
            $pieces = $splitter->carve($booking, $request->input('from'), $request->input('to'),
                $request->input('listing_id') ? (int) $request->input('listing_id') : null, Auth::id());
        } catch (\InvalidArgumentException | \App\Exceptions\OverlappingBookingException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Stay is now: ' . collect($pieces)->map(fn ($p) => sprintf('%s %s to %s', optional($p->listing)->name ?? 'unit', $p->check_in, $p->check_out))->implode('; ') . '.',
        ]);
    }

    public function reassign(Request $request, $ezeeBookingId)
    {
        $request->validate(['listing_id' => 'required|exists:listings,id']);

        $eb           = EzeeBooking::findOrFail($ezeeBookingId);
        $listing      = Listing::withoutGlobalScope('notArchived')->findOrFail($request->listing_id);
        $oldListingId = null;

        if ($eb->book_id && ($cur = Booking::withoutGlobalScopes()->find($eb->book_id)) && (int) $cur->listing_id === (int) $listing->id) {
            return response()->json(['ok' => false, 'message' => 'The booking is already in ' . $listing->name . '.'], 422);
        }

        try {
            if ($eb->book_id && ($existing = Booking::find($eb->book_id))) {
                $oldListingId         = $existing->listing_id;
                $existing->listing_id = $listing->id;
                $existing->save();
            } else {
                // An unassigned reservation is created the same way the
                // automatic path creates it: EZEE's amounts, the channel fee,
                // one row per calendar month, the link recorded. Nothing typed.
                (new EzeeAutoAssign(false, Auth::id()))->assignTo($eb, $listing);
            }
        } catch (\InvalidArgumentException | \App\Exceptions\OverlappingBookingException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        if ($oldListingId !== null) {
            EzeeAssignmentLog::create([
                'ezee_booking_id' => $eb->id,
                'listing_id'      => $listing->id,
                'old_listing_id'  => $oldListingId,
                'assigned_by'     => Auth::id(),
                'method'          => 'reassign',
                'note'            => $request->note,
            ]);
        }

        return response()->json(['ok' => true, 'message' => 'Booking reassigned successfully.']);
    }

    /**
     * Assign a reservation whose first nights were in another unit, or in an
     * extra-guest room. The person supplies only what the EZEE calendar
     * shows; the booking itself is created from EZEE's amounts.
     */
    public function assignHistory(Request $request, $ezeeBookingId)
    {
        $request->validate([
            'from'             => 'required|date_format:Y-m-d',
            'to'               => 'required|date_format:Y-m-d|after:from',
            'other_listing_id' => 'nullable|exists:listings,id',
        ]);

        $eb    = EzeeBooking::findOrFail($ezeeBookingId);
        $final = EzeeUnitMap::make()->resolve($eb);
        $other = $request->input('other_listing_id') ? (int) $request->input('other_listing_id') : null;

        try {
            $linked = $eb->book_id ? Booking::find($eb->book_id) : null;

            if ($linked && (int) $linked->status !== 1) {
                // Already assigned: the same cut applied to the booking it has.
                $pieces = (new BookingSplitter)->carve($linked, $request->input('from'), $request->input('to'), $other, Auth::id());
            } else {
                if (!$final) {
                    return response()->json(['ok' => false, 'message' => "No unit is mapped to EZEE room {$eb->RoomName}."], 422);
                }
                $pieces = (new EzeeAutoAssign(false, Auth::id()))->assignSplit($eb, $final, $request->input('from'), $request->input('to'), $other);
            }
        } catch (\InvalidArgumentException | \App\Exceptions\OverlappingBookingException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $this->closeConflictsFor($eb, sprintf('Assigned with room history: %s to %s elsewhere', $request->input('from'), $request->input('to')));

        return response()->json(['ok' => true, 'message' => 'Assigned: ' . collect($pieces)->map(fn ($p) => sprintf('%s %s to %s', optional($p->listing)->name ?? 'unit', $p->check_in, $p->check_out))->implode('; ') . '.']);
    }

    /** A booking cancelled on our side while EZEE still reports the stay comes back. */
    public function restore(Request $request, $ezeeBookingId)
    {
        $eb = EzeeBooking::findOrFail($ezeeBookingId);

        try {
            $booking = (new EzeeAutoAssign(false, Auth::id()))->restoreLinked($eb);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $this->closeConflictsFor($eb, "Restored booking #{$booking->id}");

        return response()->json(['ok' => true, 'message' => sprintf('Booking #%d restored: %s to %s.', $booking->id, $booking->check_in, $booking->check_out)]);
    }

    /** The reservation was an extra-guest room: it needs no unit of its own. */
    public function noUnit(Request $request, $ezeeBookingId)
    {
        $request->validate(['note' => 'nullable|string|max:255']);
        $eb = EzeeBooking::findOrFail($ezeeBookingId);

        try {
            (new EzeeAutoAssign(false, Auth::id()))->markNoUnit($eb, $request->input('note'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'message' => "{$eb->SubBookingId} marked as needing no unit."]);
    }

    /** EZEE's dates for a linked booking are accepted; the stamped rate stands. */
    /** Reprice a hand-keyed booking from EZEE's current amounts, from the review row. */
    public function acceptAmounts(Request $request, $id)
    {
        $row = \App\OtherModel\EzeeBooking::findOrFail($id);
        try {
            $r = (new \App\Support\EzeeAutoAssign(false, auth()->id()))->acceptEzeeAmounts($row);
            return response()->json(['ok' => true, 'message' => $r['changed'] ? 'Repriced from EZEE: ' . $r['note'] : $r['note']]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function acceptDates(Request $request, $ezeeBookingId)
    {
        $eb = EzeeBooking::findOrFail($ezeeBookingId);

        try {
            $segments = (new EzeeAutoAssign(false, Auth::id()))->acceptEzeeDates($eb);
        } catch (\InvalidArgumentException | \App\Exceptions\OverlappingBookingException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $this->closeConflictsFor($eb, "Dates accepted from EZEE: {$eb->Start} to {$eb->End}");

        return response()->json(['ok' => true, 'message' => sprintf('Booking now runs %s to %s in %d segment(s).',
            $segments[0]->check_in, end($segments)->check_out, count($segments))]);
    }

    private function closeConflictsFor(EzeeBooking $eb, string $note): void
    {
        EzeeAssignmentLog::where('ezee_booking_id', $eb->id)->where('method', 'conflict')->whereNull('resolved_at')
            ->update(['resolved_at' => now(), 'resolved_by' => Auth::id(), 'resolution_note' => $note]);
    }

    /**
     * Mark a conflict dealt with, or reopen one.
     *
     * Resolving does not change any booking — it records that a person has
     * looked at it. Reopening exists because closing the wrong row should not
     * be a one-way door.
     */
    public function resolveConflict(Request $request, $logId)
    {
        $request->validate([
            'resolved' => 'required|boolean',
            'note'     => 'nullable|string|max:255',
        ]);

        $log = EzeeAssignmentLog::findOrFail($logId);

        if ($log->method !== 'conflict') {
            return response()->json(['ok' => false, 'message' => 'Only conflicts can be resolved.'], 422);
        }

        $resolved = $request->boolean('resolved');

        $log->update([
            'resolved_at'     => $resolved ? now() : null,
            'resolved_by'     => $resolved ? Auth::id() : null,
            'resolution_note' => $resolved ? ($request->input('note') ?: 'Marked done.') : null,
        ]);

        DataLog::create([
            'related_id' => $log->ezee_booking_id,
            'title'      => $resolved ? 'EZEE conflict resolved' : 'EZEE conflict reopened',
            'data'       => json_encode([
                'log'  => $log->id,
                'note' => $log->resolution_note,
                'by'   => Auth::id(),
            ], JSON_UNESCAPED_SLASHES),
            'status'     => $resolved ? 'done' : 'needs review',
        ]);

        return response()->json([
            'ok'      => true,
            'message' => $resolved ? 'Marked as done.' : 'Reopened for review.',
        ]);
    }

    public function auditLog(Request $request)
    {
        // Conflicts are the only rows that need a person, so they get their own
        // filter rather than being buried among the successful assignments.
        $method = $request->input('method');

        $logs = EzeeAssignmentLog::with(['listing', 'assignedBy'])
            ->when($method === 'conflict', fn ($q) => $q->needsReview())
            ->when($method && $method !== 'conflict', fn ($q) => $q->where('method', $method))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $counts = EzeeAssignmentLog::selectRaw('method, COUNT(*) c')
            ->groupBy('method')
            ->pluck('c', 'method');

        // "Needs review" means an outstanding conflict, not every conflict ever
        // logged — otherwise the count never falls and the queue is useless.
        $counts['conflict'] = EzeeAssignmentLog::needsReview()->count();

        $ezeeIds = $logs->pluck('ezee_booking_id')->unique();
        $ezeeMap = EzeeBooking::whereIn('id', $ezeeIds)
            ->get(['id', 'SubBookingId', 'VoucherNo', 'FirstName', 'LastName', 'RoomName', 'RoomTypeName', 'Start', 'End', 'book_id', 'Source'])
            ->keyBy('id');

        // The review row needs to show our dates against EZEE's, and the
        // booking that blocked the assignment, so the pattern can be named
        // without leaving the screen.
        $bookIds = $ezeeMap->pluck('book_id')->filter();
        $blockedIds = $logs->map(fn ($l) => preg_match('/booking #(\d+)/', (string) $l->note, $m) ? (int) $m[1] : null)->filter();
        $bookingMap = Booking::withoutGlobalScopes()->whereIn('id', $bookIds->merge($blockedIds)->unique())
            ->with('listing')->get(['id', 'listing_id', 'folio_no', 'check_in', 'check_out', 'status', 'source'])->keyBy('id');

        $listings = $method === 'conflict'
            ? Listing::orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.ezee.assignment_log', compact('logs', 'ezeeMap', 'method', 'counts', 'bookingMap', 'listings'));
    }
}
