@extends('admin.layout')
@section('title', 'Create Group')
@section('page-title', 'Create Group')

@section('content')

<div class="page-header">
    <div><h1>Create Group</h1></div>
    <a href="/admin/group" class="btn btn-secondary">Back</a>
</div>

<div class="card">
    <div class="card-header"><h2>Group Details</h2></div>
    <div class="card-body">
        <form action="/admin/group" method="POST">
            @csrf
            <div class="form-group">
                <label>Group Name</label>
                <input type="text" name="name" class="form-input" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-textarea" rows="3">{{ old('description') }}</textarea>
            </div>
            <div class="form-group">
                <label>Formula</label>
                <input type="text" name="formula" class="form-input" value="{{ old('formula') }}" placeholder="e.g. price * 0.9">
            </div>
            <button type="submit" class="btn btn-primary">Create Group</button>
        </form>
    </div>
</div>

@endsection
