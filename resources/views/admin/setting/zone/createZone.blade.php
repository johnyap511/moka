@extends('admin.layout')
@section('title', 'Create Zone')
@section('page-title', 'Create Zone')

@section('content')

<div class="page-header">
    <div><h1>Create Zone</h1></div>
    <a href="/admin/setting/zone" class="btn btn-secondary">Back</a>
</div>

<div class="card">
    <div class="card-header"><h2>Zone Details</h2></div>
    <div class="card-body">
        <form action="/admin/setting/zone" method="POST">
            @csrf
            <div class="form-group">
                <label>Zone Name</label>
                <input type="text" name="name" class="form-input" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Create Zone</button>
        </form>
    </div>
</div>

@endsection
