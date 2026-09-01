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
        @if($showArchived)
            <a href="{{ route('admin.listing.index') }}" class="btn btn-secondary">&larr; Back to managed</a>
        @else
            <a href="{{ route('admin.listing.index', ['archived' => 1]) }}" class="btn btn-secondary">
                Archived ({{ $archivedCount }})
            </a>
        @endif
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
{{-- Appears only once something is ticked, so it stays out of the way. --}}
<div id="bulk-bar" style="display:none;align-items:center;gap:12px;padding:10px 14px;margin-bottom:12px;
                          background:#faf8f4;border:1px solid #e4e0d8;border-radius:8px">
    <strong id="bulk-count" style="font-size:13px"></strong>
    <button type="button" class="btn btn-primary" style="padding:5px 14px;font-size:12px"
            onclick="bulkArchive()">{{ $showArchived ? 'Restore selected' : 'Archive selected' }}</button>
    <button type="button" class="btn btn-secondary" style="padding:5px 14px;font-size:12px"
            onclick="clearSelection()">Clear</button>
    <span style="font-size:12px;color:var(--text-secondary)">Only rows visible under the current filter are selected.</span>
</div>

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
                    <th style="width:34px;text-align:center">
                        <input type="checkbox" id="select-all" onchange="toggleSelectAll(this.checked)"
                               title="Select every row currently visible">
                    </th>
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
                <tr class="listing-row" data-id="{{ $listing->id }}"
                    data-title="{{ strtolower($listing->title ?? '') }}"
                    data-name="{{ strtolower($listing->name ?? '') }}"
                    data-address="{{ strtolower($listing->address ?? '') }}">
                    <td style="text-align:center">
                        <input type="checkbox" class="row-select" value="{{ $listing->id }}" onchange="refreshSelection()">
                    </td>
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
                        {{-- Click to flip live/not-live without opening the edit form. --}}
                        <button type="button"
                                class="badge {{ $listing->status == 1 ? 'badge-green' : 'badge-red' }}"
                                style="border:none;cursor:pointer"
                                title="Click to {{ $listing->status == 1 ? 'deactivate' : 'activate' }}"
                                onclick="toggleStatus(this, {{ $listing->id }})">
                            {{ $listing->status == 1 ? 'Active' : 'Inactive' }}
                        </button>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="/admin/listing/{{ $listing->id }}/edit" class="btn btn-secondary btn-sm">Edit</a>
                            <a href="/admin/listing/{{ $listing->id }}/images" class="btn btn-secondary btn-sm">Images</a>
                            <a href="/admin/listing/{{ $listing->id }}/price" class="btn btn-secondary btn-sm">Pricing</a>
                            <a href="/admin/listing/{{ $listing->id }}/details" class="btn btn-secondary btn-sm">Details</a>
                            <button type="button" class="btn btn-secondary btn-sm"
                                    onclick="setArchived(this, {{ $listing->id }}, {{ $showArchived ? 'false' : 'true' }})">
                                {{ $showArchived ? 'Restore' : 'Archive' }}
                            </button>
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
                    <td colspan="8">
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

<script>
// Archive and status changes happen in place. A form post reloaded the page and
// sent the reader back to the top, which is unworkable on a list this long.

const LISTING_ARCHIVE_URL = '{{ route('admin.listing.archive') }}';
const ARCHIVING = {{ $showArchived ? 'false' : 'true' }};
const CSRF = '{{ csrf_token() }}';

function visibleRowSelects() {
    return Array.from(document.querySelectorAll('.row-select'))
        .filter(cb => cb.closest('tr').style.display !== 'none');
}

function selectedIds() {
    return visibleRowSelects().filter(cb => cb.checked).map(cb => Number(cb.value));
}

function toggleSelectAll(checked) {
    visibleRowSelects().forEach(cb => { cb.checked = checked; });
    refreshSelection();
}

function clearSelection() {
    document.querySelectorAll('.row-select').forEach(cb => { cb.checked = false; });
    const all = document.getElementById('select-all');
    if (all) { all.checked = false; }
    refreshSelection();
}

function refreshSelection() {
    const n = selectedIds().length;
    const bar = document.getElementById('bulk-bar');
    bar.style.display = n ? 'flex' : 'none';
    document.getElementById('bulk-count').textContent =
        n + ' propert' + (n === 1 ? 'y' : 'ies') + ' selected';
}

async function postJson(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.ok) { throw new Error(data.message || ('Request failed (' + res.status + ')')); }
    return data;
}

async function toggleStatus(btn, id) {
    const previous = btn.textContent.trim();
    btn.disabled = true;

    try {
        const data = await postJson('/admin/listing/' + id + '/status', {});
        btn.textContent = data.label;
        btn.classList.toggle('badge-green', data.status === 1);
        btn.classList.toggle('badge-red', data.status !== 1);
        btn.title = 'Click to ' + (data.status === 1 ? 'deactivate' : 'activate');
    } catch (e) {
        alert('Could not change status: ' + e.message);
        btn.textContent = previous;
    } finally {
        btn.disabled = false;
    }
}

async function setArchived(btn, id, archived) {
    if (archived && !confirm('Archive this property?\n\nIt will be hidden from this list and will not be assigned new EZEE bookings. Existing bookings are unchanged, and you can restore it from the Archived view.')) {
        return;
    }

    btn.disabled = true;
    btn.textContent = archived ? 'Archiving…' : 'Restoring…';

    try {
        await postJson(LISTING_ARCHIVE_URL, { ids: [id], archived: archived });
        btn.closest('tr').remove();
        refreshSelection();
    } catch (e) {
        alert('Could not update that property: ' + e.message);
        btn.disabled = false;
        btn.textContent = archived ? 'Archive' : 'Restore';
    }
}

async function bulkArchive() {
    const ids = selectedIds();
    if (!ids.length) { return; }

    const verb = ARCHIVING ? 'Archive' : 'Restore';
    const note = ARCHIVING
        ? '\n\nThey will be hidden from this list and will not be assigned new EZEE bookings. Existing bookings are unchanged, and you can restore them from the Archived view.'
        : '';

    if (!confirm(verb + ' ' + ids.length + ' propert' + (ids.length === 1 ? 'y' : 'ies') + '?' + note)) { return; }

    const btn = document.querySelector('#bulk-bar .btn-primary');
    btn.disabled = true;
    btn.textContent = ARCHIVING ? 'Archiving…' : 'Restoring…';

    try {
        await postJson(LISTING_ARCHIVE_URL, { ids: ids, archived: ARCHIVING });
        visibleRowSelects().filter(cb => cb.checked).forEach(cb => cb.closest('tr').remove());
        clearSelection();
    } catch (e) {
        alert('Could not update those properties: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.textContent = verb + ' selected';
    }
}
</script>
