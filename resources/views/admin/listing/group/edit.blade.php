@extends('admin.layout')
@section('title', 'Edit Group')
@section('page-title', 'Edit Group')

@section('content')

<div class="page-header">
    <div><h1>Edit Group</h1></div>
    <a href="/admin/group" class="btn btn-secondary">Back</a>
</div>

<div class="card">
    <div class="card-header"><h2>Group Details</h2></div>
    <div class="card-body">
        <form action="/admin/group/{{ $group->id }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label>Group Name</label>
                <input type="text" name="name" class="form-input" value="{{ old('name', $group->name) }}" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-textarea" rows="3">{{ old('description', $group->description) }}</textarea>
            </div>
            <div class="form-group">
                <label>Formula</label>
                <input type="text" name="formula" class="form-input" value="{{ old('formula', $group->formula) }}">
            </div>
            <button type="submit" class="btn btn-primary">Update Group</button>
        </form>
    </div>
</div>

@endsection
