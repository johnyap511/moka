@extends('admin.layout')
@section('page-title', 'EZEE Bookings by Property')

@push('styles')
<style>
.badge-assigned   { background:#d1fae5; color:#065f46; padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:500; }
.badge-unassigned { background:#fff7ed; color:#c2410c; padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:500; }
.badge-source     { background:#eff6ff; color:#1d4ed8; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:500; }

/* Property tabs */
.prop-tabs        { display:flex; gap:4px; border-bottom:2px solid var(--border); margin-bottom:20px; flex-wrap:wrap; }
.prop-tab         { padding:10px 20px; font-size:13px; font-weight:500; cursor:pointer; border-radius:6px 6px 0 0; border:1px solid transparent; border-bottom:none; color:var(--text-secondary); background:transparent; transition:.15s; }
.prop-tab:hover   { background:#f3f4f6; }
.prop-tab.active  { background:#fff; border-color:var(--border); border-bottom-color:#fff; color:var(--text-primary); font-weight:600; margin-bottom:-2px; }
.prop-panel       { display:none; }
.prop-panel.active{ display:block; }

/* Table sort */
th.sortable { cursor:pointer; user-select:none; white-space:nowrap; }
th.sortable:hover { color:var(--teal); }
th.sortable .sort-icon { margin-left:4px; opacity:.4; font-style:normal; font-size:10px; }
th.sortable.asc  .sort-icon::after { content:'▲'; opacity:1; }
th.sortable.desc .sort-icon::after { content:'▼'; opacity:1; }
th.sortable:not(.asc):not(.desc) .sort-icon::after { content:'⇅'; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>EZEE Bookings by Property</h1>
        <p>View bookings grouped by property</p>
    </div>
    <div>
        <a href="/admin/ezee/booking" class="btn btn-secondary">← Back to All Bookings</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif

{{-- Property Tabs --}}
<div class="prop-tabs">
    @foreach($groups as $i => $group)
    <div class="prop-tab {{ $i === 0 ? 'active' : '' }}" onclick="switchTab({{ $group->id }}, this)">
        {{ $group->name }}
        <span style="font-size:11px;color:var(--text-secondary);margin-left:6px">({{ isset($bookingsByGroup[$group->id]) ? count($bookingsByGroup[$group->id]) : 0 }})</span>
    </div>
    @endforeach
</div>

{{-- Panels --}}
@foreach($groups as $i => $group)
<div class="prop-panel {{ $i === 0 ? 'active' : '' }}" id="panel-{{ $group->id }}">
    @php $books = $bookingsByGroup[$group->id] ?? collect(); @endphp

    {{-- Search bar --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;flex-wrap:wrap">
        <div class="search-bar" style="min-width:280px">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" placeholder="Search guest, room, source…" oninput="filterTable(this.value, {{ $group->id }})">
        </div>
        <span id="count-{{ $group->id }}" style="font-size:12px;color:var(--text-secondary);white-space:nowrap">{{ count($books) }} bookings</span>
    </div>

    <div class="card">
        <div class="table-wrap" style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;min-width:900px" id="table-{{ $group->id }}">
                <thead>
                    <tr>
                        <th class="sortable" data-col="0" style="padding:10px 14px;text-align:left;font-size:11.5px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border)">Reservation No <i class="sort-icon"></i></th>
                        <th class="sortable" data-col="1" style="padding:10px 14px;text-align:left;font-size:11.5px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border)">Folio No <i class="sort-icon"></i></th>
                        <th class="sortable" data-col="2" style="padding:10px 14px;text-align:left;font-size:11.5px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border)">Guest <i class="sort-icon"></i></th>
                        <th class="sortable" data-col="3" style="padding:10px 14px;text-align:left;font-size:11.5px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border)">Room Type <i class="sort-icon"></i></th>
                        <th class="sortable" data-col="4" style="padding:10px 14px;text-align:left;font-size:11.5px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border)">Room Unit <i class="sort-icon"></i></th>
                        <th class="sortable" data-col="5" style="padding:10px 14px;text-align:left;font-size:11.5px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border)">Check In <i class="sort-icon"></i></th>
                        <th class="sortable" data-col="6" style="padding:10px 14px;text-align:left;font-size:11.5px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border)">Check Out <i class="sort-icon"></i></th>
                        <th class="sortable" data-col="7" style="padding:10px 14px;text-align:left;font-size:11.5px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border)">Amount (MYR) <i class="sort-icon"></i></th>
                        <th class="sortable" data-col="8" style="padding:10px 14px;text-align:left;font-size:11.5px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border)">Source <i class="sort-icon"></i></th>
                        <th class="sortable" data-col="9" style="padding:10px 14px;text-align:left;font-size:11.5px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border)">Status <i class="sort-icon"></i></th>
                    </tr>
                </thead>
                <tbody id="tbody-{{ $group->id }}">
                    @forelse($books as $b)
                    <tr style="border-bottom:1px solid var(--border)" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                        <td style="padding:10px 14px;font-size:12px;font-family:monospace" data-val="{{ $b->SubBookingId ?? '' }}">{{ $b->SubBookingId ?? '—' }}</td>
                        <td style="padding:10px 14px;font-size:12px;font-family:monospace" data-val="{{ $b->folio_no ?? '' }}">{{ $b->folio_no ?? '—' }}</td>
                        <td style="padding:10px 14px;font-size:13px" data-val="{{ $b->FirstName }} {{ $b->LastName }}">
                            {{ trim(($b->FirstName ?? '').' '.($b->LastName ?? '')) ?: '—' }}
                        </td>
                        <td style="padding:10px 14px;font-size:13px" data-val="{{ $b->RoomTypeName ?? '' }}">{{ $b->RoomTypeName ?? '—' }}</td>
                        <td style="padding:10px 14px;font-size:13px" data-val="{{ $b->RoomName ?? '' }}">{{ $b->RoomName ?? '—' }}</td>
                        <td style="padding:10px 14px;font-size:13px" data-val="{{ $b->Start ?? '' }}">{{ $b->Start ? \Carbon\Carbon::parse($b->Start)->format('d M Y') : '—' }}</td>
                        <td style="padding:10px 14px;font-size:13px" data-val="{{ $b->End ?? '' }}">{{ $b->End ? \Carbon\Carbon::parse($b->End)->format('d M Y') : '—' }}</td>
                        <td style="padding:10px 14px;font-size:13px;text-align:right" data-val="{{ $b->TotalAmountAfterTax ?? 0 }}">
                            {{ $b->TotalAmountAfterTax ? number_format($b->TotalAmountAfterTax, 2) : '—' }}
                        </td>
                        <td style="padding:10px 14px">
                            @if($b->Source)<span class="badge-source">{{ $b->Source }}</span>@else —@endif
                        </td>
                        <td style="padding:10px 14px">
                            @if($b->book_id)
                                <span class="badge-assigned">Assigned</span>
                            @else
                                <span class="badge-unassigned">Unassigned</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" style="padding:40px;text-align:center;color:var(--text-secondary)">No bookings found for this property.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endforeach

@endsection

@push('scripts')
<script>
function switchTab(groupId, el) {
    document.querySelectorAll('.prop-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.prop-panel').forEach(p => p.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('panel-' + groupId).classList.add('active');
}

function filterTable(val, groupId) {
    const tbody = document.getElementById('tbody-' + groupId);
    const rows  = tbody.querySelectorAll('tr');
    const q     = val.toLowerCase();
    let visible = 0;
    rows.forEach(row => {
        const text = Array.from(row.querySelectorAll('td')).map(td => (td.dataset.val || td.innerText).toLowerCase()).join(' ');
        const show = text.includes(q);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const countEl = document.getElementById('count-' + groupId);
    if (countEl) countEl.textContent = visible + ' bookings';
}

// Sorting
document.querySelectorAll('th.sortable').forEach(th => {
    th.addEventListener('click', function() {
        const table = this.closest('table');
        const tbody = table.querySelector('tbody');
        const col   = +this.dataset.col;
        const asc   = !this.classList.contains('asc');
        table.querySelectorAll('th.sortable').forEach(t => t.classList.remove('asc','desc'));
        this.classList.add(asc ? 'asc' : 'desc');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            const av = (a.cells[col]?.dataset.val || a.cells[col]?.innerText || '').trim();
            const bv = (b.cells[col]?.dataset.val || b.cells[col]?.innerText || '').trim();
            const an = parseFloat(av), bn = parseFloat(bv);
            if (!isNaN(an) && !isNaN(bn)) return asc ? an - bn : bn - an;
            return asc ? av.localeCompare(bv) : bv.localeCompare(av);
        });
        rows.forEach(r => tbody.appendChild(r));
    });
});
</script>
@endpush
