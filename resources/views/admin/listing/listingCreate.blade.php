@extends('admin.layout')

@section('title', 'New Listing')
@section('page-title', 'New Listing')

@section('content')

{{-- Page Header --}}
<div class="page-header">
    <div>
        <h1>New Listing</h1>
        <p>Add a new property to the system</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/listing" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Listings
        </a>
    </div>
</div>

{{-- Form Card --}}
<div class="card">
    <div class="card-header">
        <h2>Listing Details</h2>
    </div>
    <div class="card-body">
        <form action="/admin/listing" method="POST">
            @csrf

            {{-- Basic Info --}}
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="name">Internal Name <span style="color:var(--red)">*</span></label>
                    <input type="text" id="name" name="name" class="form-input"
                           value="{{ old('name') }}" placeholder="e.g. Villa Sunrise Unit A" required>
                    <div class="form-help">Used internally for identification</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="title">Public Title</label>
                    <input type="text" id="title" name="title" class="form-input"
                           value="{{ old('title') }}" placeholder="e.g. Cozy Beachfront Retreat">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Address</label>
                <input type="text" id="address" name="address" class="form-input"
                       value="{{ old('address') }}" placeholder="Full property address">
            </div>

            {{-- Type & Status --}}
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="type">Listing Type</label>
                    <select id="type" name="type" class="form-select" onchange="toggleGroupField(this.value)">
                        <option value="solo" {{ old('type') === 'solo' ? 'selected' : '' }}>Solo (Individual Unit)</option>
                        <option value="group" {{ old('type') === 'group' ? 'selected' : '' }}>Group (Multiple Units)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            {{-- Group Field --}}
            <div id="groupSection" style="{{ old('type') === 'group' ? '' : 'display:none' }}">
                <div class="form-group">
                    <label class="form-label" for="group_id">Assign to Group</label>
                    <select id="group_id" name="group_id" class="form-select">
                        <option value="">— Select a group —</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>
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
                <div class="text-sm text-secondary">Set the base nightly rate and additional fees</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="default_price">Price per Night (RM)</label>
                    <input type="number" id="default_price" name="default_price" class="form-input"
                           value="{{ old('default_price') }}" placeholder="0.00" min="0" step="0.01">
                </div>
                <div class="form-group">
                    <label class="form-label" for="cleaning_fee">Cleaning Fee (RM)</label>
                    <input type="number" id="cleaning_fee" name="cleaning_fee" class="form-input"
                           value="{{ old('cleaning_fee') }}" placeholder="0.00" min="0" step="0.01">
                </div>
            </div>

            <div class="divider"></div>

            {{-- Actions --}}
            <div class="flex gap-2" style="justify-content:flex-end">
                <a href="/admin/listing" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Create Listing
                </button>
            </div>

        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleGroupField(type) {
    const section = document.getElementById('groupSection');
    if (type === 'group') {
        section.style.display = '';
    } else {
        section.style.display = 'none';
        document.getElementById('group_id').value = '';
    }
}
// Run on page load to restore state on validation error
document.addEventListener('DOMContentLoaded', function () {
    toggleGroupField(document.getElementById('type').value);
});
</script>
@endpush
