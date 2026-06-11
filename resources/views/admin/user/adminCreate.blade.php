@extends('admin.layout')
@section('title', 'Create Admin')
@section('page-title', 'Create Admin')

@section('content')

<div class="page-header">
    <div>
        <h1>Create Admin</h1>
        <p>Add a new admin user and assign their role</p>
    </div>
    <a href="/admin/admin" class="btn btn-secondary">← Back</a>
</div>

<div class="card" style="max-width:640px">
    <div class="card-header"><h2>Account Details</h2></div>
    <div class="card-body">
        <form action="/admin/admin" method="POST">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="name" class="form-input" value="{{ old('name') }}" required>
                    @error('name')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-input" value="{{ old('last_name') }}" required>
                    @error('last_name')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" value="{{ old('email') }}" required>
                @error('email')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Country Code</label>
                    <input type="number" name="country_code" class="form-input" value="{{ old('country_code', '60') }}" required>
                    @error('country_code')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="number" name="phone" class="form-input" value="{{ old('phone') }}" required>
                    @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" required minlength="6">
                @error('password')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Admin Role</label>
                    <select name="admin_role" class="form-select">
                        @foreach(config('admin_permissions.roles') as $slug => $role)
                            <option value="{{ $slug }}" {{ old('admin_role') === $slug ? 'selected' : '' }}>
                                {{ $role['label'] }} — {{ $role['description'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('admin_role')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status') == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="divider"></div>
            <div class="flex justify-between">
                <a href="/admin/admin" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Admin</button>
            </div>
        </form>
    </div>
</div>

@endsection
