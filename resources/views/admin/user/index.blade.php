@extends('admin.layout')
@section('title', 'Guests')
@section('page-title', 'Guests')

@section('content')

<div class="page-header">
    <div>
        <h1>Guests</h1>
        <p>Manage registered guest accounts</p>
    </div>
    <div class="flex gap-2">
        <form action="/admin/user/export/csv" method="POST" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-secondary">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </button>
        </form>
        <form action="/admin/user/export/excel" method="POST" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-primary">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Excel
            </button>
        </form>
    </div>
</div>

{{-- Filter Tabs --}}
<div class="flex gap-2 mb-4" style="border-bottom:1px solid var(--border);padding-bottom:0;margin-bottom:20px">
    @php
        $tabs = ['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'];
        $current = $type ?? 'all';
    @endphp
    @foreach($tabs as $key => $label)
        <a href="/admin/users?type={{ $key }}"
           style="padding:10px 16px;font-size:13.5px;font-weight:500;border-bottom:2px solid {{ $current === $key ? 'var(--teal)' : 'transparent' }};color:{{ $current === $key ? 'var(--teal)' : 'var(--text-secondary)' }};transition:all .15s;margin-bottom:-1px">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <h2>
            {{ $current === 'all' ? 'All Guests' : ucfirst($current) . ' Guests' }}
            <span class="badge badge-gray" style="margin-left:8px">{{ $users->count() }}</span>
        </h2>
    </div>
    <div class="table-wrap">
        @if($users->isEmpty())
            <div class="empty-state">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <p>No guests found</p>
                <small>{{ $current !== 'all' ? 'Try changing the filter above.' : 'No guests have registered yet.' }}</small>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td class="mono">#{{ $user->id }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div style="width:30px;height:30px;border-radius:50%;background:var(--teal-light);color:var(--teal);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;flex-shrink:0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <span class="font-600">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="text-secondary">{{ $user->email }}</td>
                        <td>{{ $user->phone ?? '—' }}</td>
                        <td>
                            @if($user->status == 1)
                                <span class="badge badge-green">Active</span>
                            @else
                                <span class="badge badge-red">Inactive</span>
                            @endif
                        </td>
                        <td class="text-secondary text-sm">{{ $user->created_at ? $user->created_at->format('d M Y') : '—' }}</td>
                        <td>
                            <div class="actions">
                                <a href="/admin/users/{{ $user->id }}" class="btn btn-secondary btn-sm">View</a>
                                <a href="/admin/users/{{ $user->id }}/edit" class="btn btn-secondary btn-sm">Edit</a>
                                <form action="/admin/users/{{ $user->id }}" method="POST" onsubmit="return confirm('Delete this guest?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@if(method_exists($users, 'links'))
<div class="pagination" style="margin-top:20px">
    {{ $users->appends(request()->query())->links() }}
</div>
@endif

@endsection
