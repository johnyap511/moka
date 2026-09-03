@extends('admin.layout')
@section('title', 'Edit EZEE Booking')
@section('page-title', 'Edit EZEE Booking')

@section('content')

<div class="page-header">
    <div>
        <h1>Edit EZEE Booking</h1>
        <p>{{ $booking->SubBookingId ?? 'Booking #'.$booking->id }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ $booking->status == 8 ? '/admin/ezee/assigned_booking' : '/admin/ezee/unassigned_booking' }}" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-error" style="margin-bottom:16px">
    <ul style="margin:0;padding-left:18px">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h2>Booking Details</h2>
        @if($booking->status == 8)
            <span class="badge badge-green">Assigned</span>
        @else
            <span class="badge badge-orange">Unassigned</span>
        @endif
    </div>
    <div class="card-body">
        {{-- Field names match EzeeBookingController::update()'s validation
             exactly; anything else it ignores. --}}
        <form method="POST" action="{{ route('admin.ezee.booking.update', $booking->id) }}">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="FirstName">First Name <span style="color:var(--red)">*</span></label>
                    <input type="text" id="FirstName" name="FirstName" class="form-input"
                           value="{{ old('FirstName', $booking->FirstName) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="LastName">Last Name <span style="color:var(--red)">*</span></label>
                    <input type="text" id="LastName" name="LastName" class="form-input"
                           value="{{ old('LastName', $booking->LastName) }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="Email">Email</label>
                    <input type="email" id="Email" name="Email" class="form-input"
                           value="{{ old('Email', $booking->Email) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="Mobile">Mobile</label>
                    <input type="text" id="Mobile" name="Mobile" class="form-input"
                           value="{{ old('Mobile', $booking->Mobile) }}">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="Start">Check In <span style="color:var(--red)">*</span></label>
                    <input type="date" id="Start" name="Start" class="form-input"
                           value="{{ old('Start', $booking->Start) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="End">Check Out <span style="color:var(--red)">*</span></label>
                    <input type="date" id="End" name="End" class="form-input"
                           value="{{ old('End', $booking->End) }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="Source">Source <span style="color:var(--red)">*</span></label>
                    <select id="Source" name="Source" class="form-select" required>
                        @php $current = old('Source', $booking->Source); @endphp
                        @foreach(\App\Support\BookingOptions::SOURCES as $s)
                            <option value="{{ $s }}" {{ $current === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                        {{-- EZEE appends booking references to some source names, so
                             keep whatever is stored even if it is not in the list. --}}
                        @if($current && !in_array($current, \App\Support\BookingOptions::SOURCES, true))
                            <option value="{{ $current }}" selected>{{ $current }}</option>
                        @endif
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="TotalAmountBeforeTax">Amount Before Tax <span style="color:var(--red)">*</span></label>
                    <input type="number" step="0.01" id="TotalAmountBeforeTax" name="TotalAmountBeforeTax" class="form-input"
                           value="{{ old('TotalAmountBeforeTax', $booking->TotalAmountBeforeTax) }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="TotalExtraCharge">Cleaning Fee</label>
                    <input type="number" step="0.01" id="TotalExtraCharge" name="TotalExtraCharge" class="form-input"
                           value="{{ old('TotalExtraCharge', $booking->TotalExtraCharge) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="TotalDiscount">Discount</label>
                    <input type="number" step="0.01" id="TotalDiscount" name="TotalDiscount" class="form-input"
                           value="{{ old('TotalDiscount', $booking->TotalDiscount) }}">
                </div>
            </div>

            <div class="divider"></div>

            {{-- Read-only context; update() does not accept these. --}}
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Reservation No</label>
                    <input type="text" class="form-input" value="{{ $booking->SubBookingId }}" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Room Type</label>
                    <input type="text" class="form-input" value="{{ $booking->RoomTypeName }}" disabled>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">EZEE Unit</label>
                    <input type="text" class="form-input" value="{{ $booking->RoomName ?: '—' }}" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Folio No</label>
                    <input type="text" class="form-input" value="{{ $booking->folio_no ?: '—' }}" disabled>
                </div>
            </div>

            <div class="flex gap-2" style="justify-content:flex-end;margin-top:8px">
                <a href="{{ $booking->status == 8 ? '/admin/ezee/assigned_booking' : '/admin/ezee/unassigned_booking' }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

@php
    $linked = $booking->book_id ? \App\Booking::withoutGlobalScopes()->with('listing')->find($booking->book_id) : null;
    $segments = $linked ? \App\Booking::withoutGlobalScopes()->with('listing')->where('status', '<>', 1)
        ->where(fn ($q) => $q->where('id', $linked->id)->orWhere(fn ($w) => $w->where('folio_no', $linked->folio_no)->where('folio_no', '<>', '')->where('listing_id', $linked->listing_id)))
        ->orderBy('check_in')->get() : collect();
    $units = \App\Listing::orderBy('name')->get(['id', 'name']);
@endphp
@if($linked)
<div class="card" style="margin-top:16px">
    <div class="card-header"><h2>Split Stay</h2></div>
    <div class="card-body" style="font-size:13px">
        <p style="margin:0 0 10px;color:var(--text-secondary)">EZEE reports one room for the whole reservation. If the guest spent some nights in another unit or an extra room, move those nights here. Amounts are re-derived from the stamped nightly rate; cleaning stays with the first night; the channel fee follows the nights.</p>
        <table style="font-size:12px;margin-bottom:12px">
            <thead><tr><th>Booking</th><th>Unit</th><th>Check in</th><th>Check out</th><th>Nights</th><th>Total</th></tr></thead>
            <tbody>
            @foreach($segments as $s)
                <tr><td>#{{ $s->id }}</td><td>{{ $s->listing->name ?? '' }}</td><td>{{ $s->check_in }}</td><td>{{ $s->check_out }}</td><td>{{ $s->nights }}</td><td>RM {{ number_format($s->price, 2) }}</td></tr>
            @endforeach
            </tbody>
        </table>
        <div style="display:grid;grid-template-columns:repeat(4,max-content) auto;gap:10px;align-items:end">
            <label style="display:grid;gap:4px">Segment
                <select id="split-booking">
                    @foreach($segments as $s)<option value="{{ $s->id }}" data-in="{{ $s->check_in }}" data-out="{{ $s->check_out }}">#{{ $s->id }} {{ $s->check_in }} → {{ $s->check_out }}</option>@endforeach
                </select>
            </label>
            <label style="display:grid;gap:4px">First night elsewhere <input type="date" id="split-from" value="{{ $segments->first()->check_in ?? '' }}"></label>
            <label style="display:grid;gap:4px">Morning back <input type="date" id="split-to" value="{{ $segments->first() ? \Carbon\Carbon::parse($segments->first()->check_in)->addDay()->format('Y-m-d') : '' }}"></label>
            <label style="display:grid;gap:4px">Where
                <select id="split-unit" style="max-width:260px">
                    <option value="">Extra room (no unit)</option>
                    @foreach($units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                </select>
            </label>
            <div><button type="button" class="btn btn-primary" onclick="splitStay(this)">Move those nights</button></div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
async function splitStay(btn) {
    var id   = document.getElementById('split-booking').value;
    var from = document.getElementById('split-from').value;
    var to   = document.getElementById('split-to').value;
    var unit = document.getElementById('split-unit').value;
    if (!from || !to || to <= from) { alert('Pick the first night elsewhere and the morning the guest came back.'); return; }
    if (!confirm('Move ' + from + ' to ' + to + ' to ' + (unit ? document.querySelector('#split-unit option:checked').textContent : 'an extra room (no unit)') + '?')) { return; }
    btn.disabled = true;
    try {
        const res = await fetch('/admin/booking/' + id + '/split', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: JSON.stringify({ from: from, to: to, listing_id: unit || null }) });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) { throw new Error(data.message || 'Request failed'); }
        alert(data.message); window.location.reload();
    } catch (e) { alert('Not done: ' + e.message); btn.disabled = false; }
}
</script>
@endpush

