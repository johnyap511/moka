@extends('admin.layout')
@section('title', 'Guests')
@section('page-title', 'Guests')

@section('content')

<div class="page-header">
    <div>
        <h1>Guests</h1>
        <p>Everyone who has stayed or registered</p>
    </div>
    <div class="flex gap-2">
        <a class="btn btn-secondary" href="/admin/users?type={{ $type ?? 'all' }}{{ ($q ?? '') !== '' ? '&q=' . urlencode($q) : '' }}&export=csv">Export CSV</a>
    </div>
</div>

{{-- Filter tabs and search --}}
@php
    $tabs = ['all' => 'All guests', 'booking' => 'From bookings', 'website' => 'Website accounts'];
    $current = $type ?? 'all';
@endphp
<div class="flex gap-2" style="border-bottom:1px solid var(--border);margin-bottom:14px;align-items:flex-end;justify-content:space-between;flex-wrap:wrap">
    <div class="flex gap-2">
    @foreach($tabs as $key => $label)
        <a href="/admin/users?type={{ $key }}{{ ($q ?? '') !== '' ? '&q=' . urlencode($q) : '' }}"
           style="padding:10px 14px;font-size:13.5px;font-weight:500;border-bottom:2px solid {{ $current === $key ? 'var(--teal)' : 'transparent' }};color:{{ $current === $key ? 'var(--teal)' : 'var(--text-secondary)' }};text-decoration:none">
            {{ $label }} <span class="badge badge-gray" style="margin-left:4px">{{ number_format($counts[$key] ?? 0) }}</span>
        </a>
    @endforeach
    </div>
    <form method="get" action="/admin/users" style="display:flex;gap:6px;padding-bottom:8px">
        <input type="hidden" name="type" value="{{ $current }}">
        <input type="search" name="q" value="{{ $q ?? '' }}" placeholder="Search name, email, phone or #ID" style="padding:7px 10px;font-size:13px;border:1px solid var(--border);border-radius:8px;min-width:260px">
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        @if(($q ?? '') !== '')<a href="/admin/users?type={{ $current }}" class="btn btn-secondary btn-sm">Clear</a>@endif
    </form>
</div>
<p class="text-secondary text-sm" style="margin:0 0 12px">Newest first. Phone is shown as one clean international number (click to open WhatsApp); hover for what was typed in eZee. Booking sites give a relay address instead of the guest's email, shown as "via Booking.com".@isset($reach) <b>{{ number_format($reach['phone']) }}</b> guests have a usable phone, <b>{{ number_format($reach['email']) }}</b> a direct email.@endisset</p>

<div class="card">
    <div class="table-wrap">
        @if($users->isEmpty())
            <div class="empty-state">
                <p>No guests found</p>
                <small>{{ ($q ?? '') !== '' ? 'Nothing matches that search.' : 'Try another tab.' }}</small>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Bookings</th>
                        <th>Last stay</th>
                        <th>Type</th>
                        <th>Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    @php
                        $isWeb = $user->password !== null || $user->provider !== null;
                        $full  = trim(preg_replace('/^[.\s]+/', '', trim($user->name . ' ' . $user->last_name)));
                        // Names typed in ALL CAPS or all lower case read better in title case; the stored name is not changed.
                        if ($full !== '' && (mb_strtoupper($full) === $full || mb_strtolower($full) === $full)) { $full = mb_convert_case(mb_strtolower($full), MB_CASE_TITLE); }
                        $relay = \App\Support\Guests::isRelayEmail($user->email);
                        $via   = $relay ? (str_contains($user->email, 'booking.com') ? 'Booking.com' : (str_contains($user->email, 'agoda') ? 'Agoda' : (str_contains($user->email, 'expedia') ? 'Expedia' : 'Trip.com'))) : null;
                    @endphp
                    <tr>
                        <td class="mono">#{{ $user->id }}</td>
                        <td><span class="font-600">{{ $full ?: '—' }}</span>@if($user->bookings_count > 50) <span class="badge badge-orange" title="One guest record used by many bookings: the name on those bookings may not be this person">shared record</span>@endif</td>
                        <td class="text-secondary">@if($relay)<span title="{{ $user->email }}">via {{ $via }} <span class="text-sm">(no direct email)</span></span>@else{{ $user->email ?: '—' }}@endif</td>
                        <td>@if($user->phone_e164)<a href="https://wa.me/{{ ltrim($user->phone_e164, '+') }}" target="_blank" rel="noopener" title="As typed in eZee: {{ $user->phone }}">{{ $user->phone_e164 }}</a>@elseif($user->phone)<span title="Could not be read as one clear number">{{ $user->phone }}</span> <span class="badge badge-gray">check</span>@else—@endif</td>
                        <td>{{ $user->bookings_count ?: '—' }}</td>
                        <td class="text-secondary text-sm">{{ $user->last_stay ?: '—' }}</td>
                        <td>@if($isWeb)<span class="badge badge-teal">Website</span>@else<span class="badge badge-gray">Booking</span>@endif @if($user->status != 1)<span class="badge badge-red">Inactive</span>@endif</td>
                        <td class="text-secondary text-sm">{{ $user->created_at ? $user->created_at->format('d M Y') : '—' }}</td>
                        <td>
                            <div class="actions">
                                <a href="/admin/users/{{ $user->id }}" class="btn btn-secondary btn-sm">View</a>
                                <a href="/admin/users/{{ $user->id }}/edit" class="btn btn-secondary btn-sm">Edit</a>
                                @if(!$user->bookings_count)
                                <form action="/admin/users/{{ $user->id }}" method="POST" onsubmit="return confirm('Delete this guest? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    @if($users->lastPage() > 1)
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
        <span class="text-sm text-secondary">Showing {{ number_format($users->firstItem()) }}–{{ number_format($users->lastItem()) }} of {{ number_format($users->total()) }}</span>
        <div style="display:flex;gap:6px">
            @if(!$users->onFirstPage())<a href="{{ $users->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Prev</a>@endif
            @if($users->hasMorePages())<a href="{{ $users->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>@endif
        </div>
    </div>
    @endif
</div>

@endsection
