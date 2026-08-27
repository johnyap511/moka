@extends('admin.layout')
@section('title', 'EZEE Group Listings')
@section('page-title', 'EZEE Group Listings')

@section('content')

<div class="page-header">
    <div>
        <h1>{{ $group->name ?? 'EZEE Group' }}</h1>
        <p>Hotel code {{ $group->hotel_code ?? '—' }} — listings attached to this property</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/ezee/group" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Groups
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error" style="margin-bottom:16px">{{ session('error') }}</div>
@endif

@php
    $attachedIds = collect($addedListings ?? [])->pluck('listing_id')->all();
    $available   = collect($listings ?? [])->reject(fn ($l) => in_array($l->id, $attachedIds));
@endphp

<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h2>Attach a Listing</h2></div>
    <div class="card-body">
        <form method="POST" action="/admin/ezee/group/listing/{{ $group->id }}"
              class="flex gap-2 items-center" style="flex-wrap:wrap">
            @csrf
            @include('admin.partials.combobox', [
                'id'          => 'grouplisting',
                'name'        => 'listing_id',
                'items'       => $available,
                'placeholder' => 'Type to search listings…',
                'style'       => 'min-width:320px',
                'required'    => true,
            ])
            <button type="submit" class="btn btn-primary">Attach</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Attached Listings</h2>
        <span class="badge badge-blue">{{ count($attachedIds) }}</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:100px">ID</th>
                    <th>Listing</th>
                    <th style="width:120px">Action</th>
                </tr>
            </thead>
            <tbody>
                @php $byId = collect($listings ?? [])->keyBy('id'); @endphp
                @forelse($addedListings as $added)
                @php $listing = $byId[$added->listing_id] ?? null; @endphp
                <tr>
                    <td class="text-secondary">{{ $added->listing_id }}</td>
                    <td>
                        @if($listing)
                            <a href="/admin/listing/{{ $listing->id }}/edit" style="color:var(--teal);font-weight:500">{{ $listing->name }}</a>
                        @else
                            <span class="text-secondary">— listing no longer exists —</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="/admin/ezee/group/listing/{{ $group->id }}" style="margin:0"
                              onsubmit="return confirm('Detach this listing from {{ addslashes($group->name ?? 'this group') }}?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="listing_id" value="{{ $added->listing_id }}">
                            <button type="submit" class="btn btn-secondary btn-sm" style="color:#dc2626">Detach</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" style="padding:40px;text-align:center;color:var(--text-secondary)">No listings attached yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
