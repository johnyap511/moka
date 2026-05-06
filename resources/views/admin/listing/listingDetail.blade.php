@extends('admin.layout')
@section('title', 'Listing Details')
@section('page-title', 'Listing Details')

@section('content')

<div class="page-header">
    <div>
        <h1>Listing Details</h1>
        <p>{{ $listing->title ?? $listing->name ?? 'Listing #'.$listing->id }}</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/listing/{{ $listing->id }}/edit" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Edit
        </a>
    </div>
</div>

@if(session('success'))
    <div class="badge badge-green" style="margin-bottom:16px;display:block;padding:10px">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-header">
        <h2>Host & Stay Details</h2>
    </div>
    <div class="card-body">
        <form action="/admin/listing/{{ $listing->id }}/details" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label>Host Name</label>
                    <input type="text" name="host_name" class="form-input" value="{{ old('host_name', $listingDetails->host_name ?? '') }}">
                </div>
                <div class="form-group">
                    <label>Host Contact</label>
                    <input type="text" name="host_contact" class="form-input" value="{{ old('host_contact', $listingDetails->host_contact ?? '') }}">
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-textarea" rows="4">{{ old('description', $listingDetails->description ?? '') }}</textarea>
            </div>

            <div class="form-group">
                <label>Distances (nearby attractions, etc.)</label>
                <textarea name="distances" class="form-textarea" rows="4">{{ old('distances', $listingDetails->distances ?? '') }}</textarea>
            </div>

            <div class="form-group">
                <label>During Stay Instructions</label>
                <textarea name="during_stay" class="form-textarea" rows="4">{{ old('during_stay', $listingDetails->during_stay ?? '') }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Save Details</button>
        </form>
    </div>
</div>

@endsection
