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

            {{-- Stored on the listing_price_details row. update() zeroes the
                 *_has flags when absent, so these must always post. --}}
            <div class="font-600" style="font-size:14px;margin-bottom:10px">Charges &amp; Deposits</div>

            @foreach([['insurance','Insurance'],['promo','Promo Code'],['discount','Discount']] as [$k, $label])
                <div class="form-row" style="grid-template-columns:220px 1fr 1fr;align-items:center">
                    <div class="form-group" style="margin-bottom:12px">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="checkbox" name="{{ $k }}_has" value="1"
                                   {{ old($k.'_has', $listingPriceDetail->{$k.'_has'} ?? 0) ? 'checked' : '' }}>
                            <span class="form-label" style="margin:0">{{ $label }}</span>
                        </label>
                    </div>
                    @if($k === 'promo')
                        <div class="form-group" style="margin-bottom:12px">
                            <input type="text" name="promo_code" class="form-input" placeholder="Code"
                                   value="{{ old('promo_code', $listingPriceDetail->promo_code ?? '') }}">
                        </div>
                    @else
                        <div class="form-group" style="margin-bottom:12px">
                            <select name="{{ $k }}_fixed" class="form-select">
                                <option value="1" {{ old($k.'_fixed', $listingPriceDetail->{$k.'_fixed'} ?? 1) == 1 ? 'selected' : '' }}>Fixed (RM)</option>
                                <option value="0" {{ old($k.'_fixed', $listingPriceDetail->{$k.'_fixed'} ?? 1) == 0 ? 'selected' : '' }}>Percentage (%)</option>
                            </select>
                        </div>
                    @endif
                    <div class="form-group" style="margin-bottom:12px">
                        <input type="number" step="0.01" min="0" name="{{ $k }}_amount" class="form-input" placeholder="Amount"
                               value="{{ old($k.'_amount', $listingPriceDetail->{$k.'_amount'} ?? '') }}">
                    </div>
                </div>
            @endforeach

            <div class="form-row-3">
                <div class="form-group">
                    <label class="form-label" for="advance_rental">Advance Rental</label>
                    <input type="number" step="0.01" min="0" id="advance_rental" name="advance_rental" class="form-input"
                           value="{{ old('advance_rental', $listingPriceDetail->advance_rental ?? '0') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="security_deposit">Security Deposit</label>
                    <input type="number" step="0.01" min="0" id="security_deposit" name="security_deposit" class="form-input"
                           value="{{ old('security_deposit', $listingPriceDetail->security_deposit ?? '0') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="utility_deposit">Utility Deposit</label>
                    <input type="number" step="0.01" min="0" id="utility_deposit" name="utility_deposit" class="form-input"
                           value="{{ old('utility_deposit', $listingPriceDetail->utility_deposit ?? '0') }}">
                </div>
            </div>

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
