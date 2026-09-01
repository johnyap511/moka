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
use App\Support\EzeeAutoAssign;
use App\Support\EzeeUnitMap;
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

        // distinct() here covers the RoomName/RoomTypeName pair, so a unit EZEE
        // has recorded under more than one room type came back once per type and
        // was listed twice — "Extra Room 1" appeared seven times. A unit must
        // appear once, or it can be mapped twice to different listings.
        $fromBookings = EzeeBooking::select('RoomName', 'RoomTypeName')
            ->whereNotNull('RoomName')
            ->where('RoomName', '!=', '')
            ->distinct()
            ->get()
            ->reject(fn ($r) => $known->has(strtolower(trim($r->RoomName))))
            ->unique(fn ($r) => strtolower(trim($r->RoomName)))
            ->values();

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
