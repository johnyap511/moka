@extends('admin.layout')
@section('title', 'EZEE Report')
@section('page-title', 'EZEE Report')

@section('content')

<div class="page-header">
    <div>
        <h1>Consolidated Reports</h1>
        <p>EZEE bookings received per day, this month</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/ezee/booking" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            EZEE Bookings
        </a>
    </div>
</div>

@php
    $rows  = collect($get_all_bookings ?? []);
    $total = $rows->sum('total');
@endphp

<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="val">{{ number_format($total) }}</div>
        <div class="lbl">Bookings this month</div>
    </div>
    <div class="stat-card">
        <div class="val">{{ $rows->count() }}</div>
        <div class="lbl">Days with bookings</div>
    </div>
    <div class="stat-card">
        <div class="val">{{ $rows->count() ? number_format($total / $rows->count(), 1) : '0' }}</div>
        <div class="lbl">Average per day</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>By Date</h2>
        <div class="search-bar" style="min-width:240px">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="report-search" placeholder="Search date…" oninput="filterReport(this.value)">
        </div>
    </div>
    <div class="table-wrap">
        <table id="report-table">
            <thead>
                <tr>
                    <th style="width:100px">Sr. No.</th>
                    <th>Date</th>
                    <th style="width:180px">Total Bookings</th>
                </tr>
            </thead>
            <tbody id="report-body">
                @forelse($rows as $i => $row)
                <tr>
                    <td class="text-secondary">{{ $i + 1 }}</td>
                    <td>
                        {{-- Drills into that day via ezeeBookingsDate(). --}}
                        <a href="/admin/ezee/booking_by_date/{{ $row->date }}" style="color:var(--teal);font-weight:500">
                            {{ $row->date }}
                        </a>
                    </td>
                    <td><span class="badge badge-green">{{ $row->total }}</span></td>
                </tr>
                @empty
                <tr><td colspan="3" style="padding:40px;text-align:center;color:var(--text-secondary)">No EZEE bookings received this month.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
function filterReport(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('#report-body tr').forEach(function (row) {
        row.style.display = !q || row.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
@endpush
