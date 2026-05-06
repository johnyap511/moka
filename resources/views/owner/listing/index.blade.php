@extends('owner.layout')

@section('title', 'My Listings')
@section('page-title', 'My Listings')

@section('content')
<div class="page-header">
    <div>
        <h1>My Listings</h1>
        <p>All properties assigned to your account.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Properties ({{ $listings->count() }})</h2>
    </div>
    @if($listings->isEmpty())
        <div class="empty-state">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
            <p>No properties assigned yet</p>
            <small>Contact admin to get your properties listed.</small>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Listing Name</th>
                        <th>Address</th>
                        <th>Price / Night</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($listings as $listing)
                    <tr>
                        <td class="text-secondary">{{ $listing->id }}</td>
                        <td>
                            <div class="font-600">{{ $listing->title ?? $listing->name }}</div>
                            @if($listing->type === 'group')
                                <div class="text-sm text-secondary">Group listing</div>
                            @endif
                        </td>
                        <td class="text-secondary">{{ $listing->address }}</td>
                        <td>RM {{ number_format($listing->default_price ?? 0) }}</td>
                        <td>
                            @if($listing->status == 1)
                                <span class="badge badge-green">Active</span>
                            @else
                                <span class="badge badge-red">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <a href="/owner/listing/{{ $listing->id }}/calendar" class="btn btn-secondary btn-sm">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Calendar
                                </a>
                                <a href="/owner/listing/chart/report?listing_id={{ $listing->id }}" class="btn btn-secondary btn-sm">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Report
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
