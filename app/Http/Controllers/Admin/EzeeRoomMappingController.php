<?php

namespace App\Http\Controllers\Admin;

use App\EzeeAssignmentLog;
use App\Booking;
use App\EzeeGroup;
use App\EzeeRoom;
use App\EzeeRoomMapping;
use App\Http\Controllers\Controller;
use App\Listing;
use App\OtherModel\EzeeBooking;
use App\Role;
use App\Support\EzeePricing;
use App\Support\EzeeUnitMap;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EzeeRoomMappingController extends Controller
{
    public function index()
    {
        $listings = Listing::orderBy('name')->get();

        // Every unit EZEE holds for these properties, from ezee_rooms (filled by
        // `ezee:sync-rooms`). Listing only the units seen in bookings — which is
        // what this page used to do — hid any unit nobody had stayed in yet, so
        // those could never be mapped ahead of their first booking.
        $rooms = EzeeRoom::orderBy('room_name')
            ->get()
            ->map(function ($room) {
                return (object) [
                    'RoomName'     => $room->room_name,
                    'RoomTypeName' => $room->room_type_name,
                ];
            });

        // Anything seen in a booking but absent from the inventory still belongs
        // here: it keeps the page working before the first sync, and surfaces
        // units EZEE has since retired but which still carry bookings.
        $known = $rooms->pluck('RoomName')->map(fn ($n) => strtolower(trim($n)))->flip();

        $fromBookings = EzeeBooking::select('RoomName', 'RoomTypeName')
            ->whereNotNull('RoomName')
            ->where('RoomName', '!=', '')
            ->distinct()
            ->get()
            ->reject(fn ($r) => $known->has(strtolower(trim($r->RoomName))));

        $rooms = $rooms->concat($fromBookings)->sortBy('RoomName')->values();

        // Existing mappings keyed by room_name
        $mappings = EzeeRoomMapping::all()->keyBy('room_name');

        // Booking counts per room name
        $stats = EzeeBooking::select(
                'RoomName',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN book_id IS NOT NULL THEN 1 ELSE 0 END) as assigned')
            )
            ->whereNotNull('RoomName')
            ->where('RoomName', '!=', '')
            ->groupBy('RoomName')
            ->get()
            ->keyBy('RoomName');

        // Build auto-match suggestions: compare RoomName against listing names
        $listingMap = $listings->keyBy('name');
        $suggestions = [];
        foreach ($rooms as $room) {
            $roomName = $room->RoomName;
            if (isset($mappings[$roomName]) && $mappings[$roomName]->listing_id) continue;
            if (isset($listingMap[$roomName])) {
                $suggestions[$roomName] = $listingMap[$roomName]->id;
                continue;
            }
            $match = $listings->first(fn($l) => strtolower($l->name) === strtolower($roomName));
            if ($match) {
                $suggestions[$roomName] = $match->id;
            }
        }

        return view('admin.ezee.room_mapping', compact('listings', 'rooms', 'mappings', 'stats', 'suggestions'));
    }

    public function saveAll(Request $request)
    {
        $data  = $request->input('mappings', []);
        $saved = 0;

        foreach ($data as $roomName => $listingId) {
            if (empty($roomName)) continue;

            EzeeRoomMapping::updateOrCreate(
                ['room_name' => $roomName],
                ['listing_id' => $listingId ?: null, 'ezee_group_id' => null]
            );
            $saved++;
        }

        return back()->with('success', "Saved {$saved} room mappings.");
    }

    /**
     * Assign EZEE bookings to listings by matching EZEE's unit id.
     *
     * EZEE sends the unit on every booking as eZeePMSRoomid (e.g. "C2-07-10"),
     * stored here as RoomName. A listing carrying the same value in
     * ezee_room_id is that unit, so the pairing is exact rather than inferred.
     *
     * Bookings are created through EzeePricing so the fee breakdown matches what
     * the EZEE list previews and what a manual assignment would store. An
     * earlier version wrote only listing_id, dates and a raw total, which left
     * every generated booking with no SST or marketing fee.
     *
     * Defaults to stays that have not yet ended; assigning historical stays
     * creates owner-report entries for periods already settled. Pass dry_run to
     * preview.
     */
    public function autoAssign(Request $request)
    {
        set_time_limit(0);

        $dryRun = $request->boolean('dry_run');
        $from   = $request->input('from', date('Y-m-d'));
        $to     = $request->input('to');

        // Reads the mappings saved on this screen, not just listings.ezee_room_id —
        // see EzeeUnitMap for why both exist.
        $listings = EzeeUnitMap::build();

        if ($listings->isEmpty()) {
            return response()->json([
                'ok'      => false,
                'message' => 'No unit is mapped to a listing yet. Map some rooms on this page and save, then try again.',
            ], 422);
        }

        $query = EzeeBooking::query()
            ->where(function ($q) {
                $q->whereNull('book_id')->orWhere('book_id', 0);
            })
            ->whereNotNull('RoomName')
            ->where('RoomName', '!=', '')
            ->where('End', '>=', $from);

        if ($to) {
            $query->where('Start', '<=', $to);
        }

        $assigned = 0;
        $noListing = 0;
        $conflicts = [];
        $details   = [];

        foreach ($query->orderBy('Start')->get() as $eb) {
            $listing = $listings[EzeeUnitMap::key($eb->RoomName)] ?? null;

            if (!$listing) {
                $noListing++;
                continue;
            }

            // Same overlap rule the manual assign uses: an existing booking
            // blocks the unit when it starts before this one ends and ends
            // after it starts. Cancelled bookings (status 1) do not block.
            $clash = Booking::where('listing_id', $listing->id)
                ->where('status', '!=', 1)
                ->whereDate('check_in', '<', $eb->End)
                ->whereDate('check_out', '>', $eb->Start)
                ->first();

            if ($clash) {
                $conflicts[] = [
                    'sub_booking_id' => $eb->SubBookingId,
                    'room'           => $eb->RoomName,
                    'listing'        => $listing->name,
                    'dates'          => $eb->Start . ' → ' . $eb->End,
                    'clashes_with'   => $clash->id,
                ];
                continue;
            }

            $breakdown = EzeePricing::breakdown($eb);

            $details[] = [
                'sub_booking_id' => $eb->SubBookingId,
                'room'           => $eb->RoomName,
                'listing'        => $listing->name,
                'dates'          => $eb->Start . ' → ' . $eb->End,
                'total'          => round($breakdown['total'], 2),
            ];

            if ($dryRun) {
                $assigned++;
                continue;
            }

            DB::transaction(function () use ($eb, $listing, $breakdown, &$assigned) {
                $user = User::create([
                    'name'       => $eb->FirstName,
                    'last_name'  => $eb->LastName,
                    'phone'      => $eb->Mobile,
                    'email'      => $eb->Email,
                    'ezee_tmp'   => 1,
                ]);
                if ($role = Role::find(2)) {
                    $user->attachRole($role);
                }

                $booking = Booking::create([
                    'listing_id'   => $listing->id,
                    'user_id'      => $user->id,
                    'folio_no'     => $eb->folio_no ?: 'FN' . substr((string) $eb->TransactionId, -4),
                    'check_in'     => $eb->Start,
                    'check_out'    => $eb->End,
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
                    'discount_fee' => $eb->TotalDiscount ?? 0,
                    'source'       => preg_replace('/[^A-Za-z\. ]/', '', (string) $eb->Source),
                    'status'       => 5,
                    'remark'       => 'Auto-assigned from EZEE room ' . $eb->RoomName,
                ]);

                // status 8 marks the EZEE record assigned; without it the list
                // still shows "Unassigned" despite book_id being set.
                EzeeBooking::where('id', $eb->id)->update([
                    'book_id' => $booking->id,
                    'status'  => 8,
                ]);

                EzeeAssignmentLog::create([
                    'ezee_booking_id' => $eb->id,
                    'listing_id'      => $listing->id,
                    'old_listing_id'  => null,
                    'assigned_by'     => Auth::id(),
                    'method'          => 'auto',
                    'note'            => 'Matched on EZEE room id ' . $eb->RoomName,
                ]);

                $assigned++;
            });
        }

        return response()->json([
            'ok'         => true,
            'dry_run'    => $dryRun,
            'assigned'   => $assigned,
            'no_listing' => $noListing,
            'conflicts'  => count($conflicts),
            'message'    => ($dryRun ? 'Would assign ' : 'Assigned ') . $assigned
                            . " booking(s). {$noListing} had no listing for their room id, "
                            . count($conflicts) . ' clashed with an existing booking.',
            'conflict_detail' => array_slice($conflicts, 0, 50),
            'detail'          => array_slice($details, 0, 50),
        ]);
    }

    public function reassign(Request $request, $ezeeBookingId)
    {
        $request->validate(['listing_id' => 'required|exists:listings,id']);

        $eb           = EzeeBooking::findOrFail($ezeeBookingId);
        $oldListingId = null;

        if ($eb->book_id) {
            $existing = \App\Booking::find($eb->book_id);
            if ($existing) {
                $oldListingId         = $existing->listing_id;
                $existing->listing_id = $request->listing_id;
                $existing->save();
            }
        } else {
            $booking     = \App\Booking::create([
                'listing_id' => $request->listing_id,
                'name'       => trim($eb->FirstName . ' ' . $eb->LastName),
                'email'      => $eb->Email,
                'check_in'   => $eb->Start,
                'check_out'  => $eb->End,
                'price'      => $eb->TotalAmountAfterTax,
                'status'     => 5,
                'source'     => $eb->Source ?? 'EZEE',
            ]);
            $eb->book_id = $booking->id;
            $eb->save();
        }

        EzeeAssignmentLog::create([
            'ezee_booking_id' => $eb->id,
            'listing_id'      => $request->listing_id,
            'old_listing_id'  => $oldListingId,
            'assigned_by'     => Auth::id(),
            'method'          => $oldListingId ? 'reassign' : 'manual',
            'note'            => $request->note,
        ]);

        return response()->json(['ok' => true, 'message' => 'Booking reassigned successfully.']);
    }

    public function auditLog(Request $request)
    {
        $logs = EzeeAssignmentLog::with(['listing', 'assignedBy'])
            ->orderByDesc('created_at')
            ->paginate(50);

        $ezeeIds = $logs->pluck('ezee_booking_id')->unique();
        $ezeeMap = EzeeBooking::whereIn('id', $ezeeIds)
            ->get(['id', 'FirstName', 'LastName', 'RoomName', 'RoomTypeName', 'Start', 'End'])
            ->keyBy('id');

        return view('admin.ezee.assignment_log', compact('logs', 'ezeeMap'));
    }
}
