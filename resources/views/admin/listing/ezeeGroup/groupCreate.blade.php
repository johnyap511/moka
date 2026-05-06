@extends('admin.layout')
@section('title', 'Create Ezee Group')
@section('page-title', 'Create Ezee Group')

@section('content')

<div class="page-header">
    <div><h1>Create Ezee Group</h1></div>
    <a href="/admin/ezee/group" class="btn btn-secondary">Back</a>
</div>

<div class="card">
    <div class="card-header"><h2>Ezee Group Details</h2></div>
    <div class="card-body">
        <form action="/admin/ezee/group" method="POST">
            @csrf
            <div class="form-group">
                <label>Group Name</label>
                <input type="text" name="name" class="form-input" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label>Hotel Code</label>
                <input type="text" name="hotel_code" class="form-input" value="{{ old('hotel_code') }}">
            </div>
            <div class="form-group">
                <label>Auth Key</label>
                <input type="text" name="auth_key" class="form-input" value="{{ old('auth_key') }}">
            </div>
            <button type="submit" class="btn btn-primary">Create Ezee Group</button>
        </form>
    </div>
</div>

@endsection
