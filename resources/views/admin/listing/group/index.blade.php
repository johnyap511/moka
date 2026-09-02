@extends('admin.layout')
@section('title', 'Groups')
@section('page-title', 'Groups')

@section('content')

<div class="page-header">
    <div>
        <h1>Groups</h1>
        <p>Manage listing groups and packages</p>
    </div>
    <a href="/admin/group/create" class="btn btn-primary">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Create Group
    </a>
</div>

@if(session('success'))
    <div class="badge badge-green" style="margin-bottom:16px;display:block;padding:10px">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-header">
        <h2>All Groups</h2>
        <span class="badge badge-blue">{{ $groups->count() }} groups</span>
    </div>
    <div class="table-wrap">
        @if($groups->isEmpty())
            <div class="empty-state">
                <p>No groups created yet.</p>
                <a href="/admin/group/create" class="btn btn-primary">Create First Group</a>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Formula</th>
                        <th style="text-align:center">Units</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $group)
                    <tr>
                        <td>{{ $group->id }}</td>
                        <td>{{ $group->name }}</td>
                        <td>{{ $group->description ?? '—' }}</td>
                        <td>{{ $group->formula ?? '—' }}</td>
                        <td style="text-align:center">
                            @php $unitCount = $counts[$group->id] ?? 0; @endphp
                            @if($unitCount)
                                <a href="/admin/group/{{ $group->id }}" style="font-weight:600;color:var(--teal)">{{ $unitCount }}</a>
                            @else
                                <span style="color:var(--text-secondary)">0</span>
                            @endif
                        </td>
                        <td class="actions">
                            <a href="/admin/group/{{ $group->id }}" class="btn btn-secondary btn-sm">View Units</a>
                            <a href="/admin/group/{{ $group->id }}/edit" class="btn btn-secondary btn-sm">Edit</a>
                            <form action="/admin/group/{{ $group->id }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Delete this group?')">Delete</button>
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
