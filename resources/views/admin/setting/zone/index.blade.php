@extends('admin.layout')
@section('title', 'Zones')
@section('page-title', 'Zones')

@section('content')

<div class="page-header">
    <div>
        <h1>Zones</h1>
        <p>Manage property location zones</p>
    </div>
    <a href="/admin/setting/zone/create" class="btn btn-primary">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Zone
    </a>
</div>

@if(session('success'))
    <div class="badge badge-green" style="margin-bottom:16px;display:block;padding:10px">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-header">
        <h2>All Zones</h2>
        <span class="badge badge-blue">{{ $zones->count() }} zones</span>
    </div>
    <div class="table-wrap">
        @if($zones->isEmpty())
            <div class="empty-state">
                <p>No zones defined yet.</p>
                <a href="/admin/setting/zone/create" class="btn btn-primary">Add First Zone</a>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($zones as $zone)
                    <tr>
                        <td>{{ $zone->id }}</td>
                        <td>{{ $zone->name }}</td>
                        <td>
                            @if($zone->status == 1)
                                <span class="badge badge-green">Active</span>
                            @else
                                <span class="badge badge-red">Inactive</span>
                            @endif
                        </td>
                        <td class="actions">
                            <a href="/admin/setting/zone/{{ $zone->id }}/edit" class="btn btn-secondary btn-sm">Edit</a>
                            <form action="/admin/setting/zone/{{ $zone->id }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Delete this zone?')">Delete</button>
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
