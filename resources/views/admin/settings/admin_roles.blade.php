@extends('admin.layout')
@section('title', 'Admin Roles')
@section('page-title', 'Admin Roles')

@section('content')

<div class="page-header">
    <div>
        <h1>Admin Roles</h1>
        <p>Reference for what each role can access. Assign roles on the Admin Users page.</p>
    </div>
    <a href="/admin/admin" class="btn btn-secondary">Manage Admins →</a>
</div>

@php
    $groups = config('admin_permissions.groups', []);
    $roles  = config('admin_permissions.roles', []);
    $allPerms = [];
    foreach ($groups as $g => $perms) {
        foreach ($perms as $key => $label) {
            $allPerms[$key] = ['group' => $g, 'label' => $label];
        }
    }
@endphp

{{-- Role cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-bottom:28px">
    @foreach($roles as $slug => $role)
    @php
        $rolePerms = $role['permissions'];
        $isSuperAdmin = in_array('*', $rolePerms);
        $count = $isSuperAdmin ? count($allPerms) : count($rolePerms);
    @endphp
    <div class="card" style="margin:0">
        <div class="card-body" style="padding:18px 20px">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span class="badge {{ $slug === 'super_admin' ? 'badge-teal' : ($slug === 'manager' ? 'badge-blue' : ($slug === 'finance' ? 'badge-green' : 'badge-gray')) }}"
                    style="font-size:12px;padding:3px 10px">
                    {{ $role['label'] }}
                </span>
            </div>
            <div style="font-size:13px;color:#64748b;margin-bottom:12px">{{ $role['description'] }}</div>
            <div style="font-size:22px;font-weight:700;color:#1e293b">{{ $count }}</div>
            <div style="font-size:11.5px;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em">permissions granted</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Permission matrix --}}
<div class="card">
    <div class="card-header"><h2>Permission Matrix</h2></div>
    <div style="overflow-x:auto">
        <table style="min-width:700px">
            <thead>
                <tr>
                    <th style="width:200px">Permission</th>
                    @foreach($roles as $slug => $role)
                    <th style="text-align:center">{{ $role['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($groups as $group => $perms)
                <tr>
                    <td colspan="{{ count($roles) + 1 }}"
                        style="background:#f8fafc;padding:8px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8">
                        {{ $group }}
                    </td>
                </tr>
                @foreach($perms as $key => $label)
                <tr>
                    <td style="font-size:13px;color:#374151">{{ $label }}</td>
                    @foreach($roles as $slug => $role)
                    @php
                        $rp = $role['permissions'];
                        $has = in_array('*', $rp) || in_array($key, $rp);
                    @endphp
                    <td style="text-align:center">
                        @if($has)
                            <span style="color:#16a34a;font-size:16px">✓</span>
                        @else
                            <span style="color:#e2e8f0;font-size:16px">—</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
