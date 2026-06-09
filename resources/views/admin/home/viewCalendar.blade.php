@extends('admin.layout')
@section('title', 'Availability')
@section('page-title', 'Availability')

@push('styles')
<style>
.avail-table { width:100%; border-collapse:collapse; min-width:700px; }
.avail-table th, .avail-table td { padding:8px 12px; border:1px solid var(--border); font-size:13px; white-space:nowrap; }
.avail-table thead th { background:var(--bg-secondary); font-weight:600; font-size:11.5px; text-transform:uppercase; letter-spacing:.4px; color:var(--text-secondary); text-align:center; }
.avail-table td.listing-name { font-weight:500; max-width:200px; overflow:hidden; text-overflow:ellipsis; }
.cell-booked   { background:#fee2e2; color:#991b1b; text-align:center; font-size:12px; font-weight:500; }
.cell-avail    { background:#d1fae5; color:#065f46; text-align:center; font-size:12px; }
.cell-today    { border-left:3px solid var(--blue) !important; }
.avail-nav     { display:flex; align-items:center; gap:8px; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>Availability</h1>
        <p>7-day booking availability for all listings</p>
    </div>
</div>

{{-- Date navigation --}}
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" action="/admin/view/calendar" class="avail-nav">
            <a href="/admin/view/calendar?date={{ date('Y-m-d', strtotime($date . ' -7 days')) }}" class="btn btn-secondary btn-sm">← Prev week</a>
            <input type="date" name="date" value="{{ $date }}" class="form-input" style="width:180px" onchange="this.form.submit()">
            <button type="submit" class="btn btn-primary btn-sm">Go</button>
            <a href="/admin/view/calendar" class="btn btn-secondary btn-sm">Today</a>
            <a href="/admin/view/calendar?date={{ date('Y-m-d', strtotime($date . ' +7 days')) }}" class="btn btn-secondary btn-sm">Next week →</a>
            <span style="margin-left:8px;font-size:12px;color:var(--text-secondary)">
                <span style="display:inline-block;width:12px;height:12px;background:#d1fae5;border-radius:2px;margin-right:4px;vertical-align:middle"></span>Available
                <span style="display:inline-block;width:12px;height:12px;background:#fee2e2;border-radius:2px;margin-left:8px;margin-right:4px;vertical-align:middle"></span>Booked
            </span>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap" style="overflow-x:auto">
        <table class="avail-table">
            <thead>
                <tr>
                    <th style="text-align:left;min-width:160px">Listing</th>
                    @foreach($dateArray as $d)
                    @php
                        $isToday = ($d === date('Y-m-d'));
                        $dt = date_create($d);
                    @endphp
                    <th style="{{ $isToday ? 'color:var(--blue)' : '' }}">
                        {{ date_format($dt, 'D') }}<br>
                        <span style="font-size:12px;font-weight:400">{{ date_format($dt, 'd M') }}</span>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($listings as $listing)
                @php
                    $listingBooks = $groups[$listing->id] ?? collect();
                @endphp
                <tr>
                    <td class="listing-name" title="{{ $listing->name }}">
                        <a href="/admin/listing/{{ $listing->id }}/calendar" style="color:inherit;text-decoration:none">
                            {{ $listing->name }}
                        </a>
                    </td>
                    @foreach($dateArray as $d)
                    @php
                        $booked = false;
                        $bookedGuest = '';
                        foreach ($listingBooks as $bk) {
                            if ($d >= $bk->check_in && $d < $bk->check_out) {
                                $booked = true;
                                $bookedGuest = $bk->name ?? ($bk->user->name ?? '');
                                break;
                            }
                        }
                        $isToday = ($d === date('Y-m-d'));
                    @endphp
                    <td class="{{ $booked ? 'cell-booked' : 'cell-avail' }} {{ $isToday ? 'cell-today' : '' }}"
                        title="{{ $booked && $bookedGuest ? $bookedGuest : ($booked ? 'Booked' : 'Available') }}">
                        {{ $booked ? '✕' : '✓' }}
                    </td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($dateArray) + 1 }}" style="text-align:center;padding:40px;color:var(--text-secondary)">
                        No listings found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
