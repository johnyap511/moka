@extends('admin.layout')
@section('title', 'Admin Users')
@section('page-title', 'Admin Users')

@section('content')

<div class="page-header">
    <div>
        <h1>Admin Users</h1>
        <p>Manage admin accounts and their roles</p>
    </div>
    @if(admin_can('roles.manage'))
    <a href="/admin/admin/create" class="btn btn-primary">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Create Admin
    </a>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px;padding:12px 16px;background:var(--green-light,#ecfdf5);color:var(--green,#16a34a);border-radius:8px;font-size:14px">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:16px;padding:12px 16px;background:#fef2f2;color:#dc2626;border-radius:8px;font-size:14px">
        {{ session('error') }}
    </div>
@endif

<div class="card">
    <div class="card-header">
        <h2>
            All Admins
            <span class="badge badge-gray" style="margin-left:8px">{{ $users->count() }}</span>
        </h2>
    </div>
    <div class="table-wrap">
        @if($users->isEmpty())
            <div class="empty-state">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <p>No admin users found</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Admin Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>
                            <div class="flex items-center gap-2">
                                <div style="width:30px;height:30px;border-radius:50%;background:var(--teal-light);color:var(--teal);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;flex-shrink:0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <span class="font-600">{{ $user->name }} {{ $user->last_name }}</span>
                            </div>
                        </td>
                        <td class="text-secondary">{{ $user->email }}</td>
                        <td>
                            @php
                                $roleLabels = [
                                    'super_admin' => ['label' => 'Super Admin', 'class' => 'badge-teal'],
                                    'manager'     => ['label' => 'Manager',     'class' => 'badge-blue'],
                                    'finance'     => ['label' => 'Finance',     'class' => 'badge-green'],
                                    'operations'  => ['label' => 'Operations',  'class' => 'badge-gray'],
                                ];
                                $roleKey = $user->admin_role ?: 'super_admin';
                                $roleInfo = $roleLabels[$roleKey] ?? ['label' => ucfirst($roleKey), 'class' => 'badge-gray'];
                            @endphp
                            <span class="badge {{ $roleInfo['class'] }}">{{ $roleInfo['label'] }}</span>
                        </td>
                        <td>
                            @if($user->status == 1)
                                <span class="badge badge-green">Active</span>
                            @else
                                <span class="badge badge-red">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                @if(admin_can('roles.manage'))
                                    <a href="/admin/admin/{{ $user->id }}/edit" class="btn btn-secondary btn-sm">Edit</a>
                                    @if($user->id !== Auth::id())
                                        <form action="/admin/admin/{{ $user->id }}" method="POST" onsubmit="return confirm('Delete this admin user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
