@extends('admin.layout')
@section('title', 'Create Amenity')
@section('page-title', 'Create Amenity')

@section('content')

<div class="page-header">
    <div><h1>Create Amenity</h1></div>
    <a href="/admin/setting/amenities" class="btn btn-secondary">Back</a>
</div>

@if($errors->any())
    <div class="badge badge-red" style="margin-bottom:16px;display:block;padding:10px">
        <ul style="margin:0;padding-left:16px">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-header"><h2>Amenity Details</h2></div>
    <div class="card-body">
        <form action="/admin/setting/amenities" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-input" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label>Order</label>
                <input type="number" name="order" class="form-input" value="{{ old('order', 0) }}" required>
            </div>
            <div class="form-group">
                <label>Image</label>
                <input type="file" name="image" class="form-input" accept="image/*" required>
            </div>
            <div class="form-group">
                <label>Chinese Name</label>
                <input type="text" name="chinese" class="form-input" value="{{ old('chinese') }}">
            </div>
            <div class="form-group">
                <label>Malay Name</label>
                <input type="text" name="malay" class="form-input" value="{{ old('malay') }}">
            </div>
            <button type="submit" class="btn btn-primary">Create Amenity</button>
        </form>
    </div>
</div>

@endsection
