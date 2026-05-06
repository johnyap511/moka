@extends('admin.layout')
@section('title', 'Property Owners')
@section('page-title', 'Property Owners')

@section('content')

<div class="page-header">
    <div>
        <h1>Property Owners</h1>
        <p>Manage property owner accounts and their listings</p>
    </div>
    <a href="/admin/owners/create" class="btn btn-primary">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Owner
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h2>
            All Owners
            <span class="badge badge-gray" style="margin-left:8px">{{ $users->count() }}</span>
        </h2>
    </div>
    <div class="table-wrap">
        @if($users->isEmpty())
            <div class="empty-state">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <p>No owners yet</p>
                <small>Add your first property owner to get started.</small>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Listings</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $owner)
                    @php
                        $listingCount = App\Models\Listing::where('user_id', $owner->id)->count();
                    @endphp
                    <tr>
                        <td class="mono">#{{ $owner->id }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div style="width:30px;height:30px;border-radius:50%;background:var(--teal-light);color:var(--teal);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;flex-shrink:0">
                                    {{ strtoupper(substr($owner->name, 0, 1)) }}
                                </div>
                                <span class="font-600">{{ $owner->name }}</span>
                            </div>
                        </td>
                        <td class="text-secondary">{{ $owner->email }}</td>
                        <td>{{ $owner->phone ?? '—' }}</td>
                        <td>
                            <a href="/admin/owners/{{ $owner->id }}/listing" class="badge badge-teal" style="text-decoration:none">
                                {{ $listingCount }} {{ Str::plural('listing', $listingCount) }}
                            </a>
                        </td>
                        <td>
                            @if($owner->status == 1)
                                <span class="badge badge-green">Active</span>
                            @else
                                <span class="badge badge-red">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <a href="/admin/owners/{{ $owner->id }}/edit" class="btn btn-secondary btn-sm">Edit</a>
                                <a href="/admin/owners/{{ $owner->id }}/listing" class="btn btn-secondary btn-sm">Listings</a>
                                <form action="/admin/owners/{{ $owner->id }}" method="POST" onsubmit="return confirm('Delete this owner? This cannot be undone.')">
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

@endsection
