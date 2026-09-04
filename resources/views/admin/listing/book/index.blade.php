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
        <a href="/admin/booking/excel/template" class="btn btn-secondary" title="Blank workbook with the columns the import expects">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Download Template
        </a>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('import-file').click()">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Import Excel
        </button>
        <form method="POST" action="/admin/booking/excel/import" enctype="multipart/form-data" id="import-form" style="display:none">
            @csrf
            <input type="file" name="file" id="import-file" accept=".csv,.xlsx,.xls"
                   onchange="document.getElementById('import-form').submit()">
        </form>
        <a href="/admin/booking/excel/export" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export Excel
        </a>
        <a href="/admin/book/create" class="btn btn-primary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Booking
        </a>
    </div>
</div>

@isset($excelResult)
    <div class="alert alert-success" style="margin-bottom:16px">
        <strong>Import finished.</strong>
        @if(is_array($excelResult) || is_object($excelResult))
            <pre style="margin:8px 0 0;font-size:12px;white-space:pre-wrap">{{ json_encode($excelResult, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
        @else
            {{ $excelResult }}
        @endif
    </div>
@endisset

{{-- Stats --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="val">{{ $totalBookings ?? $books->count() }}</div>
        <div class="lbl">Total Bookings</div>
    </div>
    <div class="stat-card">
        <div class="val" style="color:var(--teal)">{{ $confirmedCount ?? 0 }}</div>
        <div class="lbl">Confirmed</div>
    </div>
    <div class="stat-card">
        <div class="val" style="color:var(--blue)">{{ $pendingCount ?? 0 }}</div>
        <div class="lbl">Pending</div>
    </div>
    <div class="stat-card">
        <div class="val" style="color:var(--text-secondary)">{{ $cancelledCount ?? 0 }}</div>
        <div class="lbl">Cancelled</div>
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="padding:16px 20px">
        <form method="GET" action="{{ request()->url() }}" class="flex gap-2 items-center" style="flex-wrap:wrap">
            <div class="search-bar" style="flex:1;min-width:180px">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search booking ID, guest, property or folio no…">
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

{{-- Date-range exports. exportExcelRange switches on `action`: loaddata and
     loaddatacheckin filter the table, exportreport and the default branch
     download a workbook. --}}
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="padding:16px 20px;display:grid;gap:12px">

        <form method="POST" action="/admin/booking/excel/export_range" class="flex gap-2 items-center" style="flex-wrap:wrap">
            @csrf
            <label class="form-label" style="margin:0;min-width:150px">By check-in date</label>
            <input type="date" name="checkin_date"   value="{{ $checkin_date ?? '' }}"   class="form-input" style="width:auto" required>
            <input type="date" name="checkinto_date" value="{{ $checkinto_date ?? '' }}" class="form-input" style="width:auto" required>
            <button type="submit" name="action" value="loaddatacheckin" class="btn btn-secondary btn-sm">Show</button>
            <button type="submit" name="action" value="exportreport" class="btn btn-primary btn-sm">Export Excel</button>
        </form>

        <form method="POST" action="/admin/booking/excel/export_range" class="flex gap-2 items-center" style="flex-wrap:wrap">
            @csrf
            <label class="form-label" style="margin:0;min-width:150px">By created date</label>
            <input type="date" name="from_date" value="{{ $from_date ?? '' }}" class="form-input" style="width:auto" required>
            <input type="date" name="to_date"   value="{{ $to_date ?? '' }}"   class="form-input" style="width:auto" required>
            <button type="submit" name="action" value="loaddata" class="btn btn-secondary btn-sm">Show</button>
            <button type="submit" name="action" value="exportcreated" class="btn btn-primary btn-sm">Export Excel</button>
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
                        @if($book->price ?? null)
                            <span class="font-600">{{ number_format($book->price, 2) }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td><span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span></td>
                    <td>
                        <div class="actions">
                            <a href="/admin/book/{{ $book->id }}" class="btn btn-secondary btn-sm">View</a>
                            <a href="/admin/book/{{ $book->id }}/edit" class="btn btn-secondary btn-sm">Edit</a>
                            @if((int) $book->status !== 1)
                            <button type="button" class="btn btn-secondary btn-sm" style="color:#b91c1c;border-color:#fecaca" onclick="cancelBookingRow(this, {{ $book->id }}, '{{ $book->check_in }}', '{{ $book->check_out }}')" title="Cancel: unit freed, eZee record retired, nothing deleted">Cancel</button>
                            @endif
                            @if(admin_can('bookings.delete'))
                            <form action="/admin/book/{{ $book->id }}" method="POST"
                                  onsubmit="return confirm('Delete this booking permanently?\n\nDeleting loses the history and the eZee link, and the 6 AM job may re-create the stay. Use Cancel instead unless this booking was keyed by mistake.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                            @endif
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

    @if(method_exists($books, 'lastPage') && $books->lastPage() > 1)
        <div class="card-body" style="padding-top:0;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <span class="text-sm text-secondary">
                Showing {{ $books->firstItem() }}–{{ $books->lastItem() }} of {{ number_format($books->total()) }} results
            </span>
            <div style="display:flex;align-items:center;gap:6px">
                @if($books->onFirstPage())
                    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default">← Prev</span>
                @else
                    <a href="{{ $books->withQueryString()->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Prev</a>
                @endif

                @foreach($books->withQueryString()->getUrlRange(max(1,$books->currentPage()-2), min($books->lastPage(),$books->currentPage()+2)) as $page => $url)
                    @if($page == $books->currentPage())
                        <span class="btn btn-primary btn-sm">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="btn btn-secondary btn-sm">{{ $page }}</a>
                    @endif
                @endforeach

                @if($books->hasMorePages())
                    <a href="{{ $books->withQueryString()->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
                @else
                    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default">Next →</span>
                @endif
            </div>
        </div>
    @endif
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
