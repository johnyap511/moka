<?php

namespace App\Http\Controllers\Admin;

use App\EzeeAssignmentLog;
use App\EzeeGroup;
use App\EzeeRoomMapping;
use App\Http\Controllers\Controller;
use App\Listing;
use App\OtherModel\EzeeBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EzeeRoomMappingController extends Controller
{
    public function index()
    {
        $listings = Listing::orderBy('name')->get();

        // All distinct RoomTypeName values (room-specific names from EZEE)
        $rooms = EzeeBooking::select('RoomTypeName')
            ->whereNotNull('RoomTypeName')
            ->where('RoomTypeName', '!=', '')
            ->distinct()
            ->orderBy('RoomTypeName')
            ->pluck('RoomTypeName');

        // Existing mappings keyed by room_type_name
        $mappings = EzeeRoomMapping::all()->keyBy('room_type_name');

        // Booking counts per room type
        $stats = EzeeBooking::select(
                'RoomTypeName',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN book_id IS NOT NULL THEN 1 ELSE 0 END) as assigned')
            )
            ->whereNotNull('RoomTypeName')
            ->where('RoomTypeName', '!=', '')
            ->groupBy('RoomTypeName')
            ->get()
            ->keyBy('RoomTypeName');

        // Build auto-match suggestions: compare RoomTypeName against listing names
        $listingMap = $listings->keyBy('name');
        $suggestions = [];
        foreach ($rooms as $roomTypeName) {
            if (isset($mappings[$roomTypeName])) continue; // already mapped
            // Exact match
            if (isset($listingMap[$roomTypeName])) {
                $suggestions[$roomTypeName] = $listingMap[$roomTypeName]->id;
                continue;
            }
            // Case-insensitive match
            $match = $listings->first(fn($l) => strtolower($l->name) === strtolower($roomTypeName));
            if ($match) {
                $suggestions[$roomTypeName] = $match->id;
            }
        }

        return view('admin.ezee.room_mapping', compact('listings', 'rooms', 'mappings', 'stats', 'suggestions'));
    }

    public function saveAll(Request $request)
    {
        $data  = $request->input('mappings', []);
        $saved = 0;

        foreach ($data as $roomTypeName => $listingId) {
            if (empty($roomTypeName)) continue;

            EzeeRoomMapping::updateOrCreate(
                ['room_type_name' => $roomTypeName],
                ['listing_id' => $listingId ?: null, 'ezee_group_id' => null]
            );
            $saved++;
        }

        return back()->with('success', "Saved {$saved} room mappings.");
    }

    public function autoAssign(Request $request)
    {
        $mappings = EzeeRoomMapping::whereNotNull('listing_id')->get()->keyBy('room_type_name');

        $assigned = 0;
        $skipped  = 0;

        $unassigned = EzeeBooking::whereNull('book_id')
            ->whereNotNull('RoomTypeName')
            ->where('RoomTypeName', '!=', '')
            ->get();

        foreach ($unassigned as $eb) {
            $mapping = $mappings[$eb->RoomTypeName] ?? null;
            if (!$mapping) { $skipped++; continue; }

            $booking = \App\Booking::create([
                'listing_id' => $mapping->listing_id,
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

            EzeeAssignmentLog::create([
                'ezee_booking_id' => $eb->id,
                'listing_id'      => $mapping->listing_id,
                'old_listing_id'  => null,
                'assigned_by'     => Auth::id(),
                'method'          => 'auto',
                'note'            => 'Bulk auto-assign via room type mapping',
            ]);

            $assigned++;
        }

        return response()->json([
            'ok'       => true,
            'assigned' => $assigned,
            'skipped'  => $skipped,
            'message'  => "Auto-assigned {$assigned} bookings. {$skipped} had no mapping.",
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
            ->get(['id', 'FirstName', 'LastName', 'RoomTypeName', 'Start', 'End'])
            ->keyBy('id');

        return view('admin.ezee.assignment_log', compact('logs', 'ezeeMap'));
    }
}
