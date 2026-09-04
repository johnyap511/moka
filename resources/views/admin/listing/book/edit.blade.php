@extends('admin.layout')
@section('title', 'Edit Booking')
@section('page-title', 'Edit Booking')

@push('styles')
<style>
.booking-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:0 28px; }
@media (max-width: 900px) { .booking-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1>Edit Booking #{{ $book->id }}</h1>
        <p>Update reservation details</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/book/{{ $book->id }}" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-error" style="margin-bottom:16px">{{ session('error') }}</div>
@endif
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
        @if($user)
            <span class="badge badge-blue">{{ trim($user->name . ' ' . $user->last_name) }}</span>
        @endif
    </div>
    <div class="card-body">
        <form action="/admin/book/{{ $book->id }}" method="POST" id="edit-booking-form">
            @csrf
            @method('PUT')

            <div class="booking-grid">
                {{-- Guest --}}
                <div>
                    <div class="form-group">
                        <label class="form-label">Folio No</label>
                        <input type="text" name="folio_no" class="form-input" placeholder="Enter Folio No" value="{{ old('folio_no', $book->folio_no) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="name" class="form-input" placeholder="Enter First Name" value="{{ old('name', $user->name ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-input" placeholder="Enter Last Name" value="{{ old('last_name', $user->last_name ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" placeholder="Enter Guest's Email" value="{{ old('email', $user->email ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-input" placeholder="Enter Phone Number" value="{{ old('phone', $user->phone ?? '') }}">
                    </div>
                </div>

                {{-- Stay --}}
                <div>
                    <div class="form-group">
                        <label class="form-label">Check In</label>
                        <input type="date" name="check_in" class="form-input" value="{{ old('check_in', $book->check_in) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Check Out</label>
                        <input type="date" name="check_out" class="form-input" value="{{ old('check_out', $book->check_out) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Adult</label>
                        <input type="number" name="adult" class="form-input" min="0" value="{{ old('adult', $book->adult ?? 1) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Infant</label>
                        <input type="number" name="infant" class="form-input" min="0" value="{{ old('infant', $book->infant ?? 0) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reservation Source</label>
                        <select name="source" class="form-input">
                            @foreach(\App\Support\BookingOptions::SOURCES as $s)
                                <option value="{{ $s }}" {{ old('source', $book->source) === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Booking Category</label>
                        <select name="category" class="form-input">
                            @foreach(\App\Support\BookingOptions::CATEGORIES as $c)
                                <option value="{{ $c }}" {{ old('category', $book->category) === $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Charges --}}
                <div>
                    <div class="form-group">
                        <label class="form-label">Price Per Night</label>
                        <input type="number" step="0.01" name="price_night" class="form-input" placeholder="Example: 90.50" value="{{ old('price_night', $book->price_night) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cleaning Fee</label>
                        <input type="number" step="0.01" name="cleaning_fee" class="form-input" placeholder="Example: 20.00" value="{{ old('cleaning_fee', $book->cleaning_fee) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">M&amp;A Fee</label>
                        <input type="number" step="0.01" name="ota_fee" class="form-input" placeholder="Example: 5.50" value="{{ old('ota_fee', $book->ota_fee) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SST</label>
                        <input type="number" step="0.01" name="sst" class="form-input" placeholder="Example: 10.00" value="{{ old('sst', $book->sst) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SST(CF)</label>
                        <input type="number" step="0.01" name="sst_cf" class="form-input" placeholder="Example: 10.00" value="{{ old('sst_cf', $book->sst_cf) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Discount Fee</label>
                        <input type="number" step="0.01" name="discount_fee" class="form-input" placeholder="Example: 5.50" value="{{ old('discount_fee', $book->discount_fee) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Price</label>
                        <input type="number" step="0.01" name="price" class="form-input" placeholder="Example: 120" value="{{ old('price', $book->price) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Remark</label>
                        <textarea name="remark" class="form-input" rows="3">{{ old('remark', $book->remark ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:8px">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="/admin/book" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@php
    // Every piece of this stay: same MOKA folio, in the same unit or with touching dates.
    $pieces = \App\Booking::withoutGlobalScopes()->with('listing')->where('status', '<>', 1)
        ->where(fn ($q) => $q->where('id', $book->id)->orWhere(fn ($w) => $w->where('folio_no', $book->folio_no)->where('folio_no', '<>', '')
            ->where(fn ($x) => $x->where('listing_id', $book->listing_id)->orWhere('check_in', $book->check_out)->orWhere('check_out', $book->check_in))))
        ->orderBy('check_in')->get();
    $units = \App\Listing::orderBy('name')->get(['id', 'name']);
@endphp
<div class="card" style="margin-top:16px">
    <div class="card-header"><h2>Unit</h2></div>
    <div class="card-body" style="font-size:13px">
        <p style="margin:0 0 10px;color:var(--text-secondary)"><b>Reassign</b> moves this booking to another unit as it is. <b>Split</b> moves some of its nights to another unit (a room move): amounts follow the stamped nightly rate, cleaning stays with the first night, the channel fee follows the nights. Both check for clashes before saving.</p>
        <table style="font-size:12px;margin-bottom:12px">
            <thead><tr><th>Booking</th><th>Unit</th><th>Check in</th><th>Check out</th><th>Nights</th><th>Total</th></tr></thead>
            <tbody>
            @foreach($pieces as $s)
                <tr @if($s->id == $book->id) style="font-weight:600" @endif><td>#{{ $s->id }}</td><td>{{ $s->listing->name ?? '' }}</td><td>{{ $s->check_in }}</td><td>{{ $s->check_out }}</td><td>{{ $s->nights }}</td><td>RM {{ number_format($s->price, 2) }}</td></tr>
            @endforeach
            </tbody>
        </table>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,max-content));gap:10px;align-items:end;margin-bottom:14px">
            <label style="display:grid;gap:4px">Reassign booking #{{ $book->id }} to
                <select id="move-unit" style="max-width:260px">
                    @foreach($units as $u)<option value="{{ $u->id }}" @if($u->id == $book->listing_id) selected @endif>{{ $u->name }}</option>@endforeach
                </select>
            </label>
            <div><button type="button" class="btn btn-secondary" onclick="moveBooking(this)">Reassign</button></div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,max-content));gap:10px;align-items:end">
            <label style="display:grid;gap:4px">Split: first night elsewhere <input type="date" id="split-from" value="{{ $book->check_in }}" min="{{ $book->check_in }}" max="{{ $book->check_out }}"></label>
            <label style="display:grid;gap:4px">Morning back <input type="date" id="split-to" value="{{ \Carbon\Carbon::parse($book->check_in)->addDay()->format('Y-m-d') }}" min="{{ $book->check_in }}" max="{{ $book->check_out }}"></label>
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

@endsection

@push('scripts')
<script>
async function postJson(btn, url, body) {
    btn.disabled = true;
    try {
        const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: JSON.stringify(body) });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) { throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Request failed')); }
        alert(data.message); window.location.reload();
    } catch (e) { alert('Not done: ' + e.message); btn.disabled = false; }
}
function moveBooking(btn) {
    var sel = document.getElementById('move-unit');
    if (!sel.value) { alert('Pick the unit.'); return; }
    if (!confirm('Move booking #{{ $book->id }} ({{ $book->check_in }} to {{ $book->check_out }}) to ' + sel.options[sel.selectedIndex].textContent + '?')) { return; }
    postJson(btn, '/admin/booking/{{ $book->id }}/reassign', { listing_id: sel.value });
}
function splitStay(btn) {
    var from = document.getElementById('split-from').value, to = document.getElementById('split-to').value, unit = document.getElementById('split-unit').value;
    if (!from || !to || to <= from) { alert('Pick the first night elsewhere and the morning the guest came back.'); return; }
    if (!confirm('Move ' + from + ' to ' + to + ' to ' + (unit ? document.querySelector('#split-unit option:checked').textContent : 'an extra room (no unit)') + '?')) { return; }
    postJson(btn, '/admin/booking/{{ $book->id }}/split', { from: from, to: to, listing_id: unit || null });
}
</script>
{{-- Rates changed over time, so an old booking must recalculate against the
     date it was created, not today. --}}
@include('admin.listing.book._fees', [
    'formId'   => 'edit-booking-form',
    'bookedOn' => optional($book->created_at)->format('Y-m-d') ?? date('Y-m-d'),
])
@endpush
