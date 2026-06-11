@extends('admin.layout')
@section('title', 'Edit Admin: ' . $user->name)
@section('page-title', 'Edit Admin')

@section('content')

<div class="page-header">
    <div>
        <h1>Edit Admin: {{ $user->name }} {{ $user->last_name }}</h1>
        <p>Update account info and permission overrides</p>
    </div>
    <a href="/admin/admin" class="btn btn-secondary">← Back</a>
</div>

<form action="/admin/admin/{{ $user->id }}" method="POST">
@csrf
@method('PUT')

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

    {{-- Left: account info --}}
    <div class="card">
        <div class="card-header"><h2>Account Details</h2></div>
        <div class="card-body">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="name" class="form-input" value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-input" value="{{ old('last_name', $user->last_name) }}" required>
                    @error('last_name')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required>
                @error('email')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Country Code</label>
                    <input type="number" name="country_code" class="form-input" value="{{ old('country_code', $user->country_code) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="number" name="phone" class="form-input" value="{{ old('phone', $user->phone) }}" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-input" placeholder="Leave blank to keep current">
                <div class="form-help">Leave blank to keep the current password.</div>
                @error('password')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Admin Role</label>
                    <select name="admin_role" class="form-select" id="adminRoleSelect" onchange="applyRoleDefaults()">
                        @foreach(config('admin_permissions.roles') as $slug => $role)
                            <option value="{{ $slug }}" {{ old('admin_role', $user->admin_role ?? 'super_admin') === $slug ? 'selected' : '' }}>
                                {{ $role['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="1" {{ old('status', $user->status) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status', $user->status) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="divider"></div>
            <div class="flex justify-between">
                <a href="/admin/admin" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>

    {{-- Right: permission matrix --}}
    <div class="card">
        <div class="card-header">
            <h2>Permissions</h2>
            <span style="font-size:12px;color:#64748b">Checked = granted. Uncheck to deny.</span>
        </div>
        <div class="card-body" style="padding:0">

            @php
                $currentRole = old('admin_role', $user->admin_role ?? 'super_admin');
                $rolePerms   = config("admin_permissions.roles.{$currentRole}.permissions", []);
                $isSuperAdmin = $currentRole === 'super_admin' || in_array('*', $rolePerms);

                // Resolve effective state per permission
                function effectiveGranted(string $key, bool $isSuperAdmin, array $rolePerms, $overrides): bool {
                    if (isset($overrides[$key])) return (bool) $overrides[$key];
                    return $isSuperAdmin || in_array($key, $rolePerms);
                }
            @endphp

            {{-- role defaults as JS data --}}
            <script id="roleDefaults" type="application/json">
            {!! json_encode(collect(config('admin_permissions.roles'))->map(fn($r) => $r['permissions'])) !!}
            </script>

            @foreach($permissionGroups as $group => $permissions)
            <div style="padding:14px 20px;border-bottom:1px solid #f1f5f9">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:10px">
                    {{ $group }}
                </div>
                @foreach($permissions as $key => $label)
                @php $granted = effectiveGranted($key, $isSuperAdmin, $rolePerms, $overrides); @endphp
                <label class="perm-row" data-perm="{{ $key }}"
                    style="display:flex;align-items:center;gap:10px;padding:5px 0;cursor:pointer;font-size:13.5px;color:#374151">
                    <input
                        type="checkbox"
                        name="permissions[{{ $key }}]"
                        value="1"
                        {{ $granted ? 'checked' : '' }}
                        style="width:15px;height:15px;accent-color:#2563eb;cursor:pointer"
                    >
                    <span>{{ $label }}</span>
                    @php
                        $hasOverride = isset($overrides[$key]);
                        $overrideVal = $hasOverride ? (bool)$overrides[$key] : null;
                    @endphp
                    @if($hasOverride)
                        @if($overrideVal)
                            <span style="font-size:11px;color:#16a34a;margin-left:auto">override: granted</span>
                        @else
                            <span style="font-size:11px;color:#dc2626;margin-left:auto">override: denied</span>
                        @endif
                    @endif
                </label>
                @endforeach
            </div>
            @endforeach
        </div>
    </div>

</div>
</form>

@push('scripts')
<script>
var roleDefaults = JSON.parse(document.getElementById('roleDefaults').textContent);

function applyRoleDefaults() {
    var role = document.getElementById('adminRoleSelect').value;
    var perms = roleDefaults[role] || [];
    var isSuperAdmin = role === 'super_admin' || perms.indexOf('*') !== -1;

    document.querySelectorAll('.perm-row input[type=checkbox]').forEach(function(cb) {
        var key = cb.closest('.perm-row').dataset.perm;
        cb.checked = isSuperAdmin || perms.indexOf(key) !== -1;
    });

    // Clear override labels since role changed
    document.querySelectorAll('.perm-row span[style*="margin-left:auto"]').forEach(function(el) {
        el.remove();
    });
}
</script>
@endpush

@endsection
