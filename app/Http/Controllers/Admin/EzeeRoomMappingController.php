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
        // Conflicts are the only rows that need a person, so they get their own
        // filter rather than being buried among the successful assignments.
        $method = $request->input('method');

        $logs = EzeeAssignmentLog::with(['listing', 'assignedBy'])
            ->when($method, fn ($q) => $q->where('method', $method))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $counts = EzeeAssignmentLog::selectRaw('method, COUNT(*) c')
            ->groupBy('method')
            ->pluck('c', 'method');

        $ezeeIds = $logs->pluck('ezee_booking_id')->unique();
        $ezeeMap = EzeeBooking::whereIn('id', $ezeeIds)
            ->get(['id', 'FirstName', 'LastName', 'RoomName', 'RoomTypeName', 'Start', 'End'])
            ->keyBy('id');

        return view('admin.ezee.assignment_log', compact('logs', 'ezeeMap', 'method', 'counts'));
    }
}
