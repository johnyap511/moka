@extends('admin.layout')
@section('title', 'Edit Zone')
@section('page-title', 'Edit Zone')

@section('content')

<div class="page-header">
    <div><h1>Edit Zone</h1></div>
    <a href="/admin/setting/zone" class="btn btn-secondary">Back</a>
</div>

<div class="card">
    <div class="card-header"><h2>Zone Details</h2></div>
    <div class="card-body">
        <form action="/admin/setting/zone/{{ $zone->id }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label>Zone Name</label>
                <input type="text" name="name" class="form-input" value="{{ old('name', $zone->name) }}" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $zone->status == 1 ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ $zone->status == 0 ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update Zone</button>
        </form>
    </div>
</div>

@endsection
