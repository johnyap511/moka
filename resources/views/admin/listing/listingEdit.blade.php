@extends('admin.layout')

@section('title', 'Edit Listing')
@section('page-title', 'Edit Listing')

@section('content')

@php
    $details = $listing->details ?? null;
@endphp

{{-- Page Header --}}
<div class="page-header">
    <div>
        <h1>Edit Listing</h1>
        <p>{{ $listing->title ?? $listing->name ?? 'Listing #'.$listing->id }}</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/listing" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back
        </a>
        <a href="/listing/{{ $listing->key ?? $listing->id }}" target="_blank" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            View Listing
        </a>
    </div>
</div>

{{-- Main Form Card --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h2>Listing Details</h2>
        <div class="flex gap-2">
            @if($listing->status == 1)
                <span class="badge badge-green">Active</span>
            @else
                <span class="badge badge-red">Inactive</span>
            @endif
            <span class="badge badge-teal">{{ ucfirst($listing->type ?? 'solo') }}</span>
        </div>
    </div>
    <div class="card-body">
        <form action="/admin/listing/{{ $listing->id }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Basic Info --}}
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="name">Internal Name <span style="color:var(--red)">*</span></label>
                    <input type="text" id="name" name="name" class="form-input"
                           value="{{ old('name', $listing->name) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="title">Public Title</label>
                    <input type="text" id="title" name="title" class="form-input"
                           value="{{ old('title', $listing->title) }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Address</label>
                <input type="text" id="address" name="address" class="form-input"
                       value="{{ old('address', $listing->address) }}">
            </div>

            {{-- Type & Status --}}
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="type">Listing Type</label>
                    <select id="type" name="type" class="form-select" onchange="toggleGroupField(this.value)">
                        <option value="solo" {{ old('type', $listing->type) === 'solo' ? 'selected' : '' }}>Solo (Individual Unit)</option>
                        <option value="group" {{ old('type', $listing->type) === 'group' ? 'selected' : '' }}>Group (Multiple Units)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="1" {{ old('status', $listing->status) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status', $listing->status) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            {{-- Group Field --}}
            <div id="groupSection" style="{{ old('type', $listing->type) === 'group' ? '' : 'display:none' }}">
                <div class="form-group">
                    <label class="form-label" for="group_id">Assign to Group</label>
                    <select id="group_id" name="group_id" class="form-select">
                        <option value="">— Select a group —</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}"
                                {{ old('group_id', $listing->group_id ?? '') == $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="divider"></div>

            {{-- Pricing --}}
            <div style="margin-bottom:12px">
                <div class="font-600" style="font-size:14px;margin-bottom:4px">Pricing</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="default_price">Price per Night (RM)</label>
                    <input type="number" id="default_price" name="default_price" class="form-input"
                           value="{{ old('default_price', $listing->default_price) }}" min="0" step="0.01">
                </div>
                <div class="form-group">
                    <label class="form-label" for="cleaning_fee">Cleaning Fee (RM)</label>
                    <input type="number" id="cleaning_fee" name="cleaning_fee" class="form-input"
                           value="{{ old('cleaning_fee', $listing->cleaning_fee ?? '') }}" min="0" step="0.01">
                </div>
            </div>

            <div class="divider"></div>

            {{-- Actions --}}
            <div class="flex gap-2" style="justify-content:flex-end">
                <a href="/admin/listing" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save Changes
                </button>
            </div>

        </form>
    </div>
</div>

{{-- Listing Details / Host Info Card --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h2>Host & Description</h2>
        <span class="text-sm text-secondary">Additional listing details visible to guests</span>
    </div>
    <div class="card-body">
        <form action="/admin/listing/{{ $listing->id }}/details" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="host_name">Host Name</label>
                    <input type="text" id="host_name" name="host_name" class="form-input"
                           value="{{ old('host_name', $details->host_name ?? '') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="host_contact">Host Contact</label>
                    <input type="text" id="host_contact" name="host_contact" class="form-input"
                           value="{{ old('host_contact', $details->host_contact ?? '') }}" placeholder="Phone or email">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-textarea" rows="4"
                          placeholder="Describe the property for guests…">{{ old('description', $details->description ?? '') }}</textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="distances">Nearby Distances</label>
                    <textarea id="distances" name="distances" class="form-textarea" rows="3"
                              placeholder="e.g. 5 min to beach, 10 min to town…">{{ old('distances', $details->distances ?? '') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="during_stay">During Your Stay</label>
                    <textarea id="during_stay" name="during_stay" class="form-textarea" rows="3"
                              placeholder="House rules, check-in info…">{{ old('during_stay', $details->during_stay ?? '') }}</textarea>
                </div>
            </div>

            <div class="flex gap-2" style="justify-content:flex-end">
                <button type="submit" class="btn btn-primary">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save Details
                </button>
            </div>

        </form>
    </div>
</div>

{{-- Quick Links Card --}}
<div class="card">
    <div class="card-header">
        <h2>Quick Links</h2>
    </div>
    <div class="card-body">
        <div class="flex gap-2">
            <a href="/admin/listing/{{ $listing->id }}/images" class="btn btn-secondary">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Manage Images
            </a>
            <a href="/admin/listing/{{ $listing->id }}/price" class="btn btn-secondary">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Pricing Calendar
            </a>
            <a href="/admin/listing/{{ $listing->id }}/book" class="btn btn-secondary">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                View Bookings
            </a>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleGroupField(type) {
    const section = document.getElementById('groupSection');
    section.style.display = (type === 'group') ? '' : 'none';
    if (type !== 'group') document.getElementById('group_id').value = '';
}
</script>
@endpush
