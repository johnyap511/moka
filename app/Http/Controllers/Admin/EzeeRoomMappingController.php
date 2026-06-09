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
        $groups   = EzeeGroup::orderBy('name')->get()->keyBy('id');
        $listings = Listing::orderBy('name')->get();

        // All distinct (ezee_group_id, RoomTypeName) combos from imported bookings
        $rooms = EzeeBooking::select('ezee_group_id', 'RoomTypeName')
            ->whereNotNull('RoomTypeName')
            ->where('RoomTypeName', '!=', '')
            ->whereNotNull('ezee_group_id')
            ->distinct()
            ->orderBy('ezee_group_id')
            ->orderBy('RoomTypeName')
            ->get();

        // Existing mappings keyed by "groupId||roomTypeName"
        $mappings = EzeeRoomMapping::all()
            ->keyBy(fn($m) => $m->ezee_group_id . '||' . $m->room_type_name);

        // Booking counts per room type per group
        $stats = EzeeBooking::select(
                'ezee_group_id', 'RoomTypeName',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN book_id IS NOT NULL THEN 1 ELSE 0 END) as assigned')
            )
            ->whereNotNull('RoomTypeName')
            ->whereNotNull('ezee_group_id')
            ->groupBy('ezee_group_id', 'RoomTypeName')
            ->get()
            ->keyBy(fn($r) => $r->ezee_group_id . '||' . $r->RoomTypeName);

        return view('admin.ezee.room_mapping', compact('groups', 'listings', 'rooms', 'mappings', 'stats'));
    }

    public function saveAll(Request $request)
    {
        $data  = $request->input('mappings', []);
        $saved = 0;

        foreach ($data as $key => $listingId) {
            [$groupId, $roomTypeName] = explode('||', $key, 2);
            if (empty($roomTypeName)) continue;

            EzeeRoomMapping::updateOrCreate(
                ['ezee_group_id' => $groupId, 'room_type_name' => $roomTypeName],
                ['listing_id' => $listingId ?: null]
            );
            $saved++;
        }

        return back()->with('success', "Saved {$saved} room mappings.");
    }

    public function autoAssign(Request $request)
    {
        $mappings = EzeeRoomMapping::whereNotNull('listing_id')->get()
            ->keyBy(fn($m) => $m->ezee_group_id . '||' . $m->room_type_name);

        $assigned = 0;
        $skipped  = 0;

        $unassigned = EzeeBooking::whereNull('book_id')
            ->whereNotNull('RoomTypeName')
            ->whereNotNull('ezee_group_id')
            ->get();

        foreach ($unassigned as $eb) {
            $key     = $eb->ezee_group_id . '||' . $eb->RoomTypeName;
            $mapping = $mappings[$key] ?? null;

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
                $oldListingId            = $existing->listing_id;
                $existing->listing_id    = $request->listing_id;
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

        // Attach ezee booking guest names
        $ezeeIds = $logs->pluck('ezee_booking_id')->unique();
        $ezeeMap = EzeeBooking::whereIn('id', $ezeeIds)
            ->get(['id', 'FirstName', 'LastName', 'RoomTypeName', 'Start', 'End'])
            ->keyBy('id');

        return view('admin.ezee.assignment_log', compact('logs', 'ezeeMap'));
    }
}
