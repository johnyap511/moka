@extends('admin.layout')
@section('title', 'Add Owner')
@section('page-title', 'Add Owner')

@section('content')

<div class="page-header">
    <div>
        <h1>Add Owner</h1>
        <p>Create a new property owner account</p>
    </div>
    <a href="/admin/owners" class="btn btn-secondary">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back
    </a>
</div>

<div style="max-width:640px">
    <div class="card">
        <div class="card-header">
            <h2>Owner Details</h2>
        </div>
        <div class="card-body">
            <form action="/admin/owners" method="POST">
                @csrf

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="name">Full Name</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-input"
                            value="{{ old('name') }}"
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
                            value="{{ old('email') }}"
                            placeholder="email@example.com"
                            required
                        >
                        @error('email')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Min. 8 characters"
                            required
                        >
                        @error('password')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password_confirmation">Confirm Password</label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-input"
                            placeholder="Repeat password"
                            required
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            class="form-input"
                            value="{{ old('phone') }}"
                            placeholder="+60 12-345 6789"
                        >
                        @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="status">Account Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="1" {{ old('status') == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('status') == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="divider"></div>

                <div class="flex justify-between items-center">
                    <a href="/admin/owners" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Create Owner
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

@endsection
