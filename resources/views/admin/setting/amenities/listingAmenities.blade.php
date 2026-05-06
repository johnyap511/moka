@extends('admin.layout')
@section('title', 'Listing Amenities')
@section('page-title', 'Listing Amenities')

@section('content')

<div class="page-header">
    <div>
        <h1>Amenities for: {{ $listing->title ?? 'Listing #'.$listing->id }}</h1>
    </div>
    <a href="/admin/listing" class="btn btn-secondary">Back to Listings</a>
</div>

@if(session('success'))
    <div class="badge badge-green" style="margin-bottom:16px;display:block;padding:10px">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="badge badge-red" style="margin-bottom:16px;display:block;padding:10px">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-header"><h2>Select Amenities</h2></div>
    <div class="card-body">
        <form action="/admin/listing/{{ $listing->id }}/amenities" method="POST">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:20px">
                @foreach($amenities as $amenity)
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}"
                            {{ $listing->amenities && $listing->amenities->contains('amenities_id', $amenity->id) ? 'checked' : '' }}>
                        @if($amenity->image)
                            <img src="{{ asset('storage/'.$amenity->image) }}" alt="" style="width:20px;height:20px;object-fit:contain">
                        @endif
                        {{ $amenity->name }}
                    </label>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary">Save Amenities</button>
        </form>
    </div>
</div>

@endsection
