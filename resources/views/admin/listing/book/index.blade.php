@extends('admin.layout')

@section('title', 'Bookings')
@section('page-title', 'Bookings')

@section('content')

@php
$statusMap = [
    0 => ['label' => 'Cancelled',   'class' => 'badge-red'],
    1 => ['label' => 'New',         'class' => 'badge-blue'],
    2 => ['label' => 'Processing',  'class' => 'badge-orange'],
    3 => ['label' => 'Pending',     'class' => 'badge-orange'],
    5 => ['label' => 'Confirmed',   'class' => 'badge-green'],
    7 => ['label' => 'Checked In',  'class' => 'badge-teal'],
    9 => ['label' => 'Completed',   'class' => 'badge-gray'],
];
@endphp

{{-- Page Header --}}
<div class="page-header">
    <div>
        <h1>Bookings</h1>
        <p>Track and manage all property reservations</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/booking/excel/export" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export Excel
        </a>
        <a href="#" class="btn btn-primary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Booking
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="val">{{ $books->total() ?? $books->count() }}</div>
        <div class="lbl">Total Bookings</div>
    </div>
    <div class="stat-card">
        <div class="val" style="color:var(--teal)">
            {{ $books->filter(fn($b) => $b->status == 5)->count() }}
        </div>
        <div class="lbl">Confirmed</div>
    </div>
    <div class="stat-card">
        <div class="val" style="color:var(--blue)">
            {{ $books->filter(fn($b) => in_array($b->status, [1,2,3]))->count() }}
        </div>
        <div class="lbl">Pending</div>
    </div>
    <div class="stat-card">
        <div class="val" style="color:var(--text-secondary)">
            {{ $books->filter(fn($b) => $b->status == 0)->count() }}
        </div>
        <div class="lbl">Cancelled</div>
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="padding:16px 20px">
        <form method="GET" action="{{ request()->url() }}" class="flex gap-2 items-center" style="flex-wrap:wrap">
            <div class="search-bar" style="flex:1;min-width:180px">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search guest name…">
            </div>
            <select name="status" class="form-select" style="width:auto;min-width:150px">
                <option value="">All Statuses</option>
                @foreach($statusMap as $val => $meta)
                    <option value="{{ $val }}" {{ request('status') === (string)$val ? 'selected' : '' }}>
                        {{ $meta['label'] }}
                    </option>
                @endforeach
            </select>
            <input type="date" name="from_date" value="{{ request('from_date', $from_date ?? '') }}"
                   class="form-input" style="width:auto" title="Check-in from">
            <input type="date" name="to_date" value="{{ request('to_date', $to_date ?? '') }}"
                   class="form-input" style="width:auto" title="Check-in to">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            @if(request()->hasAny(['q','status','from_date','to_date']))
                <a href="{{ request()->url() }}" class="btn btn-secondary btn-sm">Clear</a>
            @endif
        </form>
    </div>
</div>

{{-- Bookings Table --}}
<div class="card">
    <div class="card-header">
        <h2>All Bookings</h2>
        @if(method_exists($books, 'total'))
            <span class="text-sm text-secondary">{{ $books->total() }} records</span>
        @endif
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Guest Name</th>
                    <th>Property</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Nights</th>
                    <th>Total (RM)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($books as $book)
                @php
                    $statusInfo = $statusMap[$book->status] ?? ['label' => 'Unknown', 'class' => 'badge-gray'];
                    $checkIn  = $book->check_in  ? \Carbon\Carbon::parse($book->check_in)  : null;
                    $checkOut = $book->check_out ? \Carbon\Carbon::parse($book->check_out) : null;
                    $nights   = $book->nights ?? ($checkIn && $checkOut ? $checkIn->diffInDays($checkOut) : '—');
                @endphp
                <tr>
                    <td class="mono">#{{ $book->id }}</td>
                    <td>
                        <div class="font-600">{{ $book->name ?? $book->user->name ?? '—' }}</div>
                        @if($book->email ?? $book->user->email ?? null)
                            <div class="text-sm text-secondary">{{ $book->email ?? $book->user->email }}</div>
                        @endif
                    </td>
                    <td>
                        @if($book->listing ?? null)
                            <div>{{ $book->listing->title ?? $book->listing->name ?? '—' }}</div>
                        @else
                            <span class="text-secondary">—</span>
                        @endif
                    </td>
                    <td>{{ $checkIn ? $checkIn->format('d M Y') : '—' }}</td>
                    <td>{{ $checkOut ? $checkOut->format('d M Y') : '—' }}</td>
                    <td>{{ is_numeric($nights) ? $nights : $nights }}</td>
                    <td>
                        @if($book->total_price ?? null)
                            <span class="font-600">{{ number_format($book->total_price, 2) }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td><span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span></td>
                    <td>
                        <div class="actions">
                            <a href="/admin/book/{{ $book->id }}" class="btn btn-secondary btn-sm">View</a>
                            <a href="/admin/book/{{ $book->id }}/edit" class="btn btn-secondary btn-sm">Edit</a>
                            <form action="/admin/book/{{ $book->id }}" method="POST"
                                  onsubmit="return confirm('Delete this booking?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <p>No bookings found</p>
                            <small>Try adjusting your filters or create a new booking</small>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($books, 'links'))
        <div class="card-body" style="padding-top:0">
            <div class="pagination">
                {{ $books->withQueryString()->links() }}
            </div>
        </div>
    @endif
</div>

@endsection
