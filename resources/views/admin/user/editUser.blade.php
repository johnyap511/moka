@extends('admin.layout')
@section('title', 'Edit Guest')
@section('page-title', 'Edit Guest')

@section('content')

<div class="page-header">
    <div>
        <h1>Edit Guest</h1>
        <p>Update guest account details</p>
    </div>
    <a href="/admin/users/{{ $user->id }}" class="btn btn-secondary">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back
    </a>
</div>

<div style="max-width:640px">
    <div class="card">
        <div class="card-header">
            <h2>Account Information</h2>
        </div>
        <div class="card-body">
            <form action="/admin/users/{{ $user->id }}" method="POST">
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

                <div class="form-row">
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
                    <div class="form-group">
                        <label class="form-label" for="status">Account Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="1" {{ old('status', $user->status) == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('status', $user->status) == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="divider"></div>

                <div class="flex justify-between items-center">
                    <a href="/admin/users/{{ $user->id }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

@endsection
