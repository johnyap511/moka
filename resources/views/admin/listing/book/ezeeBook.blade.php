@extends('admin.layout')
@section('page-title', 'EZEE Bookings')

@push('styles')
<style>
.badge-source { background:#eff6ff; color:#1d4ed8; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:500; }
.assign-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:200; align-items:center; justify-content:center; }
th.sortable { cursor:pointer; user-select:none; }
/* Compact enough to fit without horizontal scrolling: headers and long values
   wrap rather than forcing the table wider than the viewport. */
.ezee-table { width:100%; border-collapse:collapse; table-layout:fixed; }
.ezee-table th, .ezee-table td { padding:7px 8px; vertical-align:top; word-break:break-word; }
.ezee-table th {
    text-align:left; font-size:10.5px; font-weight:600; line-height:1.25;
    color:var(--text-secondary); text-transform:uppercase; letter-spacing:.3px;
    border-bottom:1px solid var(--border); white-space:normal;
}
.ezee-table td { font-size:12px; border-bottom:1px solid var(--border); }
.ezee-table .num { text-align:right; white-space:nowrap; }
.ezee-table .mono { font-family:monospace; font-size:11px; }
.ezee-table tbody tr:hover { background:#fafafa; }
.ezee-actions { display:flex; flex-direction:column; gap:4px; align-items:stretch; }
.ezee-actions .btn { padding:3px 8px; font-size:11px; justify-content:center; }
th.sortable:hover { color:var(--teal); }
th.sortable .sort-icon { margin-left:4px; opacity:.4; font-style:normal; font-size:10px; }
th.sortable.asc .sort-icon::after  { content:'▲'; opacity:1; }
th.sortable.desc .sort-icon::after { content:'▼'; opacity:1; }
th.sortable:not(.asc):not(.desc) .sort-icon::after { content:'⇅'; }

</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>EZEE Bookings</h1>
        <p>Imported bookings from EZEE — assign each to a listing unit</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error" style="margin-bottom:16px">{{ session('error') }}</div>
@endif

{{-- Filter Tabs + Search --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;flex-wrap:wrap">
    <a href="/admin/ezee/booking" class="btn {{ !request()->is('admin/ezee/unassigned_booking') && !request()->is('admin/ezee/assigned_booking') ? 'btn-primary' : 'btn-secondary' }}">All</a>
    <a href="/admin/ezee/unassigned_booking" class="btn {{ request()->is('admin/ezee/unassigned_booking') ? 'btn-primary' : 'btn-secondary' }}">Unassigned</a>
    <a href="/admin/ezee/assigned_booking" class="btn {{ request()->is('admin/ezee/assigned_booking') ? 'btn-primary' : 'btn-secondary' }}">Assigned</a>
    <form method="POST" action="/admin/ezee/bookings/remove-duplicates" style="margin:0" onsubmit="return confirm('This will remove all duplicate unassigned bookings (same guest, dates and amount), keeping the oldest record. Continue?')">
        @csrf
        <button type="submit" class="btn btn-secondary" style="color:#dc2626">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Remove Duplicates
        </button>
    </form>
    <div style="margin-left:auto;display:flex;align-items:center;gap:8px">
        <div class="search-bar" style="min-width:280px">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="ezee-search" placeholder="Search guest, room, source…" oninput="filterTable(this.value)">
        </div>
        <span id="result-count" style="font-size:12px;color:var(--text-secondary);white-space:nowrap"></span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Bookings <span class="badge badge-blue" id="visible-count">{{ count($books) }}</span></h2>
    </div>
    <div class="table-wrap">
        <table class="ezee-table" id="ezee-table">
            <colgroup>
                <col style="width:7%">   {{-- Res No --}}
                <col style="width:6%">   {{-- Folio --}}
                <col style="width:5%">   {{-- Prop ID --}}
                <col style="width:11%">   {{-- Property --}}
                <col style="width:10%">   {{-- Room Type --}}
                <col style="width:6%">   {{-- Check In --}}
                <col style="width:6%">   {{-- Check Out --}}
                <col style="width:8%">   {{-- Source --}}
                <col style="width:6%">   {{-- Rate/Night --}}
                <col style="width:5%">   {{-- SST --}}
                <col style="width:6%">   {{-- Cleaning --}}
                <col style="width:5%">   {{-- SST(CF) --}}
                <col style="width:5%">   {{-- M&A --}}
                <col style="width:6%">   {{-- Total --}}
                <col style="width:8%">   {{-- Action --}}
            </colgroup>
            <thead>
                <tr>
                    <th class="sortable" data-col="0">Res No<i class="sort-icon"></i></th>
                    <th class="sortable" data-col="1">Folio<i class="sort-icon"></i></th>
                    <th class="sortable" data-col="2">Prop ID<i class="sort-icon"></i></th>
                    <th class="sortable" data-col="3">Property<i class="sort-icon"></i></th>
                    <th class="sortable" data-col="4">Room Type<i class="sort-icon"></i></th>
                    <th class="sortable" data-col="5">Check In<i class="sort-icon"></i></th>
                    <th class="sortable" data-col="6">Check Out<i class="sort-icon"></i></th>
                    <th class="sortable" data-col="7">Source<i class="sort-icon"></i></th>
                    <th class="sortable num" data-col="8">Rate/Night<i class="sort-icon"></i></th>
                    <th class="sortable num" data-col="9">SST<i class="sort-icon"></i></th>
                    <th class="sortable num" data-col="10">Cleaning<i class="sort-icon"></i></th>
                    <th class="sortable num" data-col="11">SST(CF)<i class="sort-icon"></i></th>
                    <th class="sortable num" data-col="12">M&amp;A<i class="sort-icon"></i></th>
                    <th class="sortable num" data-col="13">Total<i class="sort-icon"></i></th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="ezee-tbody">
                @forelse($books as $i => $b)
                @php
                    $breakdown    = $b->breakdown ?? [];
                    $priceNight   = $breakdown['price_night']  ?? null;
                    $sst          = $breakdown['sst']          ?? null;
                    $cleaningFee  = $breakdown['cleaning_fee'] ?? null;
                    $sstCf        = $breakdown['sst_cf']       ?? null;
                    $otaFee       = $breakdown['ota_fee']      ?? null;
                    $total        = $breakdown['total']        ?? ($b->TotalAmountAfterTax ?? null);
                    $propId       = $b->property_code;
                    $propName     = $b->property_name;
                @endphp
                <tr>
                    <td class="mono" data-val="{{ $b->SubBookingId ?? '' }}">{{ $b->SubBookingId ?? '—' }}</td>
                    <td class="mono" data-val="{{ $b->folio_no ?? '' }}">{{ $b->folio_no ?? '—' }}</td>
                    <td class="mono" data-val="{{ $propId ?? '' }}">{{ $propId ?? '—' }}</td>
                    <td  data-val="{{ strtolower($propName ?? '') }}">
                        @if($propName)
                            <span style="font-weight:500;color:var(--teal)">{{ $propName }}</span>
                        @else
                            <span style="color:var(--text-secondary)">—</span>
                        @endif
                    </td>
                    <td  data-val="{{ strtolower($b->RoomTypeName ?? '') }}">{{ $b->RoomTypeName ?? '—' }}</td>
                    <td  data-val="{{ $b->Start }}">{{ $b->Start }}</td>
                    <td  data-val="{{ $b->End }}">{{ $b->End }}</td>
                    <td  data-val="{{ strtolower($b->Source ?? '') }}"><span class="badge-source">{{ $b->Source ?? '—' }}</span></td>
                    <td class="num" data-val="{{ $priceNight ?? '' }}">
                        {{ $priceNight !== null ? number_format($priceNight, 2) : '—' }}
                    </td>
                    <td class="num" data-val="{{ $sst ?? '' }}">
                        {{ $sst !== null ? number_format($sst, 2) : '—' }}
                    </td>
                    <td class="num" data-val="{{ $cleaningFee ?? '' }}">
                        {{ $cleaningFee !== null ? number_format($cleaningFee, 2) : '—' }}
                    </td>
                    <td class="num" data-val="{{ $sstCf ?? '' }}">
                        {{ $sstCf !== null ? number_format($sstCf, 2) : '—' }}
                    </td>
                    <td class="num" data-val="{{ $otaFee ?? '' }}">
                        {{ $otaFee !== null ? number_format($otaFee, 2) : '—' }}
                    </td>
                    <td class="num" style="font-weight:600" data-val="{{ $total ?? 0 }}">
                        RM {{ number_format($total ?? 0, 2) }}
                    </td>
                    <td >
                        <div class="ezee-actions">
                            <button type="button" class="btn btn-primary"
                                onclick="openAssign({{ $b->id }}, '{{ addslashes($b->FirstName.' '.$b->LastName) }}', '{{ $b->Start }}', '{{ $b->End }}', '{{ $b->book_id }}')">
                                {{ $b->book_id ? 'Reassign' : 'Assign' }}
                            </button>
                            <a href="{{ route('admin.ezee.booking.edit', $b->id) }}" class="btn btn-secondary">Edit</a>
                            <form method="POST" action="/admin/ezee/booking/{{ $b->id }}" style="margin:0"
                                onsubmit="return confirm('Delete this booking for {{ addslashes($b->FirstName.' '.$b->LastName) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-secondary" style="color:#dc2626" title="Delete">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="15" style="padding:40px;text-align:center;color:var(--text-secondary)">No EZEE bookings found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Assign Modal --}}
<div id="assign-modal" class="assign-modal">
    <div style="background:#fff;border-radius:16px;padding:28px;width:480px;max-width:90vw;box-shadow:0 20px 60px rgba(0,0,0,.2)">
        <h2 style="font-size:17px;font-weight:600;margin-bottom:4px">Assign Booking</h2>
        <p id="modal-guest" style="font-size:13px;color:var(--text-secondary);margin-bottom:20px"></p>
        <form method="POST" id="assign-form">
            @csrf
            <div class="form-group">
                <label class="form-label">Listing / Unit</label>
                @include('admin.partials.combobox', [
                    'id'          => 'unit',
                    'name'        => 'listing_id',
                    'items'       => $listings,
                    'placeholder' => 'Type to search units…',
                    'required'    => true,
                    'var'         => 'unitCombo',
                ])
            </div>
            <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label">Check In</label>
                    <input type="date" name="check_in" id="modal-checkin" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Check Out</label>
                    <input type="date" name="check_out" id="modal-checkout" class="form-input">
                </div>
            </div>
            <div id="reassign-wrap" style="display:none;margin-bottom:12px">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                    <input type="checkbox" name="reassign" value="1"> Reassign (update existing booking)
                </label>
            </div>
            <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px">
                <button type="button" class="btn btn-secondary" onclick="closeAssign()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Assignment</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ---- Assign modal ----
function openAssign(id, guest, start, end, bookId) {
    document.getElementById('modal-guest').textContent = guest + ' · ' + start + ' → ' + end;
    document.getElementById('modal-checkin').value = start;
    document.getElementById('modal-checkout').value = end;
    const route = bookId ? '/admin/ezee/bookingEdit/' + id : '/admin/ezee/booking/' + id;
    document.getElementById('assign-form').action = route;
    document.getElementById('reassign-wrap').style.display = bookId ? 'block' : 'none';
    document.getElementById('assign-modal').style.display = 'flex';
    unitCombo.reset();
}
function closeAssign() {
    document.getElementById('assign-modal').style.display = 'none';
    unitCombo.close();
}

document.getElementById('assign-modal').addEventListener('click', function(e) {
    if (e.target === this) closeAssign();
});

// ---- Search ----
function filterTable(q) {
    q = q.toLowerCase().trim();
    var rows = document.querySelectorAll('#ezee-tbody tr');
    var visible = 0;
    rows.forEach(function(row) {
        var text = row.innerText.toLowerCase();
        var show = !q || text.includes(q);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('visible-count').textContent = visible;
    document.getElementById('result-count').textContent = q ? ('Showing ' + visible + ' result' + (visible !== 1 ? 's' : '')) : '';
}

// ---- Sorting ----
var sortState = { col: null, dir: 0 };

document.querySelectorAll('th.sortable').forEach(function(th) {
    th.addEventListener('click', function() {
        var col = parseInt(this.getAttribute('data-col'));
        if (sortState.col === col) {
            sortState.dir = sortState.dir === 1 ? -1 : (sortState.dir === -1 ? 0 : 1);
        } else {
            sortState.col = col;
            sortState.dir = 1;
        }
        document.querySelectorAll('th.sortable').forEach(function(t) {
            t.classList.remove('asc', 'desc');
        });
        if (sortState.dir !== 0) {
            this.classList.add(sortState.dir === 1 ? 'asc' : 'desc');
        }
        sortTable(col, sortState.dir);
    });
});

function sortTable(col, dir) {
    var tbody = document.getElementById('ezee-tbody');
    var rows  = Array.from(tbody.querySelectorAll('tr'));
    var numericCols = [2, 8, 9, 10, 11, 12, 13];

    if (dir === 0) {
        rows.sort(function(a, b) {
            return parseInt(a.getAttribute('data-orig') || 0) - parseInt(b.getAttribute('data-orig') || 0);
        });
    } else {
        rows.forEach(function(r, i) {
            if (!r.getAttribute('data-orig')) r.setAttribute('data-orig', i);
        });
        rows.sort(function(a, b) {
            var aVal = (a.cells[col] ? a.cells[col].getAttribute('data-val') || a.cells[col].innerText : '').toLowerCase();
            var bVal = (b.cells[col] ? b.cells[col].getAttribute('data-val') || b.cells[col].innerText : '').toLowerCase();
            if (numericCols.indexOf(col) !== -1) {
                return (parseFloat(aVal) - parseFloat(bVal)) * dir;
            }
            return aVal < bVal ? -dir : (aVal > bVal ? dir : 0);
        });
    }

    rows.forEach(function(r) { tbody.appendChild(r); });
}

// Init count
document.getElementById('visible-count').textContent = document.querySelectorAll('#ezee-tbody tr').length;
</script>
@endpush
