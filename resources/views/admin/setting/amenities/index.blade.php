@extends('admin.layout')
@section('title', 'Amenities')
@section('page-title', 'Amenities')

@section('content')

<div class="page-header">
    <div>
        <h1>Amenities</h1>
        <p>Manage property amenity options</p>
    </div>
    <a href="/admin/setting/amenities/create" class="btn btn-primary">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Amenity
    </a>
</div>

@if(session('success'))
    <div class="badge badge-green" style="margin-bottom:16px;display:block;padding:10px">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-header">
        <h2>All Amenities</h2>
        <span class="badge badge-blue">{{ $amenities->count() }} items</span>
    </div>
    <div class="table-wrap">
        @if($amenities->isEmpty())
            <div class="empty-state">
                <p>No amenities defined yet.</p>
                <a href="/admin/setting/amenities/create" class="btn btn-primary">Add First Amenity</a>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($amenities as $amenity)
                    <tr>
                        <td>{{ $amenity->id }}</td>
                        <td>
                            @if($amenity->image)
                                <img src="{{ asset('storage/'.$amenity->image) }}" alt="" style="width:28px;height:28px;object-fit:contain;vertical-align:middle;margin-right:8px">
                            @endif
                            {{ $amenity->name }}
                        </td>
                        <td>{{ $amenity->order ?? '—' }}</td>
                        <td class="actions">
                            <a href="/admin/setting/amenities/{{ $amenity->id }}/edit" class="btn btn-secondary btn-sm">Edit</a>
                            <form action="/admin/setting/amenities/{{ $amenity->id }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Delete this amenity?')">Delete</button>
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
