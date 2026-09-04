@extends('admin.layout')
@section('title', 'Booking Detail')
@section('page-title', 'Booking Detail')

@section('content')

<div class="page-header">
    <div>
        <h1>Booking #{{ $book->id }}</h1>
        <p>Full details for this reservation</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/book" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back
        </a>
        <a href="/admin/book/{{ $book->id }}/edit" class="btn btn-primary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit
        </a>
        <a href="/admin/book/{{ $book->id }}/edit#unit-card" class="btn btn-secondary" title="Reassign, split or swap the unit">Unit / room move</a>
        @if((int) $book->status !== 1)
        <button type="button" class="btn btn-secondary" style="color:#b91c1c;border-color:#fecaca" onclick="cancelBookingRow(this, {{ $book->id }}, '{{ $book->check_in }}', '{{ $book->check_out }}')">Cancel booking</button>
        @else
        <span class="badge badge-red" style="align-self:center">Cancelled</span>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    {{-- Booking Info --}}
    <div class="card">
        <div class="card-header">
            <h2>Booking Info</h2>
            @php
                $statusMap = [0=>'badge-red',1=>'badge-blue',2=>'badge-yellow',3=>'badge-yellow',5=>'badge-green',7=>'badge-teal',9=>'badge-gray'];
                $statusLabels = [0=>'Cancelled',1=>'New',2=>'Processing',3=>'Pending',5=>'Confirmed',7=>'Checked In',9=>'Completed'];
                $cls = $statusMap[$book->status ?? 1] ?? 'badge-gray';
                $lbl = $statusLabels[$book->status ?? 1] ?? 'Unknown';
            @endphp
            <span class="badge {{ $cls }}">{{ $lbl }}</span>
        </div>
        <div class="card-body" style="padding:0">
            <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
                <div class="text-sm text-secondary">Check In</div>
                <div class="font-600">{{ $book->check_in ? \Carbon\Carbon::parse($book->check_in)->format('d M Y') : '—' }}</div>
            </div>
            <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
                <div class="text-sm text-secondary">Check Out</div>
                <div class="font-600">{{ $book->check_out ? \Carbon\Carbon::parse($book->check_out)->format('d M Y') : '—' }}</div>
            </div>
            <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
                <div class="text-sm text-secondary">Total Price</div>
                <div class="font-600">RM {{ number_format($book->price ?? 0, 2) }}</div>
            </div>
            <div style="padding:14px 20px">
                <div class="text-sm text-secondary">Booking Date</div>
                <div class="font-600">{{ $book->created_at ? $book->created_at->format('d M Y H:i') : '—' }}</div>
            </div>
        </div>
    </div>

    {{-- Guest Info --}}
    <div class="card">
        <div class="card-header">
            <h2>Guest</h2>
        </div>
        <div class="card-body" style="padding:0">
            @if($user)
            <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
                <div class="text-sm text-secondary">Name</div>
                <div class="font-600">{{ $user->name }}</div>
            </div>
            <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
                <div class="text-sm text-secondary">Email</div>
                <div class="font-600">{{ $user->email }}</div>
            </div>
            <div style="padding:14px 20px">
                <div class="text-sm text-secondary">Phone</div>
                <div class="font-600">{{ $user->phone ?? '—' }}</div>
            </div>
            @else
            <div style="padding:20px"><span class="text-secondary">Guest not found</span></div>
            @endif
        </div>
    </div>

    {{-- Listing Info --}}
    <div class="card">
        <div class="card-header">
            <h2>Property</h2>
        </div>
        <div class="card-body" style="padding:0">
            @if($listing)
            <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
                <div class="text-sm text-secondary">Name</div>
                <div class="font-600">{{ $listing->title ?? $listing->name ?? '—' }}</div>
            </div>
            <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
                <div class="text-sm text-secondary">Address</div>
                <div class="font-600">{{ $listing->address ?? '—' }}</div>
            </div>
            <div style="padding:14px 20px">
                <a href="/admin/listing/{{ $listing->id }}/edit" class="btn btn-secondary btn-sm">View Listing</a>
            </div>
            @else
            <div style="padding:20px"><span class="text-secondary">Listing not found</span></div>
            @endif
        </div>
    </div>

    {{-- Owner Info --}}
    <div class="card">
        <div class="card-header">
            <h2>Owner</h2>
        </div>
        <div class="card-body" style="padding:0">
            @if($owner)
            <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
                <div class="text-sm text-secondary">Name</div>
                <div class="font-600">{{ $owner->name }}</div>
            </div>
            <div style="padding:14px 20px">
                <div class="text-sm text-secondary">Email</div>
                <div class="font-600">{{ $owner->email }}</div>
            </div>
            @else
            <div style="padding:20px"><span class="text-secondary">Owner not found</span></div>
            @endif
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
async function cancelBookingRow(btn, id, ci, co) {
    var reason = prompt('Cancel booking #' + id + ' (' + ci + ' to ' + co + ').\n\nThe unit is freed, the eZee record is retired, nothing is deleted.\n\nReason:', 'voided in eZee');
    if (reason === null) { return; }
    if (!reason.trim()) { alert('A reason is required.'); return; }
    btn.disabled = true;
    try {
        const res = await fetch('/admin/booking/' + id + '/cancel', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: JSON.stringify({ reason: reason.trim() }) });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) { throw new Error(data.message || 'Request failed'); }
        alert(data.message); window.location.reload();
    } catch (e) { alert('Not done: ' + e.message); btn.disabled = false; }
}
</script>
@endpush
