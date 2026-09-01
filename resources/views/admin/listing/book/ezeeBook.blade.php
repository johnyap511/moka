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
    <div class="flex gap-2">
        @if($conflictTotal > 0)
            <a href="{{ route('admin.ezee.assignment-log', ['method' => 'conflict']) }}"
               class="btn btn-secondary" style="border-color:#f59e0b;color:#b45309">
                {{ $conflictTotal }} need review
            </a>
        @endif
        <button type="button" class="btn btn-primary" id="btn-auto-assign" onclick="runAutoAssign()">
            Auto-Assign Unassigned
        </button>
    </div>
</div>

{{-- Reservations automatic assignment refused because the unit was already
     taken. Nothing was changed for these; a person has to decide. --}}
@if($conflicts->isNotEmpty())
<div style="margin-bottom:16px;padding:12px 14px;background:#fffbeb;border:1px solid #fcd34d;border-radius:8px">
    <strong style="font-size:13px">{{ $conflicts->count() }} booking(s) on this page could not be assigned automatically</strong>
    <div style="font-size:12px;color:#92400e;margin-top:4px">
        The unit was already occupied over those dates, so nothing was changed. Each is marked
        <span class="badge" style="background:#fef3c7;color:#92400e">Needs review</span> below — assign it to a
        different unit, or resolve the clash in EZEE.
    </div>
</div>
@endif

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
    <form method="GET" style="margin-left:auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        {{-- Server-side now: with 60k bookings the list is paginated, so
             filtering only the visible page would be misleading. --}}
        <div class="search-bar" style="min-width:240px">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Guest, res no, folio, unit, source…">
        </div>
        <input type="date" name="from" value="{{ $from ?? '' }}" class="form-input" style="width:auto" title="Check-out on or after">
        <input type="date" name="to"   value="{{ $to ?? '' }}"   class="form-input" style="width:auto" title="Check-in on or before">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        @if(request()->hasAny(['q','from','to']))
            <a href="{{ request()->url() }}" class="btn btn-secondary btn-sm">Reset</a>
        @endif
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2>Bookings <span class="badge badge-blue">{{ number_format($books->total()) }}</span></h2>
    </div>
    <div class="table-wrap">
        <table class="ezee-table" id="ezee-table">
            <colgroup>
                <col style="width:6%">   {{-- Res No --}}
                <col style="width:6%">   {{-- Folio --}}
                <col style="width:4%">   {{-- Prop ID --}}
                <col style="width:9%">   {{-- Property --}}
                <col style="width:10%">  {{-- Assigned Unit --}}
                <col style="width:8%">   {{-- Room Type --}}
                <col style="width:5%">   {{-- Check In --}}
                <col style="width:5%">   {{-- Check Out --}}
                <col style="width:7%">   {{-- Source --}}
                <col style="width:6%">   {{-- Rate/Night --}}
                <col style="width:5%">   {{-- SST --}}
                <col style="width:6%">   {{-- Cleaning --}}
                <col style="width:5%">   {{-- SST(CF) --}}
                <col style="width:5%">   {{-- M&A --}}
                <col style="width:6%">   {{-- Total --}}
                <col style="width:7%">   {{-- Action --}}
            </colgroup>
            <thead>
                <tr>
                    <th class="sortable" data-col="0">Res No<i class="sort-icon"></i></th>
                    <th class="sortable" data-col="1">Folio<i class="sort-icon"></i></th>
                    <th class="sortable" data-col="2">Prop ID<i class="sort-icon"></i></th>
                    <th class="sortable" data-col="3">Property<i class="sort-icon"></i></th>
                    <th class="sortable" data-col="4">Assigned Unit<i class="sort-icon"></i></th>
                    <th class="sortable" data-col="5">Room Type<i class="sort-icon"></i></th>
                    <th class="sortable" data-col="6">Check In<i class="sort-icon"></i></th>
                    <th class="sortable" data-col="7">Check Out<i class="sort-icon"></i></th>
                    <th class="sortable" data-col="8">Source<i class="sort-icon"></i></th>
                    <th class="sortable num" data-col="9">Rate/Night<i class="sort-icon"></i></th>
                    <th class="sortable num" data-col="10">SST<i class="sort-icon"></i></th>
                    <th class="sortable num" data-col="11">Cleaning<i class="sort-icon"></i></th>
                    <th class="sortable num" data-col="12">SST(CF)<i class="sort-icon"></i></th>
                    <th class="sortable num" data-col="13">M&amp;A<i class="sort-icon"></i></th>
                    <th class="sortable num" data-col="14">Total<i class="sort-icon"></i></th>
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
                    <td data-val="{{ strtolower($b->assigned_unit ?? $b->RoomName ?? '') }}">
                        @if($b->assigned_unit)
                            <a href="/admin/listing/{{ $b->assigned_listing_id }}/edit"
                               style="color:var(--teal);font-weight:500">{{ $b->assigned_unit }}</a>
                        @elseif($b->RoomName)
                            {{-- Not assigned yet, but EZEE told us the unit. --}}
                            <span class="mono" style="color:var(--text-secondary)" title="EZEE unit, not yet assigned">{{ $b->RoomName }}</span>
                            @if($conflicts->has($b->id))
                                <div class="badge" style="background:#fef3c7;color:#92400e;margin-top:4px;display:inline-block"
                                     title="{{ $conflicts[$b->id]->note }}">Needs review</div>
                            @endif
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
                            {{-- Thirteen values would be unreadable as positional
                                 arguments, so the row carries them as data. --}}
                            <button type="button" class="btn btn-primary"
                                data-booking="{{ json_encode([
                                    'id'           => $b->id,
                                    'guest'        => trim($b->FirstName . ' ' . $b->LastName),
                                    'book_id'      => $b->book_id,
                                    'folio_no'     => $b->folio_no,
                                    'start'        => $b->Start,
                                    'end'          => $b->End,
                                    'source'       => $b->Source,
                                    'booked_on'    => optional($b->created_at)->format('Y-m-d'),
                                    'price_night'  => $priceNight,
                                    'cleaning_fee' => $cleaningFee,
                                    'ota_fee'      => $otaFee,
                                    'sst'          => $sst,
                                    'sst_cf'       => $sstCf,
                                    'discount'     => $b->TotalDiscount,
                                    'total'        => $total,
                                ]) }}"
                                onclick="openAssign(this)">
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
                <tr><td colspan="16" style="padding:40px;text-align:center;color:var(--text-secondary)">No EZEE bookings found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($books->lastPage() > 1)
        <div class="card-body" style="padding-top:0;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <span class="text-sm text-secondary">
                Showing {{ $books->firstItem() }}–{{ $books->lastItem() }} of {{ number_format($books->total()) }}
            </span>
            <div style="display:flex;align-items:center;gap:6px">
                @if($books->onFirstPage())
                    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default">← Prev</span>
                @else
                    <a href="{{ $books->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Prev</a>
                @endif

                @foreach($books->getUrlRange(max(1, $books->currentPage() - 2), min($books->lastPage(), $books->currentPage() + 2)) as $page => $url)
                    @if($page == $books->currentPage())
                        <span class="btn btn-primary btn-sm">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="btn btn-secondary btn-sm">{{ $page }}</a>
                    @endif
                @endforeach

                @if($books->hasMorePages())
                    <a href="{{ $books->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
                @else
                    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default">Next →</span>
                @endif
            </div>
        </div>
    @endif
</div>

{{-- Assign Modal.

     Field names match what ezeeBookingStoreEdit reads. It expects sixteen and
     the modal previously sent three, so reassigning wrote nulls over the fee
     columns. Values are pre-filled from the same breakdown the table shows and
     stay editable, with the fee calculator recomputing as they change. --}}
<div id="assign-modal" class="assign-modal">
    <div style="background:#fff;border-radius:16px;padding:24px;width:620px;max-width:94vw;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2)">
        <h2 style="font-size:17px;font-weight:600;margin-bottom:4px">Assign Booking</h2>
        <p id="modal-guest" style="font-size:13px;color:var(--text-secondary);margin-bottom:18px"></p>

        <form method="POST" id="assign-form">
            @csrf
            {{-- Read by the fee calculator; rates changed over time. --}}
            <input type="hidden" name="booked_on" id="modal-booked-on">

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

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Folio No</label>
                    <input type="text" name="folio_no" id="modal-folio" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Reservation Source</label>
                    <select name="source" id="modal-source" class="form-select">
                        @foreach(\App\Support\BookingOptions::SOURCES as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Check In</label>
                    <input type="date" name="check_in" id="modal-checkin" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Check Out</label>
                    <input type="date" name="check_out" id="modal-checkout" class="form-input">
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label class="form-label">Booking Category</label>
                    <select name="category" class="form-select">
                        @foreach(\App\Support\ListingOptions::CATEGORIES as $label)
                            <option value="{{ $label }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Adult</label>
                    <input type="number" name="adult" id="modal-adult" class="form-input" min="0" value="2">
                </div>
                <div class="form-group">
                    <label class="form-label">Infant</label>
                    <input type="number" name="infant" id="modal-infant" class="form-input" min="0" value="0">
                </div>
            </div>

            <div class="divider"></div>

            <div class="form-row-3">
                <div class="form-group">
                    <label class="form-label">Price Per Night</label>
                    <input type="number" step="0.01" name="price_night" id="modal-price-night" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Cleaning Fee</label>
                    <input type="number" step="0.01" name="cleaning_fee" id="modal-cleaning" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">M&amp;A Fee</label>
                    <input type="number" step="0.01" name="ota_fee" id="modal-ota" class="form-input">
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label class="form-label">SST</label>
                    <input type="number" step="0.01" name="sst" id="modal-sst" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">SST(CF)</label>
                    <input type="number" step="0.01" name="sst_cf" id="modal-sstcf" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Discount Fee</label>
                    <input type="number" step="0.01" name="discount_fee" id="modal-discount" class="form-input">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Total Price</label>
                <input type="number" step="0.01" name="price" id="modal-total" class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Remark</label>
                <textarea name="remark" id="modal-remark" class="form-input" rows="2"></textarea>
            </div>

            <div id="reassign-wrap" style="display:none;margin-bottom:12px">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                    <input type="checkbox" name="reassign" value="1"> Reassign (update existing booking)
                </label>
            </div>

            <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px">
                <button type="button" class="btn btn-secondary" onclick="closeAssign()">Cancel</button>
                <button type="submit" class="btn btn-primary">Assign</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ---- Assign modal ----
function openAssign(btn) {
    var d = JSON.parse(btn.getAttribute("data-booking"));

    function set(id, value) {
        var el = document.getElementById(id);
        if (el) el.value = (value === null || value === undefined) ? "" : value;
    }

    document.getElementById("modal-guest").textContent = d.guest + " · " + d.start + " → " + d.end;

    set("modal-folio",       d.folio_no);
    set("modal-checkin",     d.start);
    set("modal-checkout",    d.end);
    set("modal-price-night", d.price_night);
    set("modal-cleaning",    d.cleaning_fee);
    set("modal-ota",         d.ota_fee);
    set("modal-sst",         d.sst);
    set("modal-sstcf",       d.sst_cf);
    set("modal-discount",    d.discount);
    set("modal-total",       d.total);
    set("modal-remark",      d.source);
    set("modal-booked-on",   d.booked_on);

    // EZEE appends booking references to some source names, so keep whatever
    // it sent rather than silently switching the booking to another channel.
    var source = document.getElementById("modal-source");
    if (source && d.source) {
        var known = Array.prototype.some.call(source.options, function (o) { return o.value === d.source; });
        if (!known) source.add(new Option(d.source, d.source));
        source.value = d.source;
    }

    var form = document.getElementById("assign-form");
    form.action = d.book_id ? "/admin/ezee/bookingEdit/" + d.id : "/admin/ezee/booking/" + d.id;

    document.getElementById("reassign-wrap").style.display = d.book_id ? "block" : "none";
    document.getElementById("assign-modal").style.display = "flex";
    unitCombo.reset();

    // Values above were set programmatically, so nothing counts as a manual
    // override for this booking.
    if (window.assignFees) assignFees.clearOverrides();
}
function closeAssign() {
    document.getElementById('assign-modal').style.display = 'none';
    unitCombo.close();
}

document.getElementById('assign-modal').addEventListener('click', function(e) {
    if (e.target === this) closeAssign();
});

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

</script>
@include('admin.listing.book._fees', ['formId' => 'assign-form', 'bookedOn' => date('Y-m-d'), 'var' => 'assignFees'])
<script>
</script>
@endpush

@push('scripts')
<script>
// Same endpoint the Room Mapping screen uses. This is where the team works
// through unassigned bookings, so the action belongs here too.
function runAutoAssign() {
    if (!confirm('Assign every unassigned EZEE booking that has a mapped unit?\n\nBookings whose unit is already occupied are left alone and flagged for review.')) {
        return;
    }

    var btn = document.getElementById('btn-auto-assign');
    btn.disabled = true;
    btn.textContent = 'Assigning…';

    fetch('{{ route('admin.ezee.auto-assign') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({})
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        alert(res.message || 'Done.');
        if (res.ok) { window.location.reload(); }
    })
    .catch(function (e) { alert('Could not run: ' + e.message); })
    .finally(function () {
        btn.disabled = false;
        btn.textContent = 'Auto-Assign Unassigned';
    });
}
</script>
@endpush
