@extends('admin.layout')
@section('title', 'EZEE Room Mapping')
@section('page-title', 'EZEE Room Mapping')

@push('styles')
<style>
.mapping-table { width:100%; border-collapse:collapse; }
.mapping-table th { padding:9px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--text-secondary); background:var(--bg-secondary); border-bottom:1px solid var(--border); white-space:nowrap; }
.mapping-table td { padding:10px 14px; border-bottom:1px solid var(--border); font-size:13px; vertical-align:middle; }
.mapping-table tr:last-child td { border-bottom:none; }
.mapping-table tr:hover td { background:#fafafa; }
.pill-mapped    { background:#d1fae5; color:#065f46; padding:2px 10px; border-radius:20px; font-size:11.5px; font-weight:500; white-space:nowrap; }
.pill-unmapped  { background:#fff7ed; color:#c2410c; padding:2px 10px; border-radius:20px; font-size:11.5px; font-weight:500; white-space:nowrap; }
.pill-suggested { background:#eff6ff; color:#1d4ed8; padding:2px 10px; border-radius:20px; font-size:11.5px; font-weight:500; white-space:nowrap; }
select.msel { padding:6px 10px; border:1px solid var(--border); border-radius:6px; font-size:12.5px; width:100%; max-width:340px; background:#fff; }
select.msel:focus { outline:2px solid var(--teal); border-color:var(--teal); }
select.msel.is-mapped { border-color:#10b981; background:#f0fdf4; }
select.msel.is-suggested { border-color:#3b82f6; background:#eff6ff; }
.sticky-bar { position:sticky; top:0; z-index:50; background:#fff; border-bottom:1px solid var(--border); padding:10px 0 10px; margin-bottom:16px; display:flex; align-items:center; gap:12px; }
.search-filter { padding:6px 12px; border:1px solid var(--border); border-radius:6px; font-size:13px; width:240px; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>EZEE Room Mapping</h1>
        <p>Map each EZEE room name to a listing unit for automatic booking assignment</p>
    </div>
    <div class="flex gap-2">
        @if($showArchived)
            <a href="{{ route('admin.ezee.room-mapping') }}" class="btn btn-secondary">
                &larr; Back to active units
            </a>
        @else
            <a href="{{ route('admin.ezee.room-mapping', ['archived' => 1]) }}" class="btn btn-secondary">
                Archived ({{ $archivedCount }})
            </a>
        @endif
        <a href="/admin/ezee/assignment-log" class="btn btn-secondary">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Assignment Log
        </a>
        <button type="button" class="btn btn-primary" id="btn-auto-assign" onclick="runAutoAssign()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Auto-Assign Unassigned
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif

{{-- Stats --}}
@php
$totalRooms    = count($rooms);
$mappedRooms   = $rooms->filter(fn($r) => isset($mappings[$r->Key]) && $mappings[$r->Key]->listing_id)->count();
$suggestedCount= count($suggestions);
$totalBooks    = $stats->sum('total');
$assignedBooks = $stats->sum('assigned');
@endphp
<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card"><div class="val">{{ $totalRooms }}</div><div class="lbl">Units</div></div>
    <div class="stat-card"><div class="val" style="color:var(--teal)">{{ $mappedRooms }}</div><div class="lbl">Mapped</div></div>
    <div class="stat-card"><div class="val" style="color:#3b82f6">{{ $suggestedCount }}</div><div class="lbl">Auto-Matched</div></div>
    <div class="stat-card"><div class="val" style="color:#f59e0b">{{ $totalRooms - $mappedRooms - $suggestedCount }}</div><div class="lbl">Need Manual Map</div></div>
    <div class="stat-card"><div class="val">{{ number_format($totalBooks) }}</div><div class="lbl">Total Bookings</div></div>
    <div class="stat-card"><div class="val" style="color:#f59e0b">{{ number_format($totalBooks - $assignedBooks) }}</div><div class="lbl">Unassigned</div></div>
</div>

<form method="POST" action="/admin/ezee/room-mapping/save" id="mapping-form">
    @csrf

    <div class="sticky-bar">
        <button type="submit" class="btn btn-primary">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Save All Mappings
        </button>
        @if($suggestedCount > 0)
        <button type="button" class="btn btn-secondary" onclick="applyAllSuggestions()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Apply {{ $suggestedCount }} Auto-Matches
        </button>
        @endif
        <input type="text" id="search-box" class="search-filter" placeholder="Filter room names…" oninput="filterRooms(this.value)">
        <label style="font-size:12px;color:var(--text-secondary);margin-left:auto;display:flex;align-items:center;gap:6px;cursor:pointer">
            <input type="checkbox" id="show-unmapped-only" onchange="filterUnmapped(this.checked)">
            Show unmapped only
        </label>
        <span id="changed-count" style="font-size:12px;color:var(--text-secondary)"></span>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="mapping-table" id="mapping-table">
                <thead>
                    <tr>
                        <th style="width:36px">#</th>
                        <th>Property</th>
                        <th>Room Unit (RoomName)</th>
                        <th>Room Category (RoomTypeName)</th>
                        <th style="width:80px;text-align:center">Bookings</th>
                        <th style="width:80px;text-align:center">Assigned</th>
                        <th style="width:130px;text-align:center">Status</th>
                        <th>Map to Listing Unit ↓</th>
                        <th style="width:90px;text-align:center">Manage</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rooms as $i => $room)
                    @php
                        // Keyed on property + unit: the same unit name exists in
                        // several properties and each needs its own mapping.
                        $roomKey      = $room->Key;
                        $roomName     = $room->RoomName;
                        $roomTypeName = $room->RoomTypeName;
                        $mapping      = $mappings[$roomKey] ?? null;
                        $statRow      = $stats[$roomKey]    ?? null;
                        $isMapped     = $mapping && $mapping->listing_id;
                        $suggested    = !$isMapped && isset($suggestions[$roomKey]) ? $suggestions[$roomKey] : null;
                        $total        = $statRow->total    ?? 0;
                        $assigned     = $statRow->assigned ?? 0;
                    @endphp
                    <tr class="room-row {{ $isMapped ? 'is-mapped-row' : ($suggested ? 'is-suggested-row' : 'is-unmapped-row') }}"
                        data-room="{{ strtolower($roomName) }}">
                        <td class="mono" style="color:var(--text-secondary)">{{ $i+1 }}</td>
                        <td style="font-size:12px;color:var(--text-secondary)">{{ $room->PropertyName }}</td>
                        <td style="font-weight:600;font-family:monospace">{{ $roomName }}</td>
                        <td style="font-size:12px;color:var(--text-secondary)">{{ $roomTypeName ?? '—' }}</td>
                        <td style="text-align:center;font-weight:600">{{ $total }}</td>
                        <td style="text-align:center;color:{{ $assigned < $total ? '#f59e0b' : '#10b981' }};font-weight:600">
                            {{ $assigned }}
                        </td>
                        <td style="text-align:center">
                            @if($isMapped)
                                <span class="pill-mapped">Mapped</span>
                            @elseif($suggested)
                                <span class="pill-suggested">Auto-matched</span>
                            @else
                                <span class="pill-unmapped">Unmapped</span>
                            @endif
                        </td>
                        <td>
                            <select name="mappings[{{ $roomKey }}]"
                                    data-suggested="{{ $suggested }}"
                                    class="msel {{ $isMapped ? 'is-mapped' : ($suggested ? 'is-suggested' : '') }}"
                                    onchange="onMappingChange(this)">
                                <option value="">— Not mapped —</option>
                                @foreach($listings as $l)
                                <option value="{{ $l->id }}"
                                    {{ ($isMapped && $mapping->listing_id == $l->id) ? 'selected' : ($suggested == $l->id && !$isMapped ? 'data-suggest=1' : '') }}>
                                    {{ $l->name }}
                                </option>
                                @endforeach
                            </select>
                        </td>
                        <td style="text-align:center">
                            @if($showArchived)
                                <button type="button" class="btn btn-secondary" style="padding:4px 10px;font-size:12px"
                                        onclick="setArchived(this, '{{ $roomKey }}', false)">Restore</button>
                            @else
                                <button type="button" class="btn btn-secondary" style="padding:4px 10px;font-size:12px"
                                        onclick="setArchived(this, '{{ $roomKey }}', true)"
                                        title="Stop managing this unit. It will not be assigned to; existing bookings are untouched.">Archive</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:60px;color:var(--text-secondary)">
                            No EZEE room names found. Import EZEE bookings first.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

<div id="assign-toast" style="display:none;position:fixed;bottom:24px;right:24px;background:#111827;color:#fff;padding:14px 20px;border-radius:10px;font-size:13px;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.3);max-width:340px"></div>
@endsection

@push('scripts')
<script>
var changedCount = 0;

// Pre-select suggested (auto-matched) options in dropdowns
document.querySelectorAll('select.msel.is-suggested').forEach(function(sel) {
    var suggestedId = sel.getAttribute('data-suggested');
    if (!suggestedId) return;
    Array.from(sel.options).forEach(function(opt) {
        if (opt.value == suggestedId) opt.selected = true;
    });
});

function onMappingChange(sel) {
    var row = sel.closest('tr');
    var hasMapped = sel.value !== '';
    sel.className = 'msel' + (hasMapped ? ' is-mapped' : '');
    var pill = row.querySelector('[class^="pill-"]');
    if (pill) {
        pill.className   = hasMapped ? 'pill-mapped' : 'pill-unmapped';
        pill.textContent = hasMapped ? 'Mapped' : 'Unmapped';
    }
    row.className = row.className.replace(/\bis-(mapped|suggested|unmapped)-row\b/g, '') + ' ' + (hasMapped ? 'is-mapped' : 'is-unmapped') + '-row';
    changedCount++;
    document.getElementById('changed-count').textContent = changedCount + ' unsaved change(s)';
}

function applyAllSuggestions() {
    document.querySelectorAll('select.msel.is-suggested').forEach(function(sel) {
        var suggestedId = sel.getAttribute('data-suggested');
        if (!suggestedId || sel.value) return;
        Array.from(sel.options).forEach(function(opt) {
            if (opt.value == suggestedId) opt.selected = true;
        });
        onMappingChange(sel);
    });
}

function filterRooms(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.room-row').forEach(function(row) {
        row.style.display = (!q || row.dataset.room.includes(q)) ? '' : 'none';
    });
}

function filterUnmapped(only) {
    document.querySelectorAll('.room-row').forEach(function(row) {
        row.style.display = (only && row.classList.contains('is-mapped-row')) ? 'none' : '';
    });
}

function runAutoAssign() {
    if (!confirm('Auto-assign all unassigned EZEE bookings that have a saved room mapping?\n\nThis creates Booking records for each matched entry.')) return;
    var btn = document.getElementById('btn-auto-assign');
    btn.disabled = true; btn.textContent = 'Running…';
    fetch('/admin/ezee/room-mapping/auto-assign', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(function(res) {
        btn.disabled = false;
        btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Auto-Assign Unassigned';
        showToast(res.message, res.ok ? '#10b981' : '#ef4444');
        if (res.assigned > 0) setTimeout(() => location.reload(), 2500);
    })
    .catch(() => { btn.disabled = false; showToast('Error running auto-assign.', '#ef4444'); });
}

function showToast(msg, color) {
    var t = document.getElementById('assign-toast');
    t.textContent = msg; t.style.background = color || '#111827'; t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 5000);
}

window.addEventListener('beforeunload', function(e) {
    if (changedCount > 0) { e.preventDefault(); e.returnValue = ''; }
});
document.getElementById('mapping-form').addEventListener('submit', function() { changedCount = 0; });
</script>
@endpush

<script>
// Archiving a unit takes it off the working list and stops anything being
// assigned to it. It is reversible from the Archived view.
async function setArchived(btn, key, archived) {
    if (archived && !confirm('Archive this unit?\n\nIt will be hidden from this list and no bookings will be assigned to it. Existing bookings are not changed. You can restore it from the Archived view.')) {
        return;
    }

    btn.disabled = true;
    btn.textContent = archived ? 'Archiving…' : 'Restoring…';

    try {
        const res = await fetch('{{ route('admin.ezee.room-mapping.archive') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ key: key, archived: archived }),
        });

        const data = await res.json();

        if (!res.ok || !data.ok) {
            throw new Error(data.message || 'Request failed');
        }

        btn.closest('tr').remove();
    } catch (e) {
        alert('Could not update that unit: ' + e.message);
        btn.disabled = false;
        btn.textContent = archived ? 'Archive' : 'Restore';
    }
}
</script>
