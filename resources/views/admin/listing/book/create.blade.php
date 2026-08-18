@extends('admin.layout')
@section('title', 'Create Booking')
@section('page-title', 'Create Booking')

@push('styles')
<style>
.booking-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:0 28px; }
@media (max-width: 900px) { .booking-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1>Create Booking</h1>
        <p>Add a reservation manually</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/book" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Bookings
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
    </div>
    <div class="card-body">
        {{-- The store route takes the listing id in the path, so the action is
             rewritten from the Unit No. dropdown below. --}}
        <form method="POST" id="create-booking-form" action="/admin/listing/0/book">
            @csrf

            <div class="booking-grid">
                {{-- Guest --}}
                <div>
                    <div class="form-group">
                        <label class="form-label">Unit No.</label>
                        <select name="listing_id" id="listing-select" class="form-input" required>
                            <option value="">Select Listing</option>
                            @foreach($listings as $l)
                                <option value="{{ $l->id }}" {{ old('listing_id') == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Folio No</label>
                        <input type="text" name="folio_no" class="form-input" placeholder="Enter Folio No" value="{{ old('folio_no') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="name" class="form-input" placeholder="Enter First Name" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-input" placeholder="Enter Last Name" value="{{ old('last_name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" placeholder="Enter Guest's Email" value="{{ old('email') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-input" placeholder="Enter Phone Number" value="{{ old('phone') }}">
                    </div>
                </div>

                {{-- Stay --}}
                <div>
                    <div class="form-group">
                        <label class="form-label">Check In</label>
                        <input type="date" name="check_in" class="form-input" value="{{ old('check_in') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Check Out</label>
                        <input type="date" name="check_out" class="form-input" value="{{ old('check_out') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Adult</label>
                        <input type="number" name="adult" class="form-input" min="0" value="{{ old('adult', 1) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Infant</label>
                        <input type="number" name="infant" class="form-input" min="0" value="{{ old('infant', 0) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reservation Source</label>
                        <select name="source" class="form-input" required>
                            @foreach(\App\Support\BookingOptions::SOURCES as $s)
                                <option value="{{ $s }}" {{ old('source') === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Booking Category</label>
                        <select name="category" class="form-input">
                            @foreach(\App\Support\BookingOptions::CATEGORIES as $c)
                                <option value="{{ $c }}" {{ old('category') === $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Charges. cleaning_fee, ota_fee, sst and sst_cf are required
                     by the store validator, so they default to 0.00. --}}
                <div>
                    <div class="form-group">
                        <label class="form-label">Price Per Night</label>
                        <input type="number" step="0.01" name="price_night" class="form-input" placeholder="Example: 90.50" value="{{ old('price_night') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cleaning Fee</label>
                        <input type="number" step="0.01" name="cleaning_fee" class="form-input" placeholder="Example: 20.00" value="{{ old('cleaning_fee', '0.00') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">M&amp;A Fee</label>
                        <input type="number" step="0.01" name="ota_fee" class="form-input" placeholder="Example: 5.50" value="{{ old('ota_fee', '0.00') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SST</label>
                        <input type="number" step="0.01" name="sst" class="form-input" placeholder="Example: 10.00" value="{{ old('sst', '0.00') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SST(CF)</label>
                        <input type="number" step="0.01" name="sst_cf" class="form-input" placeholder="Example: 10.00" value="{{ old('sst_cf', '0.00') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Discount Fee</label>
                        <input type="number" step="0.01" name="discount_fee" class="form-input" placeholder="Example: 5.50" value="{{ old('discount_fee', '0.00') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Price</label>
                        <input type="number" step="0.01" name="price" class="form-input" placeholder="Example: 120" value="{{ old('price') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Remark</label>
                        <textarea name="remark" class="form-input" rows="3">{{ old('remark') }}</textarea>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:8px">
                <button type="submit" class="btn btn-primary">Create</button>
                <button type="reset" class="btn btn-secondary">Clear</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
// store() reads the listing from the URL path as well as the posted field,
// so both have to stay in step.
(function () {
    var form   = document.getElementById('create-booking-form');
    var select = document.getElementById('listing-select');

    function syncAction() {
        form.action = '/admin/listing/' + (select.value || 0) + '/book';
    }

    select.addEventListener('change', syncAction);
    syncAction();
})();
</script>
@include('admin.listing.book._fees', [
    'formId'   => 'create-booking-form',
    'bookedOn' => date('Y-m-d'),
])
@endpush
