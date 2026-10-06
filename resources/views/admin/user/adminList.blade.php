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
        <div style="display:flex;gap:6px;align-items:center">
            <a href="/admin/admin" class="btn btn-sm {{ $archived ? 'btn-secondary' : 'btn-primary' }}">Active <span class="badge {{ $archived ? 'badge-gray' : '' }}" style="margin-left:6px;{{ $archived ? '' : 'background:rgba(255,255,255,.25);color:#fff' }}">{{ $counts['active'] }}</span></a>
            <a href="/admin/admin?archived=1" class="btn btn-sm {{ $archived ? 'btn-primary' : 'btn-secondary' }}">Inactive <span class="badge {{ $archived ? '' : 'badge-gray' }}" style="margin-left:6px;{{ $archived ? 'background:rgba(255,255,255,.25);color:#fff' : '' }}">{{ $counts['archived'] }}</span></a>
        </div>
    </div>
    <div class="table-wrap">
        @if($users->isEmpty())
            <div class="empty-state">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <p>{{ $archived ? 'No inactive logins.' : 'No admin users found' }}</p>
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
                                $roleClasses = ['super_admin' => 'badge-teal', 'admin' => 'badge-blue', 'operations_manager' => 'badge-blue', 'operations' => 'badge-gray', 'finance_manager' => 'badge-green', 'finance' => 'badge-green', 'sales' => 'badge-gray'];
                                $roleLabels = collect(config('admin_permissions.roles'))->map(fn ($r, $k) => ['label' => $r['label'], 'class' => $roleClasses[$k] ?? 'badge-gray'])->all();
                                $roleKey = $user->admin_role ?: 'super_admin';
                                $roleInfo = $roleLabels[$roleKey] ?? ['label' => ucfirst($roleKey), 'class' => 'badge-gray'];
                            @endphp
                            <span class="badge {{ $roleInfo['class'] }}">{{ $roleInfo['label'] }}</span>
                        </td>
                        <td>
                            @if(admin_can('roles.manage') && $user->id !== Auth::id())
                                <form action="/admin/admin/{{ $user->id }}/toggle" method="POST" style="display:inline" onsubmit="return confirm('{{ $user->status == 1 ? 'Set ' . $user->name . ' to Inactive? The login stops working; record and commission history stay. A tied sales person gets today as leaving date.' : 'Set ' . $user->name . ' to Active again?' }}')">
                                    @csrf
                                    <button type="submit" class="badge {{ $user->status == 1 ? 'badge-green' : 'badge-red' }} badge-toggle" title="Click to switch">{{ $user->status == 1 ? 'Active' : 'Inactive' }}</button>
                                </form>
                                @if($user->status != 1 && $user->archived_at)<div style="font-size:11px;color:var(--text-secondary);margin-top:2px">since {{ \Carbon\Carbon::parse($user->archived_at)->format('j M Y') }}</div>@endif
                            @elseif($user->status == 1)
                                <span class="badge badge-green">Active</span>
                            @else
                                <span class="badge badge-red">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                @if(admin_can('roles.manage'))
                                    <a href="/admin/admin/{{ $user->id }}/edit" class="btn btn-secondary btn-sm">Edit</a>
                                    @if($user->status != 1 && $user->id !== Auth::id())
                                        <form action="/admin/admin/{{ $user->id }}" method="POST" onsubmit="return confirm('Delete {{ $user->name }} permanently? Inactive logins keep their history; deleting removes the record.')">
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

@push('styles')
<style>.badge-toggle{border:0;cursor:pointer;font:inherit;font-size:inherit}.badge-toggle:hover{filter:brightness(.92)}</style>
@endpush
