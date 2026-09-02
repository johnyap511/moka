@extends('admin.layout')
@section('title', 'Assignment Log')
@section('page-title', 'Assignment Log')

@section('content')
<div class="page-header">
    <div>
        <h1>Assignment Log</h1>
        <p>Audit trail for all EZEE booking assignments and reassignments</p>
    </div>
    <a href="/admin/ezee/room-mapping" class="btn btn-secondary">← Back to Room Mapping</a>
</div>

{{-- Conflicts are the rows that need a person; everything else is a record of
     what already happened. --}}
@php
    $tabs = [
        null        => 'All',
        'conflict'  => 'Needs review',
        'modified'  => 'Changed in eZee',
        'auto'      => 'Auto-assigned',
        'move'      => 'Room moves',
        'manual'    => 'Manual',
        'reassign'  => 'Reassigned',
    ];
@endphp
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
    @foreach($tabs as $value => $label)
        @php $count = $value ? ($counts[$value] ?? 0) : $counts->sum(); @endphp
        <a href="{{ route('admin.ezee.assignment-log', $value ? ['method' => $value] : []) }}"
           class="btn {{ $method === $value ? 'btn-primary' : 'btn-secondary' }}"
           style="padding:5px 14px;font-size:12px{{ $value === 'conflict' && $count > 0 && $method !== 'conflict' ? ';border-color:#f59e0b;color:#b45309' : '' }}">
            {{ $label }} ({{ $count }})
        </a>
    @endforeach
</div>

@if($method === 'conflict')
<div class="alert" style="margin-bottom:16px;padding:12px 14px;background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;font-size:13px">
    These bookings could not be assigned because the unit was already occupied over those dates.
    Nothing was changed — the existing assignment was left alone. Resolve each in EZEE or reassign
    manually from EZEE Bookings.
</div>
@endif

<div class="card">
    <div class="card-header">
        <h2>All Assignments</h2>
        @if(method_exists($logs, 'total'))
        <span class="text-sm text-secondary">{{ number_format($logs->total()) }} records</span>
        @endif
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Guest</th>
                    <th>Room Unit</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Assigned To</th>
                    <th>Old Listing</th>
                    <th>Method</th>
                    <th>By</th>
                    <th>Date</th>
                    <th style="width:210px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                    $eb = $ezeeMap[$log->ezee_booking_id] ?? null;
                    $methodColors = ['auto'=>'badge-blue','manual'=>'badge-teal','reassign'=>'badge-orange'];
                @endphp
                <tr>
                    <td class="mono">#{{ $log->ezee_booking_id }}</td>
                    <td>{{ $eb ? $eb->FirstName.' '.$eb->LastName : '—' }}</td>
                    <td>{{ $eb->RoomName ?? ($eb->RoomTypeName ?? '—') }}</td>
                    <td>{{ $eb->Start ?? '—' }}</td>
                    <td>{{ $eb->End ?? '—' }}</td>
                    <td>{{ $log->listing->name ?? '—' }}</td>
                    <td>
                        @if($log->old_listing_id)
                            @php $oldListing = \App\Listing::withArchived()->find($log->old_listing_id); @endphp
                            <span style="color:var(--text-secondary)">{{ $oldListing->name ?? '#'.$log->old_listing_id }}</span>
                        @else
                            <span style="color:var(--text-secondary)">—</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $methodColors[$log->method] ?? 'badge-gray' }}">{{ ucfirst($log->method) }}</span></td>
                    <td>{{ $log->assignedBy->name ?? 'System' }}</td>
                    <td style="white-space:nowrap">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i') }}</td>
                    <td>
                        @if($log->method === 'conflict')
                            <div style="display:flex;gap:4px;flex-wrap:wrap">
                                {{-- Reassign and Edit reuse the screens the team
                                     already works in, rather than new ones. --}}
                                <a href="/admin/ezee/booking?q={{ urlencode($eb->SubBookingId ?? '') }}"
                                   class="btn btn-secondary btn-sm" title="Find this booking on EZEE Bookings to reassign it">Reassign</a>
                                <a href="{{ route('admin.ezee.booking.edit', $log->ezee_booking_id) }}"
                                   class="btn btn-secondary btn-sm">Edit</a>
                                @if($log->resolved_at)
                                    <button type="button" class="btn btn-secondary btn-sm"
                                            onclick="setResolved(this, {{ $log->id }}, false)">Reopen</button>
                                @else
                                    <button type="button" class="btn btn-primary btn-sm"
                                            onclick="setResolved(this, {{ $log->id }}, true)">Mark done</button>
                                @endif
                            </div>
                            @if($log->resolved_at)
                                <div style="font-size:11px;color:var(--text-secondary);margin-top:4px">
                                    Done {{ \Carbon\Carbon::parse($log->resolved_at)->format('d M H:i') }}
                                </div>
                            @endif
                        @else
                            <span style="color:var(--text-secondary)">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" style="text-align:center;padding:40px;color:var(--text-secondary)">No assignments logged yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($logs, 'lastPage') && $logs->lastPage() > 1)
    <div class="card-body" style="padding-top:0;display:flex;align-items:center;justify-content:space-between;gap:12px">
        <span class="text-sm text-secondary">Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }}</span>
        <div style="display:flex;gap:6px">
            @if(!$logs->onFirstPage())
                <a href="{{ $logs->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Prev</a>
            @endif
            @if($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Resolving records that a person has dealt with a conflict. It changes no
// booking; it only takes the row off the queue, and can be reopened.
async function setResolved(btn, logId, resolved) {
    var note = null;

    if (resolved) {
        note = prompt('Mark this conflict as done?\n\nOptional note (what you did):', '');
        if (note === null) { return; }
    }

    btn.disabled = true;
    btn.textContent = resolved ? 'Saving…' : 'Reopening…';

    try {
        const res = await fetch('/admin/ezee/assignment-log/' + logId + '/resolve', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ resolved: resolved, note: note }),
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) { throw new Error(data.message || 'Request failed'); }

        // On the Needs review tab a resolved row no longer belongs; elsewhere
        // reload so the state and counts are consistent.
        if (resolved && window.location.search.indexOf('method=conflict') !== -1) {
            btn.closest('tr').remove();
        } else {
            window.location.reload();
        }
    } catch (e) {
        alert('Could not update: ' + e.message);
        btn.disabled = false;
        btn.textContent = resolved ? 'Mark done' : 'Reopen';
    }
}
</script>
@endpush
