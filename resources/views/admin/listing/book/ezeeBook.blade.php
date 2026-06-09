@extends('admin.layout')
@section('page-title', 'EZEE Bookings')

@push('styles')
<style>
.badge-assigned { background:#d1fae5; color:#065f46; padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:500; }
.badge-unassigned { background:#fff7ed; color:#c2410c; padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:500; }
.badge-source { background:#eff6ff; color:#1d4ed8; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:500; }
.assign-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:200; align-items:center; justify-content:center; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>EZEE Bookings</h1>
        <p>Imported bookings from EZEE — assign each to a listing unit</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error" style="margin-bottom:16px">{{ session('error') }}</div>
@endif

{{-- Filter Tabs --}}
<div style="display:flex;gap:8px;margin-bottom:20px">
    <a href="/admin/ezee/booking" class="btn {{ !request()->is('admin/ezee/unassigned_booking') && !request()->is('admin/ezee/assigned_booking') ? 'btn-primary' : 'btn-secondary' }}">All</a>
    <a href="/admin/ezee/unassigned_booking" class="btn {{ request()->is('admin/ezee/unassigned_booking') ? 'btn-primary' : 'btn-secondary' }}">Unassigned</a>
    <a href="/admin/ezee/assigned_booking" class="btn {{ request()->is('admin/ezee/assigned_booking') ? 'btn-primary' : 'btn-secondary' }}">Assigned</a>
</div>

<div class="card">
    <div class="card-header">
        <h2>Bookings <span class="badge badge-blue">{{ count($books) }}</span></h2>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;min-width:900px">
            <thead>
                <tr>
                    @foreach(['#','Guest','Room Type','Check In','Check Out','Amount','Source','Status','Action'] as $h)
                    <th style="padding:10px 14px;text-align:left;font-size:11.5px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border);white-space:nowrap">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($books as $i => $b)
                <tr style="border-bottom:1px solid var(--border)" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                    <td style="padding:10px 14px;font-size:13px">{{ $i + 1 }}</td>
                    <td style="padding:10px 14px;font-size:13px">
                        <div style="font-weight:500">{{ $b->FirstName }} {{ $b->LastName }}</div>
                        <div style="font-size:11.5px;color:var(--text-secondary)">{{ $b->Email }}</div>
                    </td>
                    <td style="padding:10px 14px;font-size:13px">{{ $b->RoomTypeName ?? '—' }}</td>
                    <td style="padding:10px 14px;font-size:13px">{{ $b->Start }}</td>
                    <td style="padding:10px 14px;font-size:13px">{{ $b->End }}</td>
                    <td style="padding:10px 14px;font-size:13px">RM {{ number_format($b->TotalAmountAfterTax ?? 0, 2) }}</td>
                    <td style="padding:10px 14px;font-size:13px"><span class="badge-source">{{ $b->Source ?? '—' }}</span></td>
                    <td style="padding:10px 14px;font-size:13px">
                        @if($b->book_id)
                            <span class="badge-assigned">Assigned</span>
                        @else
                            <span class="badge-unassigned">Unassigned</span>
                        @endif
                    </td>
                    <td style="padding:10px 14px;font-size:13px">
                        <button type="button" class="btn btn-primary" style="padding:4px 12px;font-size:12px"
                            onclick="openAssign({{ $b->id }}, '{{ addslashes($b->FirstName.' '.$b->LastName) }}', '{{ $b->Start }}', '{{ $b->End }}', '{{ $b->book_id }}')">
                            {{ $b->book_id ? 'Reassign' : 'Assign' }}
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" style="padding:40px;text-align:center;color:var(--text-secondary)">No EZEE bookings found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Assign Modal --}}
<div id="assign-modal" class="assign-modal">
    <div style="background:#fff;border-radius:16px;padding:28px;width:480px;max-width:90vw;box-shadow:0 20px 60px rgba(0,0,0,.2)">
        <h2 style="font-size:17px;font-weight:600;margin-bottom:4px">Assign Booking</h2>
        <p id="modal-guest" style="font-size:13px;color:var(--text-secondary);margin-bottom:20px"></p>
        <form method="POST" id="assign-form">
            @csrf
            <div class="form-group">
                <label class="form-label">Listing / Unit</label>
                <select name="listing_id" class="form-input" required>
                    <option value="">— Select unit —</option>
                    @foreach($listings as $l)
                    <option value="{{ $l->id }}">{{ $l->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label">Check In</label>
                    <input type="date" name="check_in" id="modal-checkin" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Check Out</label>
                    <input type="date" name="check_out" id="modal-checkout" class="form-input">
                </div>
            </div>
            <div id="reassign-wrap" style="display:none;margin-bottom:12px">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                    <input type="checkbox" name="reassign" value="1"> Reassign (update existing booking)
                </label>
            </div>
            <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px">
                <button type="button" class="btn btn-secondary" onclick="closeAssign()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Assignment</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openAssign(id, guest, start, end, bookId) {
    document.getElementById('modal-guest').textContent = guest + ' · ' + start + ' → ' + end;
    document.getElementById('modal-checkin').value = start;
    document.getElementById('modal-checkout').value = end;
    const route = bookId ? '/admin/ezee/bookingEdit/' + id : '/admin/ezee/booking/' + id;
    document.getElementById('assign-form').action = route;
    document.getElementById('reassign-wrap').style.display = bookId ? 'block' : 'none';
    document.getElementById('assign-modal').style.display = 'flex';
}
function closeAssign() {
    document.getElementById('assign-modal').style.display = 'none';
}
document.getElementById('assign-modal').addEventListener('click', function(e) {
    if (e.target === this) closeAssign();
});
</script>
@endpush
