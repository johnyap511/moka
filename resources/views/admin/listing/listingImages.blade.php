@extends('admin.layout')
@section('title', 'Listing Images')
@section('page-title', 'Listing Images')

@section('content')

<div class="page-header">
    <div>
        <h1>Listing Images</h1>
        <p>Manage photos for this property</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/listing/{{ $id }}/edit" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Edit
        </a>
    </div>
</div>

@if(session('success'))
    <div class="badge badge-green" style="margin-bottom:16px;display:block;padding:10px">{{ session('success') }}</div>
@endif

{{-- Upload Form --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h2>Upload Images</h2>
    </div>
    <div class="card-body">
        <form action="/admin/listing/{{ $id }}/images" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Select Images</label>
                <input type="file" name="images[]" class="form-input" multiple accept="image/*">
                <small style="color:var(--text-secondary)">You can select multiple images at once.</small>
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>
    </div>
</div>

{{-- Image Gallery --}}
<div class="card">
    <div class="card-header">
        <h2>Gallery</h2>
        <span class="badge badge-blue">{{ $images->count() }} images</span>
    </div>
    <div class="card-body">
        @if($images->isEmpty())
            <div class="empty-state">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p>No images uploaded yet</p>
                <small>Upload photos to showcase this property.</small>
            </div>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px">
                @foreach($images as $image)
                <div style="position:relative;border:1px solid var(--border);border-radius:8px;overflow:hidden">
                    <img src="{{ asset('storage/' . $image->image) }}" alt="Listing image"
                         style="width:100%;height:120px;object-fit:cover;display:block"
                         onerror="this.src='/img/placeholder.jpg'">
                    <div style="padding:8px;text-align:center">
                        <form action="/admin/listing/{{ $id }}/images/{{ $image->id }}" method="POST" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this image?')">Delete</button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@endsection
