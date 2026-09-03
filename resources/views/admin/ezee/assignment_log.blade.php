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
    These reservations could not be assigned because the unit was already occupied over those nights.
    Nothing was changed. Read the room history on the EZEE calendar, name the pattern, then use the
    matching control: <b>Room history</b> when the first nights were in another unit or an extra room,
    <b>Accept EZEE dates</b> when the stay was shortened or extended, <b>No unit</b> for an extra-guest
    room, <b>Reassign</b> to move the whole booking. Nothing is typed by hand.
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
                    {{-- The reservation number is what staff search eZee by. --}}
                    <th>Reservation</th>
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
                    <td class="text-nowrap"><code>{{ $eb->SubBookingId ?? '—' }}</code></td>
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
                            @php
                                $ours = $eb && $eb->book_id ? ($bookingMap[$eb->book_id] ?? null) : null;
                                $blockId = preg_match_all('/[Bb]ooking #(\d+)/', (string) $log->note, $bm) ? (int) end($bm[1]) : null;
                                $blocker = $blockId ? ($bookingMap[$blockId] ?? null) : null;
                            @endphp
                            <div style="font-size:11px;color:var(--text-secondary);margin-bottom:6px;line-height:1.5">
                                @if($ours)
                                    Ours: {{ $ours->check_in }} → {{ $ours->check_out }} on {{ $ours->listing->name ?? '#'.$ours->listing_id }}
                                    @if($ours->check_in != $eb->Start || $ours->check_out != $eb->End)
                                        <span style="color:#b45309">(EZEE says {{ $eb->Start }} → {{ $eb->End }})</span>
                                    @endif
                                    <br>
                                @else
                                    Not assigned yet.<br>
                                @endif
                                @if($blocker)
                                    Blocked by #{{ $blocker->id }}: {{ $blocker->check_in }} → {{ $blocker->check_out }}, {{ $blocker->source }}{{ $blocker->status == 1 ? ' (cancelled)' : '' }}
                                @endif
                            </div>
                            @if(!$log->resolved_at)
                            @php $linkDead = $eb && $eb->book_id && (!$ours || (int) $ours->status === 1); @endphp
                            <div style="display:flex;gap:4px;flex-wrap:wrap">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="togglePanel('hist-{{ $log->id }}')" title="Some nights were in another unit or an extra room">Room history</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="acceptDates(this, {{ $log->ezee_booking_id }}, '{{ $eb->Start }}', '{{ $eb->End }}')" title="Move our dates to EZEE's; the stamped rate stands">Accept EZEE dates</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="noUnit(this, {{ $log->ezee_booking_id }})" title="Extra-guest room, needs no unit">No unit</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="togglePanel('reassign-{{ $log->id }}')" title="Move the whole booking to another unit">Reassign</button>
                                @if($linkDead)
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="restoreBooking(this, {{ $log->ezee_booking_id }})" title="Bring back the cancelled booking EZEE still reports">Restore</button>
                                @endif
                                <button type="button" class="btn btn-primary btn-sm" onclick="setResolved(this, {{ $log->id }}, true)">Mark done</button>
                            </div>
                            <div id="hist-{{ $log->id }}" class="review-panel" style="display:none">
                                <div class="review-panel-title">Nights the guest was <b>not</b> in {{ $eb->RoomName }}</div>
                                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:6px">
                                    <input type="date" id="hist-from-{{ $log->id }}" value="{{ $eb->Start }}" min="{{ $eb->Start }}" max="{{ $eb->End }}">
                                    <span>to</span>
                                    <input type="date" id="hist-to-{{ $log->id }}" value="{{ \Carbon\Carbon::parse($eb->Start)->addDay()->format('Y-m-d') }}" min="{{ $eb->Start }}" max="{{ $eb->End }}">
                                </div>
                                <div class="review-panel-title">Where the guest was on those nights</div>
                                <select id="hist-unit-{{ $log->id }}" style="max-width:240px;margin-bottom:8px">
                                    <option value="">Extra room (no unit)</option>
                                    @foreach($listings as $l)
                                        <option value="{{ $l->id }}">{{ $l->name }}</option>
                                    @endforeach
                                </select>
                                <div style="display:flex;gap:4px">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="assignHistory(this, {{ $log->id }}, {{ $log->ezee_booking_id }})">Assign</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="togglePanel('hist-{{ $log->id }}')">Cancel</button>
                                </div>
                            </div>
                            <div id="reassign-{{ $log->id }}" class="review-panel" style="display:none">
                                <div class="review-panel-title">Move the whole stay to</div>
                                <select id="reassign-unit-{{ $log->id }}" style="max-width:240px;margin-bottom:8px">
                                    @foreach($listings as $l)
                                        <option value="{{ $l->id }}">{{ $l->name }}</option>
                                    @endforeach
                                </select>
                                <div style="display:flex;gap:4px">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="reassignTo(this, {{ $log->id }}, {{ $log->ezee_booking_id }})">Reassign</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="togglePanel('reassign-{{ $log->id }}')">Cancel</button>
                                </div>
                            </div>
                            @else
                                <div style="display:flex;gap:4px;flex-wrap:wrap">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="setResolved(this, {{ $log->id }}, false)">Reopen</button>
                                </div>
                                <div style="font-size:11px;color:var(--text-secondary);margin-top:4px">
                                    Done {{ \Carbon\Carbon::parse($log->resolved_at)->format('d M H:i') }}{{ $log->resolution_note ? ' — '.$log->resolution_note : '' }}
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
<style>
.review-panel { margin-top:8px; padding:10px; border:1px solid var(--border, #e5e7eb); border-radius:6px; background:var(--bg-secondary, #f9fafb); font-size:12px; }
.review-panel-title { font-size:11px; margin-bottom:6px; }
.review-panel input, .review-panel select { font-size:12px; padding:4px 6px; }
</style>
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

function togglePanel(id) {
    var el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

// Every control posts, shows the server's own sentence, and takes the row off
// the queue. Nothing here computes a price or a date: the server does, from
// EZEE's amounts, so what staff click cannot differ from what the automatic
// path would have written.
async function postAction(btn, url, body, doneLabel) {
    var label = btn.textContent;
    btn.disabled = true; btn.textContent = 'Working…';
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify(body || {}),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) { throw new Error(data.message || 'Request failed'); }
        alert(data.message || doneLabel);
        window.location.reload();
    } catch (e) {
        alert('Not done: ' + e.message);
        btn.disabled = false; btn.textContent = label;
    }
}

function assignHistory(btn, logId, ezeeId) {
    var from = document.getElementById('hist-from-' + logId).value;
    var to   = document.getElementById('hist-to-' + logId).value;
    var unit = document.getElementById('hist-unit-' + logId).value;
    if (!from || !to || to <= from) { alert('Pick the nights the guest was elsewhere: the first night and the morning they moved back.'); return; }
    postAction(btn, '/admin/ezee/booking/' + ezeeId + '/assign-history', { from: from, to: to, other_listing_id: unit || null }, 'Assigned.');
}

function reassignTo(btn, logId, ezeeId) {
    var unit = document.getElementById('reassign-unit-' + logId).value;
    if (!unit) { alert('Pick the unit.'); return; }
    postAction(btn, '/admin/ezee/booking/' + ezeeId + '/reassign', { listing_id: unit, note: 'Reassigned from the review row' }, 'Reassigned.');
}

function restoreBooking(btn, ezeeId) {
    if (!confirm('Restore the cancelled booking? EZEE still reports this stay.')) { return; }
    postAction(btn, '/admin/ezee/booking/' + ezeeId + '/restore', {}, 'Restored.');
}

function noUnit(btn, ezeeId) {
    var note = prompt('Mark this reservation as needing no unit (extra-guest room)?\n\nWhat the EZEE calendar shows:', '');
    if (note === null) { return; }
    postAction(btn, '/admin/ezee/booking/' + ezeeId + '/no-unit', { note: note }, 'Marked.');
}

function acceptDates(btn, ezeeId, start, end) {
    if (!confirm('Move our dates to EZEE\'s (' + start + ' to ' + end + ')?\n\nThe nightly rate stays as stamped; the amount follows the nights. Segments outside the new dates are cancelled.')) { return; }
    postAction(btn, '/admin/ezee/booking/' + ezeeId + '/accept-dates', {}, 'Dates updated.');
}
</script>
@endpush
