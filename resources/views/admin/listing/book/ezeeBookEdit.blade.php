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

@endsection
