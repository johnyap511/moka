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
        <form action="/admin/listing/{{ $listing->id }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Keeps the owner-context redirect in update() working. --}}
            @isset($user)
                <input type="hidden" name="user_listing" value="1">
            @endisset

            {{-- Rental Category. The id matters: PaymentController enforces a
                 one-month minimum on 2 and six months on 3. --}}
            <div class="form-group">
                <label class="form-label">Rental Category</label>
                @foreach(\App\Support\ListingOptions::CATEGORIES as $cid => $label)
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:6px;cursor:pointer">
                        <input type="radio" name="category" value="{{ $cid }}"
                               {{ (int) old('category', $categories[0] ?? 1) === $cid ? 'checked' : '' }}>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <div class="form-group">
                <label class="form-label" for="user_id">Owner</label>
                <select id="user_id" name="user_id" class="form-select">
                    <option value="">— No owner —</option>
                    @foreach($owners as $o)
                        <option value="{{ $o->id }}"
                            {{ (string) old('user_id', $listing->user_id) === (string) $o->id ? 'selected' : '' }}>
                            {{ $o->email ?: trim($o->name . ' ' . $o->last_name) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="title">Title <span style="color:var(--red)">*</span></label>
                    <input type="text" id="title" name="title" class="form-input"
                           value="{{ old('title', $listing->title) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="name">Name <span style="color:var(--red)">*</span></label>
                    <input type="text" id="name" name="name" class="form-input"
                           value="{{ old('name', $listing->name) }}" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Address <span style="color:var(--red)">*</span></label>
                <input type="text" id="address" name="address" class="form-input"
                       value="{{ old('address', $listing->address) }}" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="agent_code">Agent Code <span style="color:var(--red)">*</span></label>
                    <input type="text" id="agent_code" name="agent_code" class="form-input"
                           value="{{ old('agent_code', $listing->agent_code) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="room_type">Room Type</label>
                    <input type="text" id="room_type" name="room_type" class="form-input"
                           value="{{ old('room_type', $listing->room_type) }}">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="ezee_room_id">EZEE Room ID</label>
                    <input type="text" id="ezee_room_id" name="ezee_room_id" class="form-input"
                           placeholder="e.g. C2-07-10"
                           value="{{ old('ezee_room_id', $listing->ezee_room_id) }}">
                    <div class="form-help">EZEE's unit id for this listing. Bookings carrying this id assign here automatically.</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="default_price">Default Price <span style="color:var(--red)">*</span></label>
                    <input type="number" id="default_price" name="default_price" class="form-input"
                           value="{{ old('default_price', $listing->default_price) }}" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="cleaning_fee">Default Cleaning Fee</label>
                    <input type="number" id="cleaning_fee" name="cleaning_fee" class="form-input"
                           value="{{ old('cleaning_fee', $listing->cleaning_fee) }}" min="0" step="0.01">
                </div>
            </div>

            <div class="divider"></div>

            {{-- Tourism Tax. 'percentage' is checked by name in
                 PaymentController and CheckoutController. --}}
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="tourism_tax_type">Tourism Tax <span style="color:var(--red)">*</span></label>
                    <select id="tourism_tax_type" name="tourism_tax_type" class="form-select" required>
                        @foreach(\App\Support\ListingOptions::TOURISM_TAX_TYPES as $v => $label)
                            <option value="{{ $v }}" {{ old('tourism_tax_type', $listing->tourism_tax_type) === $v ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="tourism_tax_amount">Amount <span style="color:var(--red)">*</span></label>
                    <input type="number" id="tourism_tax_amount" name="tourism_tax_amount" class="form-input"
                           value="{{ old('tourism_tax_amount', $listing->tourism_tax_amount ?? '0.00') }}" min="0" step="0.01" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="type">Listing Type</label>
                    <select id="type" name="type" class="form-select" onchange="toggleGroupField(this.value)">
                        <option value="solo"  {{ old('type', $listing->type) === 'solo'  ? 'selected' : '' }}>Individual Listing</option>
                        <option value="group" {{ old('type', $listing->type) === 'group' ? 'selected' : '' }}>Group Listing</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="profit">Profit Sharing <span style="color:var(--text-secondary);font-weight:400">(owner:Moka)</span></label>
                    <select id="profit" name="profit" class="form-select">
                        <option value="">Select Profit Sharing</option>
                        @foreach(\App\Support\ListingOptions::PROFIT_SHARING as $v => $label)
                            <option value="{{ $v }}" {{ (string) old('profit', round($listing->profit)) === (string) $v ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="pool_weight">Pool weight <span style="color:var(--text-secondary);font-weight:400">(pooled units only: by size, e.g. 900 for a 3BR, 600 for a studio; 1 = equal share)</span></label>
                    <input type="number" step="0.01" min="0" id="pool_weight" name="pool_weight" class="form-input" value="{{ old('pool_weight', $listing->pool_weight ?? 1) }}">
                </div>
            </div>

            <div id="groupSection" style="{{ old('type', $listing->type) === 'group' ? '' : 'display:none' }}">
                <div class="form-group">
                    <label class="form-label" for="group_id">Assign to Group</label>
                    <select id="group_id" name="group_id" class="form-select">
                        <option value="">— Select a group —</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}"
                                {{ old('group_id', $listingGroup->group_id ?? '') == $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="divider"></div>

            {{-- Utilities. The letter codes drive how each cost is split in the
                 owner report — see App\Support\ListingOptions. Unchecking a row
                 clears the option, matching update()'s waterO/internetO checks. --}}
            <div class="font-600" style="font-size:14px;margin-bottom:10px">Utilities</div>

            @foreach([['water','waterO','Water'],['internet','internetO','Internet'],['electricity','electricityO','Electricity'],['mfsf','mfsfO','MF + SF']] as [$field, $flag, $label])
                @php $current = old($field, $listing->{$field}); @endphp
                <div class="form-row" style="grid-template-columns:220px 1fr;align-items:center">
                    <div class="form-group" style="margin-bottom:12px">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="checkbox" name="{{ $flag }}" value="1"
                                   {{ old($flag, $current ? 1 : 0) ? 'checked' : '' }}
                                   onchange="this.closest('.form-row').querySelector('select').disabled = !this.checked">
                            <span class="form-label" style="margin:0">{{ $label }}</span>
                        </label>
                    </div>
                    <div class="form-group" style="margin-bottom:12px">
                        <select name="{{ $field }}" class="form-select" {{ $current ? '' : 'disabled' }}>
                            @foreach(\App\Support\ListingOptions::UTILITY_OPTIONS as $code => $optLabel)
                                <option value="{{ $code }}" {{ $current === $code ? 'selected' : '' }}>{{ $optLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endforeach

            <div class="divider"></div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="status">Status <span style="color:var(--red)">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        @foreach(\App\Support\ListingOptions::STATUSES as $v => $label)
                            <option value="{{ $v }}" {{ (string) old('status', $listing->status) === (string) $v ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="banner">Banner</label>
                    @if($listing->banner)
                        <div style="margin-bottom:6px"><img src="/images/listing/{{ $listing->banner }}" alt="" style="max-height:60px;border-radius:6px"></div>
                    @endif
                    <input type="file" id="banner" name="banner" class="form-input" accept="image/*">
                    <div style="font-size:12px;color:var(--text-secondary);margin-top:4px">Leave empty to keep the current banner.</div>
                </div>
            </div>

            <div class="divider"></div>

            <div class="flex gap-2" style="justify-content:flex-end">
                <a href="/admin/listing" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Update
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
    section.style.display = (type === 'group') ? '' : 'none';
    if (type !== 'group') document.getElementById('group_id').value = '';
}
</script>
@endpush
