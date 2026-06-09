@extends('admin.layout')
@section('title', 'EZEE Room Mapping')
@section('page-title', 'EZEE Room Mapping')

@push('styles')
<style>
.group-section { margin-bottom: 32px; }
.group-header  { display:flex; align-items:center; gap:10px; padding:12px 0 8px; border-bottom:2px solid var(--teal); margin-bottom:0; }
.group-header h3 { font-size:14px; font-weight:700; margin:0; color:var(--text-primary); }
.mapping-table { width:100%; border-collapse:collapse; }
.mapping-table th { padding:9px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--text-secondary); background:var(--bg-secondary); border-bottom:1px solid var(--border); }
.mapping-table td { padding:10px 14px; border-bottom:1px solid var(--border); font-size:13px; vertical-align:middle; }
.mapping-table tr:last-child td { border-bottom:none; }
.mapping-table tr:hover td { background:#fafafa; }
.pill-mapped   { background:#d1fae5; color:#065f46; padding:2px 10px; border-radius:20px; font-size:11.5px; font-weight:500; }
.pill-unmapped { background:#fff7ed; color:#c2410c; padding:2px 10px; border-radius:20px; font-size:11.5px; font-weight:500; }
.stat-chip     { font-size:11.5px; color:var(--text-secondary); }
.stat-chip span { font-weight:600; color:var(--text-primary); }
select.mapping-select { padding:5px 8px; border:1px solid var(--border); border-radius:6px; font-size:12px; min-width:220px; max-width:320px; background:#fff; }
select.mapping-select:focus { outline:2px solid var(--teal); }
select.mapping-select.has-value { border-color: var(--teal); }
.sticky-bar { position:sticky; top:0; z-index:100; background:#fff; border-bottom:1px solid var(--border); padding:10px 0; margin-bottom:20px; display:flex; align-items:center; gap:12px; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>EZEE Room Mapping</h1>
        <p>Map each EZEE room type to a listing unit for automatic booking assignment</p>
    </div>
    <div class="flex gap-2">
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

{{-- Summary stats --}}
@php
$totalRooms   = $rooms->count();
$mappedRooms  = $rooms->filter(fn($r) => isset($mappings[$r->ezee_group_id.'||'.$r->RoomTypeName]) && $mappings[$r->ezee_group_id.'||'.$r->RoomTypeName]->listing_id)->count();
$totalBooks   = $stats->sum('total');
$assignedBooks= $stats->sum('assigned');
@endphp
<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="val">{{ $totalRooms }}</div>
        <div class="lbl">Room Types Found</div>
    </div>
    <div class="stat-card">
        <div class="val" style="color:var(--teal)">{{ $mappedRooms }}</div>
        <div class="lbl">Mapped</div>
    </div>
    <div class="stat-card">
        <div class="val" style="color:var(--blue)">{{ $totalRooms - $mappedRooms }}</div>
        <div class="lbl">Unmapped</div>
    </div>
    <div class="stat-card">
        <div class="val">{{ number_format($totalBooks) }}</div>
        <div class="lbl">Total Bookings</div>
    </div>
    <div class="stat-card">
        <div class="val" style="color:var(--teal)">{{ number_format($assignedBooks) }}</div>
        <div class="lbl">Assigned</div>
    </div>
    <div class="stat-card">
        <div class="val" style="color:var(--orange, #f59e0b)">{{ number_format($totalBooks - $assignedBooks) }}</div>
        <div class="lbl">Unassigned</div>
    </div>
</div>

<form method="POST" action="/admin/ezee/room-mapping/save" id="mapping-form">
    @csrf

    {{-- Sticky save bar --}}
    <div class="sticky-bar">
        <button type="submit" class="btn btn-primary">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Save All Mappings
        </button>
        <span id="changed-count" style="font-size:12px;color:var(--text-secondary)"></span>
        <label style="font-size:12px;color:var(--text-secondary);margin-left:auto;display:flex;align-items:center;gap:6px">
            <input type="checkbox" id="show-unmapped-only" onchange="filterUnmapped(this.checked)">
            Show unmapped only
        </label>
    </div>

    @php $grouped = $rooms->groupBy('ezee_group_id'); @endphp

    @forelse($grouped as $groupId => $groupRooms)
    @php $group = $groups[$groupId] ?? null; @endphp
    <div class="card group-section" data-group="{{ $groupId }}">
        <div class="card-body" style="padding-bottom:0">
            <div class="group-header">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <h3>{{ $group ? $group->name : 'Hotel #'.$groupId }}</h3>
                @if($group)
                <span class="badge badge-blue" style="font-size:11px">Code: {{ $group->hotel_code }}</span>
                @endif
                <span style="margin-left:auto;font-size:12px;color:var(--text-secondary)">{{ $groupRooms->count() }} room types</span>
            </div>
        </div>
        <div class="table-wrap">
            <table class="mapping-table">
                <thead>
                    <tr>
                        <th style="width:36px">#</th>
                        <th>Room Type Name (EZEE)</th>
                        <th style="width:100px">Bookings</th>
                        <th style="width:100px">Assigned</th>
                        <th style="width:140px">Status</th>
                        <th>Map to Listing Unit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupRooms as $i => $room)
                    @php
                        $mapKey    = $room->ezee_group_id . '||' . $room->RoomTypeName;
                        $mapping   = $mappings[$mapKey] ?? null;
                        $statRow   = $stats[$mapKey]    ?? null;
                        $isMapped  = $mapping && $mapping->listing_id;
                        $total     = $statRow->total    ?? 0;
                        $assigned  = $statRow->assigned ?? 0;
                    @endphp
                    <tr class="room-row {{ $isMapped ? 'mapped-row' : 'unmapped-row' }}">
                        <td class="mono" style="color:var(--text-secondary)">{{ $i+1 }}</td>
                        <td>
                            <div style="font-weight:500">{{ $room->RoomTypeName }}</div>
                        </td>
                        <td class="stat-chip"><span>{{ $total }}</span></td>
                        <td class="stat-chip">
                            <span style="{{ $assigned < $total ? 'color:#f59e0b' : 'color:#10b981' }}">{{ $assigned }}</span>
                            @if($total > 0)
                            <span style="color:var(--text-secondary)">/{{ $total }}</span>
                            @endif
                        </td>
                        <td>
                            @if($isMapped)
                                <span class="pill-mapped">Mapped</span>
                            @else
                                <span class="pill-unmapped">Unmapped</span>
                            @endif
                        </td>
                        <td>
                            <select name="mappings[{{ $mapKey }}]"
                                    class="mapping-select {{ $isMapped ? 'has-value' : '' }}"
                                    onchange="onMappingChange(this)">
                                <option value="">— Not mapped —</option>
                                @foreach($listings as $l)
                                <option value="{{ $l->id }}"
                                    {{ ($mapping && $mapping->listing_id == $l->id) ? 'selected' : '' }}>
                                    {{ $l->name }}
                                </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body" style="text-align:center;padding:60px;color:var(--text-secondary)">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom:12px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <p>No EZEE room types found. Import EZEE bookings first, then come back to map them.</p>
        </div>
    </div>
    @endforelse

</form>

{{-- Auto-assign result toast --}}
<div id="assign-toast" style="display:none;position:fixed;bottom:24px;right:24px;background:#111827;color:#fff;padding:14px 20px;border-radius:10px;font-size:13px;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.3);max-width:320px"></div>

@endsection

@push('scripts')
<script>
var changedCount = 0;

function onMappingChange(sel) {
    var row = sel.closest('tr');
    var hasMapped = sel.value !== '';
    sel.classList.toggle('has-value', hasMapped);
    var pill = row.querySelector('.pill-mapped, .pill-unmapped');
    if (pill) {
        pill.className   = hasMapped ? 'pill-mapped' : 'pill-unmapped';
        pill.textContent = hasMapped ? 'Mapped' : 'Unmapped';
    }
    row.className = row.className.replace(/\b(mapped|unmapped)-row\b/g, '') + ' ' + (hasMapped ? 'mapped' : 'unmapped') + '-row';
    changedCount++;
    document.getElementById('changed-count').textContent = changedCount + ' change(s) unsaved';
}

function filterUnmapped(onlyUnmapped) {
    document.querySelectorAll('.room-row').forEach(function(row) {
        if (onlyUnmapped && row.classList.contains('mapped-row')) {
            row.style.display = 'none';
        } else {
            row.style.display = '';
        }
    });
}

function runAutoAssign() {
    if (!confirm('Auto-assign all unassigned EZEE bookings that have a matching room type mapping?\n\nThis will create Booking records for matched entries.')) return;

    var btn = document.getElementById('btn-auto-assign');
    btn.disabled = true;
    btn.textContent = 'Running…';

    fetch('/admin/ezee/room-mapping/auto-assign', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(function(res) {
        btn.disabled = false;
        btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Auto-Assign Unassigned';
        showToast(res.message || 'Done!', res.ok ? '#10b981' : '#ef4444');
        if (res.assigned > 0) setTimeout(() => location.reload(), 2000);
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = 'Auto-Assign Unassigned';
        showToast('Error running auto-assign.', '#ef4444');
    });
}

function showToast(msg, color) {
    var t = document.getElementById('assign-toast');
    t.textContent  = msg;
    t.style.background = color || '#111827';
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 5000);
}

// Warn before leaving with unsaved changes
window.addEventListener('beforeunload', function(e) {
    if (changedCount > 0) {
        e.preventDefault();
        e.returnValue = '';
    }
});
document.getElementById('mapping-form').addEventListener('submit', function() {
    changedCount = 0;
});
</script>
@endpush
