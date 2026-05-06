@extends('admin.layout')
@section('title', 'Create Booking')
@section('page-title', 'Create Booking')

@section('content')

<div class="page-header">
    <div>
        <h1>Create Booking</h1>
        @if($listing)
            <p>{{ $listing->title ?? $listing->name ?? 'Listing #'.$listing->id }}</p>
        @endif
    </div>
    <div class="flex gap-2">
        <a href="/admin/book" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Bookings
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Booking Details</h2>
        @if($listing)
            <span class="badge badge-blue">{{ $listing->title ?? $listing->name ?? 'Listing #'.$listing->id }}</span>
        @endif
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="badge badge-green" style="margin-bottom:16px;display:block;padding:10px">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="badge badge-red" style="margin-bottom:16px;display:block;padding:10px">{{ session('error') }}</div>
        @endif

        <form action="/admin/listing/{{ $listing->id ?? 0 }}/book" method="POST">
            @csrf

            <input type="hidden" name="listing_id" value="{{ $listing->id ?? '' }}">

            <div class="form-row">
                <div class="form-group">
                    <label>Guest Name</label>
                    <input type="text" name="guest_name" class="form-input" value="{{ old('guest_name') }}" required>
                </div>
                <div class="form-group">
                    <label>Guest Email</label>
                    <input type="email" name="guest_email" class="form-input" value="{{ old('guest_email') }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Guest Phone</label>
                    <input type="text" name="guest_phone" class="form-input" value="{{ old('guest_phone') }}">
                </div>
                <div class="form-group">
                    <label>Number of Guests</label>
                    <input type="number" name="guest_count" class="form-input" value="{{ old('guest_count', 1) }}" min="1">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Check In Date</label>
                    <input type="date" name="check_in" class="form-input" value="{{ old('check_in') }}" required>
                </div>
                <div class="form-group">
                    <label>Check Out Date</label>
                    <input type="date" name="check_out" class="form-input" value="{{ old('check_out') }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Total Price (RM)</label>
                    <input type="number" name="total_price" class="form-input" value="{{ old('total_price') }}" step="0.01">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-select">
                        <option value="1">New</option>
                        <option value="3">Pending</option>
                        <option value="5">Confirmed</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Notes / Remarks</label>
                <textarea name="notes" class="form-textarea" rows="3">{{ old('notes') }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Create Booking</button>
        </form>
    </div>
</div>

@endsection
