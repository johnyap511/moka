@extends('admin.layout')

@section('title', 'Listings')
@section('page-title', 'Listings')

@section('content')

{{-- Page Header --}}
<div class="page-header">
    <div>
        <h1>Listings</h1>
        <p>Manage all properties in the system</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/listing/create" class="btn btn-primary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Listing
        </a>
    </div>
</div>

{{-- Stats Grid --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="val">{{ $listings->count() }}</div>
        <div class="lbl">Total Listings</div>
    </div>
    <div class="stat-card">
        <div class="val" style="color:var(--teal)">{{ $listings->where('status', 1)->count() }}</div>
        <div class="lbl">Active</div>
    </div>
    <div class="stat-card">
        <div class="val" style="color:var(--text-secondary)">{{ $listings->where('status', 0)->count() }}</div>
        <div class="lbl">Inactive</div>
    </div>
    <div class="stat-card">
        <div class="val" style="color:var(--blue)">{{ $listings->where('type', 'group')->count() }}</div>
        <div class="lbl">Group Listings</div>
    </div>
</div>

{{-- Search + Table Card --}}
<div class="card">
    <div class="card-header">
        <h2>All Listings</h2>
        <form method="GET" action="/admin/listing" style="display:flex;align-items:center;gap:10px">
            <div class="search-bar">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search listings…" id="searchInput">
            </div>
            <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            @if(request('q'))
                <a href="/admin/listing" class="btn btn-secondary btn-sm">Clear</a>
            @endif
        </form>
    </div>

    <div class="table-wrap">
        <table id="listingsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Address</th>
                    <th>Type</th>
                    <th>Price/night</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($listings as $listing)
                <tr class="listing-row"
                    data-title="{{ strtolower($listing->title ?? '') }}"
                    data-name="{{ strtolower($listing->name ?? '') }}"
                    data-address="{{ strtolower($listing->address ?? '') }}">
                    <td class="mono">{{ $listing->id }}</td>
                    <td>
                        <div class="font-600">{{ $listing->title ?? '—' }}</div>
                        @if($listing->name && $listing->name !== $listing->title)
                            <div class="text-sm text-secondary mt-1">{{ $listing->name }}</div>
                        @endif
                    </td>
                    <td>
                        <div style="max-width:220px" class="truncate" title="{{ $listing->address }}">
                            {{ $listing->address ?? '—' }}
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-teal">{{ ucfirst($listing->type ?? 'solo') }}</span>
                    </td>
                    <td>
                        @if($listing->default_price)
                            <span class="font-600">RM {{ number_format($listing->default_price, 2) }}</span>
                        @else
                            <span class="text-secondary">—</span>
                        @endif
                    </td>
                    <td>
                        @if($listing->status == 1)
                            <span class="badge badge-green">Active</span>
                        @else
                            <span class="badge badge-red">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="actions">
                            <a href="/admin/listing/{{ $listing->id }}/edit" class="btn btn-secondary btn-sm">Edit</a>
                            <a href="/admin/listing/{{ $listing->id }}/images" class="btn btn-secondary btn-sm">Images</a>
                            <a href="/admin/listing/{{ $listing->id }}/price" class="btn btn-secondary btn-sm">Pricing</a>
                            <a href="/admin/listing/{{ $listing->id }}/details" class="btn btn-secondary btn-sm">Details</a>
                            <form action="/admin/listing/{{ $listing->id }}" method="POST"
                                  onsubmit="return confirm('Delete this listing? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <p>No listings found</p>
                            <small>Create your first listing to get started</small>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Client-side filter for instant search
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.listing-row').forEach(function (row) {
            if (!q) { row.style.display = ''; return; }
            const haystack = (row.dataset.title || '') + ' ' + (row.dataset.name || '') + ' ' + (row.dataset.address || '');
            row.style.display = haystack.includes(q) ? '' : 'none';
        });
    });
}
</script>
@endpush
