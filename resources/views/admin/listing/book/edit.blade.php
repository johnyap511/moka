@extends('admin.layout')
@section('title', 'Edit Booking')
@section('page-title', 'Edit Booking')

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
    <div class="badge badge-green" style="margin-bottom:16px;display:block;padding:10px">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="badge badge-red" style="margin-bottom:16px;display:block;padding:10px">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-header">
        <h2>Booking Details</h2>
        @if($user)
            <span class="badge badge-blue">{{ $user->name }}</span>
        @endif
    </div>
    <div class="card-body">
        <form action="/admin/book/{{ $book->id }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label>Check In</label>
                    <input type="date" name="check_in" class="form-input" value="{{ old('check_in', $book->check_in) }}" required>
                </div>
                <div class="form-group">
                    <label>Check Out</label>
                    <input type="date" name="check_out" class="form-input" value="{{ old('check_out', $book->check_out) }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Total Price (RM)</label>
                    <input type="number" name="total_price" class="form-input" value="{{ old('total_price', $book->total_price) }}" step="0.01">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-select">
                        <option value="0" {{ ($book->status ?? '') == 0 ? 'selected' : '' }}>Cancelled</option>
                        <option value="1" {{ ($book->status ?? '') == 1 ? 'selected' : '' }}>New</option>
                        <option value="2" {{ ($book->status ?? '') == 2 ? 'selected' : '' }}>Processing</option>
                        <option value="3" {{ ($book->status ?? '') == 3 ? 'selected' : '' }}>Pending</option>
                        <option value="5" {{ ($book->status ?? '') == 5 ? 'selected' : '' }}>Confirmed</option>
                        <option value="7" {{ ($book->status ?? '') == 7 ? 'selected' : '' }}>Checked In</option>
                        <option value="9" {{ ($book->status ?? '') == 9 ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Notes / Remarks</label>
                <textarea name="notes" class="form-textarea" rows="3">{{ old('notes', $book->notes ?? '') }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Update Booking</button>
        </form>
    </div>
</div>

@endsection
