@extends('admin.layout')
@section('title', 'Upload Bookings')
@section('page-title', 'Upload Bookings')

@section('content')

<div class="page-header">
    <div>
        <h1>Upload Bookings</h1>
        <p>Import EZEE bookings from a spreadsheet</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/ezee/booking" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            EZEE Bookings
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error" style="margin-bottom:16px">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="alert alert-error" style="margin-bottom:16px">
    <ul style="margin:0;padding-left:18px">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h2>Upload File</h2></div>
    <div class="card-body">
        <form method="POST" action="/admin/ezee/upload_bookings_data" enctype="multipart/form-data"
              class="flex gap-2 items-center" style="flex-wrap:wrap">
            @csrf
            <input type="file" name="file" class="form-input" accept=".csv,.xlsx,.xls" required style="width:auto">
            <button type="submit" class="btn btn-primary">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Upload
            </button>
        </form>
        <div class="form-help" style="margin-top:10px">
            {{-- uploadBookingData() matches the listing by name and skips any row
                 whose listing is not found, or whose reservation already exists. --}}
            The first row must be a header. Rows are matched to listings by name, and a row is
            skipped when no listing matches or the reservation is already recorded.
        </div>
    </div>
</div>

@php $rows = collect($get_all_bookings ?? []); @endphp

<div class="card">
    <div class="card-header">
        <h2>This Month by Property</h2>
        <span class="badge badge-blue">{{ number_format($rows->sum('total')) }} bookings</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:180px">Property ID</th>
                    <th>Property</th>
                    <th style="width:180px">Total Bookings</th>
                </tr>
            </thead>
            <tbody>
                @php $groups = \App\EzeeGroup::all()->keyBy('hotel_code'); @endphp
                @forelse($rows as $row)
                <tr>
                    <td style="font-family:monospace">{{ $row->property_id }}</td>
                    <td>{{ $groups[$row->property_id]->name ?? '—' }}</td>
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
