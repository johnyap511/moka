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
        'cancelled' => 'Cancelled in eZee',
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
<div class="rv-help">
    <div class="rv-help__lead"><b>How to use this page.</b> Each box below is one stay the system could not sort out by itself, and <b>nothing has been changed yet</b>. Read “What to do”, check the stay in eZee, then press the green button. If you are not sure, leave it and ask.</div>
    <details class="rv-help__more"><summary>What does each button do?</summary>
    <div class="rv-help__grid">
        <div><b>Accept eZee dates</b><span>The stay was shortened, extended or moved to other dates in eZee</span></div>
        <div><b>Move to another unit</b><span>The whole stay belongs in a different unit</span></div>
        <div><b>Some nights elsewhere</b><span>Part of the stay was in another unit or an extra room</span></div>
        <div><b>Mark as duplicate</b><span>The same stay was keyed twice: cancels the extra copy, keeps the other</span></div>
        <div><b>Cancelled in eZee</b><span>You checked eZee and it is voided or cancelled there: cancels it here too</span></div>
        <div><b>Needs no unit</b><span>An extra-guest “room” that needs no unit</span></div>
        <div><b>Mark done</b><span>Already sorted out by hand; only takes it off this list</span></div>
    </div>
    </details>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h2>All Assignments</h2>
        @if(method_exists($logs, 'total'))
        <span class="text-sm text-secondary">{{ number_format($logs->total()) }} records</span>
        @endif
    </div>
    <div class="table-wrap wide {{ $method === 'conflict' ? 'rv-mode' : '' }}">
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
                    <th class="rv-actions-h">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                    $eb = $ezeeMap[$log->ezee_booking_id] ?? null;
                    $methodColors = ['auto'=>'badge-blue','manual'=>'badge-teal','reassign'=>'badge-orange','cancelled'=>'badge-red'];
                @endphp
                <tr>
                    <td class="mono">#{{ $log->ezee_booking_id }}</td>
                    <td class="text-nowrap"><code>{{ $eb->SubBookingId ?? '—' }}</code></td>
                    <td>{{ $eb ? $eb->FirstName.' '.$eb->LastName : '—' }}</td>
                    <td>{{ $eb->RoomName ?? ($eb->RoomTypeName ?? '—') }}</td>
                    <td class="text-nowrap">{{ $eb && $eb->Start ? \Carbon\Carbon::parse($eb->Start)->format('j M Y') : '—' }}</td>
                    <td class="text-nowrap">{{ $eb && $eb->End ? \Carbon\Carbon::parse($eb->End)->format('j M Y') : '—' }}</td>
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
                            @php
                                $note = (string) $log->note;
                                $fmt  = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M') : '—';
                                $datesDiffer = $ours && ($ours->check_in != $eb->Start || $ours->check_out != $eb->End);
                                if (str_starts_with($note, 'Possible duplicate'))        { $kind = 'dup'; }
                                elseif (str_starts_with($note, 'Room swap applied'))     { $kind = 'swap'; }
                                elseif (str_starts_with($note, 'Dates changed in EZEE')) { $kind = 'dates'; }
                                elseif (str_starts_with($note, 'EZEE cancelled'))        { $kind = 'cancelled'; }
                                elseif (str_starts_with($note, 'EZEE now charges'))      { $kind = 'amounts'; }
                                elseif (stripos($note, 'Extra Room') !== false && stripos($note, 'split') !== false) { $kind = 'extra'; }
                                elseif (stripos($note, 'already occupies') !== false || str_starts_with($note, 'Could not')) { $kind = 'clash'; }
                                else { $kind = 'other'; }
                                $kinds = [
                                    'clash'     => ['Two stays want the same unit', '#b45309', 'Open eZee and see who really has this unit on these nights. Usually one guest was moved to another room, or the dates changed. Fix the one that is wrong.', $datesDiffer ? ['dates', 'reassign'] : ['reassign']],
                                    'dates'     => ['eZee changed the dates', '#1d4ed8', 'Check the stay in eZee. If the new dates are right, accept them.', ['dates']],
                                    'swap'      => ['Rooms were swapped in eZee (already done here)', '#047857', 'Nothing to fix. Look at both guests in eZee, and if the rooms match, mark it done.', ['done']],
                                    'cancelled' => ['Cancelled in eZee, still live here', '#b91c1c', 'Check eZee. If it really is cancelled there, cancel it here too.', ['voided']],
                                    'amounts'   => ['The amount differs from eZee', '#1d4ed8', 'Check the folio in eZee. If eZee is right, use its amounts.', ['amounts']],
                                    'extra'     => ['Guest spent nights in an extra room', '#7c3aed', 'Read Room Charges in eZee, then put the extra-room nights on the extra room and the unit nights on the unit.', ['history']],
                                    'dup'       => ['Possible duplicate booking', '#b91c1c', 'Open both bookings. If they are the same stay keyed twice, mark the extra copy as duplicate. If they are two parts of one stay, mark it done.', ['duplicate']],
                                    'other'     => ['Needs a person to check', '#475569', 'Read the system note below and check the stay in eZee.', []],
                                ];
                                [$kTitle, $kColor, $kDo, $kPrimary] = $kinds[$kind];
                                // The two bookings a duplicate is chosen from: the ones the note names, else ours and the blocker.
                                preg_match_all('/(?:[Bb]ooking |and )#(\d+)/', $note, $nm);
                                $pair = collect(array_map('intval', $nm[1]))->push($ours->id ?? null)->filter()->unique()->map(fn ($id) => $bookingMap[$id] ?? null)->filter()->filter(fn ($b) => (int) $b->status !== 1)->take(2)->values();
                            @endphp
                            <div class="rv-card" style="border-left-color:{{ $kColor }}">
                                <div class="rv-card__title" style="color:{{ $kColor }}">{{ $kTitle }}</div>
                                <div class="rv-facts">
                                    <div><span>eZee says</span><b>{{ $eb->RoomName ?: 'no room yet' }}</b> · {{ $fmt($eb->Start) }} → {{ $fmt($eb->End) }}</div>
                                    <div><span>Homemoka has</span>
                                        @if($ours)
                                            <a href="/admin/book/{{ $ours->id }}/edit#unit-card">#{{ $ours->id }}</a> · <b>{{ $ours->listing->name ?? '#'.$ours->listing_id }}</b> · {{ $fmt($ours->check_in) }} → {{ $fmt($ours->check_out) }}{{ (int) $ours->status === 1 ? ' (cancelled)' : '' }}
                                            @if($datesDiffer)<em class="rv-flag">dates differ</em>@endif
                                        @else
                                            nothing yet (not on the calendar)
                                        @endif
                                    </div>
                                    @if($blocker && (!$ours || $blocker->id !== $ours->id))
                                    <div><span>In the way</span><a href="/admin/book/{{ $blocker->id }}/edit#unit-card">#{{ $blocker->id }}</a> · <b>{{ $blocker->listing->name ?? '#'.$blocker->listing_id }}</b> · {{ $fmt($blocker->check_in) }} → {{ $fmt($blocker->check_out) }} · {{ $blocker->source }}{{ $blocker->status == 1 ? ' (cancelled)' : '' }}</div>
                                    @endif
                                </div>
                                <div class="rv-do"><b>What to do:</b> {{ $kDo }}</div>
                                <details class="rv-note"><summary>System note</summary>{{ $note }}</details>
                            </div>
                            @if(!$log->resolved_at)
                            @php
                                $linkDead = $eb && $eb->book_id && (!$ours || (int) $ours->status === 1);
                                $B = [];
                                if ($ours) { $B['open'] = '<a href="/admin/book/'.$ours->id.'/edit#unit-card" class="btn %s btn-sm" title="Edit, cancel, reassign, split or swap this booking">Open #'.$ours->id.'</a>'; }
                                $B['dates']    = '<button type="button" class="btn %s btn-sm" onclick="acceptDates(this, '.$log->ezee_booking_id.', \''.$eb->Start.'\', \''.$eb->End.'\')" title="Move our dates to eZee\'s; the stamped rate stands">Accept eZee dates</button>';
                                $B['reassign'] = '<button type="button" class="btn %s btn-sm" onclick="togglePanel(\'reassign-'.$log->id.'\')" title="Move the whole booking to another unit">Move to another unit</button>';
                                $B['history']  = '<button type="button" class="btn %s btn-sm" onclick="togglePanel(\'hist-'.$log->id.'\')" title="Some nights were in another unit or an extra room">Some nights elsewhere</button>';
                                if (str_starts_with($note, 'EZEE now charges')) { $B['amounts'] = '<button type="button" class="btn %s btn-sm" onclick="acceptAmounts(this, '.$log->ezee_booking_id.')" title="Set this booking\'s rate, cleaning fee and SST to eZee\'s current figures">Use eZee amounts</button>'; }
                                if ($pair->count() === 2) { $B['duplicate'] = '<button type="button" class="btn %s btn-sm" onclick="togglePanel(\'dup-'.$log->id.'\')" title="The same stay was keyed twice: cancel the extra copy">Mark as duplicate</button>'; }
                                $B['voided']   = '<button type="button" class="btn %s btn-sm rv-danger" onclick="voidedInEzee(this, '.$log->ezee_booking_id.', \''.e($eb->SubBookingId).'\')" title="You checked eZee and this reservation is voided or cancelled there">Cancelled in eZee</button>';
                                $B['nounit']   = '<button type="button" class="btn %s btn-sm" onclick="noUnit(this, '.$log->ezee_booking_id.')" title="Extra-guest room, needs no unit">Needs no unit</button>';
                                if ($linkDead) { $B['restore'] = '<button type="button" class="btn %s btn-sm" onclick="restoreBooking(this, '.$log->ezee_booking_id.')" title="Bring back the cancelled booking eZee still reports">Restore</button>'; }
                                $B['done']     = '<button type="button" class="btn %s btn-sm" onclick="setResolved(this, '.$log->id.', true)" title="Already sorted out; takes it off this list">Mark done</button>';
                                $primary = array_values(array_filter($kPrimary, fn ($k) => isset($B[$k])));
                                $always  = array_values(array_diff(array_filter(['open', 'done'], fn ($k) => isset($B[$k])), $primary));
                                $rest    = array_values(array_diff(array_keys($B), $primary, $always));
                            @endphp
                            <div class="rv-actions">
                                @foreach($primary as $k){!! sprintf($B[$k], 'btn-primary') !!}@endforeach
                                @foreach($always as $k){!! sprintf($B[$k], 'btn-secondary') !!}@endforeach
                                @if($rest)
                                <details class="rv-more"><summary class="btn btn-secondary btn-sm">Other actions</summary>
                                    <div class="rv-more__list">@foreach($rest as $k){!! sprintf($B[$k], 'btn-secondary') !!}@endforeach</div>
                                </details>
                                @endif
                            </div>
                            @if($pair->count() === 2)
                            <div id="dup-{{ $log->id }}" class="review-panel" style="display:none">
                                <div class="review-panel-title"><b>Which one is the extra copy?</b> It will be cancelled, not deleted. The other one stays.</div>
                                @foreach($pair as $i => $pb)
                                <label class="rv-dup-opt"><input type="radio" name="dup-{{ $log->id }}" value="{{ $pb->id }}" data-other="{{ $pair[$i === 0 ? 1 : 0]->id }}">
                                    Cancel <b>#{{ $pb->id }}</b> · {{ $pb->listing->name ?? '#'.$pb->listing_id }} · {{ $fmt($pb->check_in) }} → {{ $fmt($pb->check_out) }} · {{ $pb->source }}{{ ($eb && (int) $eb->book_id === (int) $pb->id) ? ' · tied to '.$eb->SubBookingId : '' }}</label>
                                @endforeach
                                <input type="text" id="dup-reason-{{ $log->id }}" placeholder="Why is it a duplicate? (required)" maxlength="160" style="width:100%;max-width:420px;margin:6px 0 8px">
                                <div style="display:flex;gap:4px">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="markDuplicate(this, {{ $log->id }})">Cancel the selected copy</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="togglePanel('dup-{{ $log->id }}')">Close</button>
                                </div>
                            </div>
                            @endif
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
.table-wrap.wide td{vertical-align:top}
/* Needs Review: fewer columns, one tidy row of actions, help as a legend (18 Sep 2026) */
.rv-help{margin-bottom:16px;padding:12px 14px;background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;font-size:13px;color:#78350f}
.rv-help__lead{margin-bottom:10px;line-height:1.5}
.rv-help__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:6px 18px}
.rv-help__grid div{display:flex;flex-direction:column;line-height:1.35}
.rv-help__grid b{font-size:12.5px}
.rv-help__grid span{font-size:12px;opacity:.85}
.rv-mode th:nth-child(1),.rv-mode td:nth-child(1),.rv-mode th:nth-child(8),.rv-mode td:nth-child(8),.rv-mode th:nth-child(9),.rv-mode td:nth-child(9),.rv-mode th:nth-child(10),.rv-mode td:nth-child(10){display:none}
.table-wrap.wide.rv-mode table{min-width:0;width:100%;display:block}
/* Each review item is two lines: the stay across the top, then why it is stuck and the
   buttons underneath at full width, so nothing is squeezed into a 200px column. */
.rv-mode thead,.rv-mode tbody{display:block}
.rv-mode thead tr,.rv-mode tbody tr{display:grid;grid-template-columns:120px 1.5fr 1.1fr 110px 110px 1.4fr;align-items:start}
.rv-mode th:nth-child(11),.rv-mode td:nth-child(11),.rv-mode thead th:last-child{display:none}
.rv-mode tbody td{border-bottom:0}
.rv-mode tbody tr{border-bottom:1px solid var(--border,#e5e7eb);padding:4px 0 10px}
.rv-mode tbody td:last-child{grid-column:1/-1;padding-top:2px}
.rv-mode tbody td[colspan]{grid-column:1/-1}
.rv-mode tbody td:nth-child(2) code{font-size:12.5px;font-weight:600}
.table-wrap.rv-mode.tw-sticky th:last-child,.table-wrap.rv-mode.tw-sticky td:last-child{position:static;box-shadow:none}
.rv-mode td:last-child .btn{white-space:nowrap}
.rv-help__more{margin-top:2px}.rv-help__more summary{cursor:pointer;font-weight:600;font-size:12.5px;margin-bottom:8px}
.rv-card{border-left:4px solid #94a3b8;background:#f8fafc;border-radius:8px;padding:10px 12px;margin-bottom:8px;font-size:13px;line-height:1.5}
.rv-card__title{font-weight:700;font-size:13.5px;margin-bottom:6px}
.rv-facts{display:grid;gap:3px;margin-bottom:8px}
.rv-facts>div>span{display:inline-block;min-width:104px;color:var(--text-secondary);font-size:12px}
.rv-flag{font-style:normal;font-size:11px;font-weight:600;color:#b45309;background:#fef3c7;border-radius:999px;padding:1px 8px;margin-left:6px}
.rv-do{color:#0f172a}
.rv-note{margin-top:6px;font-size:12px;color:var(--text-secondary)}.rv-note summary{cursor:pointer}
.rv-actions{display:flex;gap:6px;flex-wrap:wrap;align-items:flex-start}
.rv-more{position:relative}.rv-more>summary{list-style:none;cursor:pointer}.rv-more>summary::-webkit-details-marker{display:none}.rv-more>summary::after{content:" ▾"}
.rv-more__list{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;padding:8px;border:1px dashed var(--border,#e5e7eb);border-radius:8px;background:#fff}
.rv-danger{color:#b91c1c!important;border-color:#fecaca!important}
.rv-dup-opt{display:flex;gap:8px;align-items:center;padding:4px 0;font-size:12.5px;cursor:pointer}
@media (max-width:700px){.rv-facts>div>span{display:block;min-width:0}}
.rv-why{font-size:12px;color:var(--text-secondary);margin-bottom:8px;line-height:1.5;padding:6px 8px;background:#f8fafc;border-radius:6px}

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

function voidedInEzee(btn, ezeeId, res) {
    var reason = prompt('You checked eZee and ' + res + ' is voided or cancelled there?\n\nMOKA retires it (never assigned again), cancels its booking if one exists, and frees the unit. Nothing is deleted.\n\nReason:', 'voided in eZee');
    if (reason === null) { return; }
    if (!reason.trim()) { alert('A reason is required.'); return; }
    postAction(btn, '/admin/ezee/booking/' + ezeeId + '/voided', { reason: reason.trim() }, 'Retired.');
}
function markDuplicate(btn, logId) {
    var pick = document.querySelector('input[name="dup-' + logId + '"]:checked');
    var reason = document.getElementById('dup-reason-' + logId).value.trim();
    if (!pick) { alert('Choose which booking is the extra copy.'); return; }
    if (!reason) { alert('Please say why it is a duplicate.'); return; }
    if (!confirm('Cancel booking #' + pick.value + ' as a duplicate and keep #' + pick.dataset.other + '?\n\nNothing is deleted; the cancelled copy stays in the history.')) { return; }
    postAction(btn, '/admin/ezee/review/' + logId + '/duplicate', { cancel_id: parseInt(pick.value, 10), keep_id: parseInt(pick.dataset.other, 10), reason: reason }, 'Done.');
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

function acceptAmounts(btn, ezeeId) {
    if (!confirm('Set this booking to EZEE\'s current rate, cleaning fee and SST?\n\nEvery segment of the stay is repriced; nothing else changes.')) { return; }
    postAction(btn, '/admin/ezee/booking/' + ezeeId + '/accept-amounts', {}, 'Repriced from EZEE.');
}
function acceptDates(btn, ezeeId, start, end) {
    if (!confirm('Move our dates to EZEE\'s (' + start + ' to ' + end + ')?\n\nThe nightly rate stays as stamped; the amount follows the nights. Segments outside the new dates are cancelled.')) { return; }
    postAction(btn, '/admin/ezee/booking/' + ezeeId + '/accept-dates', {}, 'Dates updated.');
}
</script>
@endpush
