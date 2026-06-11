@extends('admin.layout')
@section('title', 'Edit Owner: ' . $user->name)
@section('page-title', 'Edit Owner')

@section('content')

@php
    $listings = App\Models\Listing::where('user_id', $user->id)->get();
@endphp

<div class="page-header">
    <div>
        <h1>Edit Owner: {{ $user->name }}</h1>
        <p>Update owner account information</p>
    </div>
    <a href="/admin/owners" class="btn btn-secondary">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

    {{-- Edit Form --}}
    <div class="card">
        <div class="card-header">
            <h2>Account Information</h2>
        </div>
        <div class="card-body">
            <form action="/admin/owners/{{ $user->id }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="name">Full Name</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-input"
                            value="{{ old('name', $user->name) }}"
                            placeholder="Full name"
                            required
                        >
                        @error('name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            value="{{ old('email', $user->email) }}"
                            placeholder="email@example.com"
                            required
                        >
                        @error('email')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        class="form-input"
                        value="{{ old('phone', $user->phone) }}"
                        placeholder="+60 12-345 6789"
                    >
                    @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="divider"></div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="password">New Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Leave blank to keep current"
                        >
                        <div class="form-help">Leave blank to keep the current password.</div>
                        @error('password')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password_confirmation">Confirm Password</label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-input"
                            placeholder="Repeat new password"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Account Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="1" {{ old('status', $user->status) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status', $user->status) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="divider"></div>

                {{-- Preserve fields not shown in this form --}}
                <input type="hidden" name="last_name" value="{{ $user->last_name }}">
                <input type="hidden" name="country_code" value="{{ $user->country_code }}">

                <div class="flex justify-between items-center">
                    <a href="/admin/owners" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>

    {{-- Listings Quick-list --}}
    <div class="card">
        <div class="card-header">
            <h2>Listings</h2>
            <a href="/admin/owners/{{ $user->id }}/listing" class="btn btn-secondary btn-sm">View All</a>
        </div>
        @if($listings->isEmpty())
            <div class="empty-state" style="padding:32px 20px">
                <svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <p>No listings</p>
                <small>This owner has no properties yet.</small>
            </div>
        @else
            <div style="padding:0">
                @foreach($listings as $listing)
                <div style="padding:12px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:8px">
                    <div>
                        <div class="font-600" style="font-size:13.5px">{{ $listing->title ?? 'Listing #' . $listing->id }}</div>
                        @if(isset($listing->location) || isset($listing->address))
                            <div class="text-sm text-secondary" style="margin-top:2px">{{ $listing->location ?? $listing->address ?? '' }}</div>
                        @endif
                    </div>
                    @if(isset($listing->status))
                        @if($listing->status == 1)
                            <span class="badge badge-green">Active</span>
                        @else
                            <span class="badge badge-gray">Inactive</span>
                        @endif
                    @endif
                </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

@endsection
