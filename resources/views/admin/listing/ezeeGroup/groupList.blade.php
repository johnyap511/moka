@extends('admin.layout')
@section('title', 'Ezee Groups')
@section('page-title', 'Ezee Groups')

@section('content')

<div class="page-header">
    <div>
        <h1>Ezee Groups</h1>
        <p>Manage EZEE channel manager hotel groups</p>
    </div>
    <a href="/admin/ezee/group/create" class="btn btn-primary">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Create Ezee Group
    </a>
</div>

@if(session('success'))
    <div class="badge badge-green" style="margin-bottom:16px;display:block;padding:10px">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-header">
        <h2>All Ezee Groups</h2>
        <span class="badge badge-blue">{{ $groups->count() }} groups</span>
    </div>
    <div class="table-wrap">
        @if($groups->isEmpty())
            <div class="empty-state">
                <p>No Ezee groups configured yet.</p>
                <a href="/admin/ezee/group/create" class="btn btn-primary">Create First Group</a>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Hotel Code</th>
                        <th>Auth Key</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $group)
                    <tr>
                        <td>{{ $group->id }}</td>
                        <td>{{ $group->name }}</td>
                        <td>{{ $group->hotel_code ?? '—' }}</td>
                        <td>{{ $group->auth_key ? '••••••' : '—' }}</td>
                        <td class="actions">
                            <a href="/admin/ezee/group/{{ $group->id }}/listing" class="btn btn-secondary btn-sm">Listings</a>
                            <form action="/admin/ezee/group/{{ $group->id }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Delete this Ezee group?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
