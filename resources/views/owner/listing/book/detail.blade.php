@extends('owner.layout')

@section('title', 'Booking Detail')
@section('page-title', 'Booking Detail')

@section('content')
<div class="page-header">
    <div>
        <h1>Booking #{{ $book->id ?? '' }}</h1>
        <p>Full booking information.</p>
    </div>
    <a href="javascript:history.back()" class="btn btn-secondary">Back</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    {{-- Booking Info --}}
    <div class="card">
        <div class="card-header">
            <h2>Booking Details</h2>
            @php
                $statusLabels = [0=>'Cancelled',1=>'New',3=>'Pending',5=>'Confirmed',7=>'Checked In',9=>'Completed'];
                $statusClasses = [0=>'badge-red',1=>'badge-gray',3=>'badge-orange',5=>'badge-green',7=>'badge-blue',9=>'badge-gray'];
                $status = $book->status ?? 0;
            @endphp
            <span class="badge {{ $statusClasses[$status] ?? 'badge-gray' }}">{{ $statusLabels[$status] ?? 'Unknown' }}</span>
        </div>
        <div class="card-body">
            <table style="width:100%">
                <tbody>
                    <tr>
                        <td style="padding:8px 0;color:var(--text-secondary);font-size:13px;width:45%">Check-in</td>
                        <td style="padding:8px 0;font-weight:500">{{ $book->check_in ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:var(--text-secondary);font-size:13px">Check-out</td>
                        <td style="padding:8px 0;font-weight:500">{{ $book->check_out ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:var(--text-secondary);font-size:13px">Nights</td>
                        <td style="padding:8px 0">{{ $book->nights ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:var(--text-secondary);font-size:13px">Guests</td>
                        <td style="padding:8px 0">{{ ($book->adult ?? 0) }} adults, {{ ($book->infant ?? 0) }} children</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:var(--text-secondary);font-size:13px">Price / Night</td>
                        <td style="padding:8px 0">RM {{ number_format($book->price_night ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:var(--text-secondary);font-size:13px">Cleaning Fee</td>
                        <td style="padding:8px 0">RM {{ number_format($book->cleaning_fee ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:var(--text-secondary);font-size:13px">SST</td>
                        <td style="padding:8px 0">RM {{ number_format($book->sst ?? 0, 2) }}</td>
                    </tr>
                    <tr style="border-top:1px solid var(--border)">
                        <td style="padding:10px 0;font-weight:600">Total Amount</td>
                        <td style="padding:10px 0;font-weight:700;font-size:16px;color:var(--teal)">RM {{ number_format($book->price ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:var(--text-secondary);font-size:13px">Source</td>
                        <td style="padding:8px 0">
                            @if($book->source)
                                <span class="badge badge-blue">{{ $book->source }}</span>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:20px">

        {{-- Listing Info --}}
        <div class="card">
            <div class="card-header">
                <h2>Property</h2>
            </div>
            <div class="card-body">
                <div class="font-600" style="font-size:15px;margin-bottom:4px">{{ $listing->title ?? $listing->name ?? '-' }}</div>
                <div class="text-secondary" style="font-size:13px;margin-bottom:12px">{{ $listing->address ?? '-' }}</div>
                @if($owner)
                <div style="font-size:13px">
                    <span class="text-secondary">Owner: </span>
                    {{ $owner->name ?? '' }} {{ $owner->last_name ?? '' }}
                </div>
                @endif
            </div>
        </div>

        {{-- Guest Info --}}
        <div class="card">
            <div class="card-header">
                <h2>Guest Information</h2>
            </div>
            <div class="card-body">
                @if($user)
                    <div class="font-600" style="font-size:15px;margin-bottom:4px">{{ $user->name ?? '' }} {{ $user->last_name ?? '' }}</div>
                    <div class="text-secondary" style="font-size:13px;margin-bottom:6px">{{ $user->email ?? '-' }}</div>
                    @if($user->phone ?? null)
                        <div style="font-size:13px"><span class="text-secondary">Phone: </span>{{ $user->phone }}</div>
                    @endif
                @elseif(isset($ezeeBook) && $ezeeBook)
                    <div class="font-600" style="font-size:15px;margin-bottom:4px">{{ trim(($ezeeBook->FirstName ?? '') . ' ' . ($ezeeBook->LastName ?? '')) }}</div>
                    @if($ezeeBook->Email)
                    <div class="text-secondary" style="font-size:13px;margin-bottom:4px">{{ $ezeeBook->Email }}</div>
                    @endif
                    @if($ezeeBook->Mobile)
                    <div style="font-size:13px;margin-bottom:4px"><span class="text-secondary">Phone: </span>{{ $ezeeBook->Mobile }}</div>
                    @endif
                    @if($ezeeBook->Country)
                    <div style="font-size:13px"><span class="text-secondary">Country: </span>{{ $ezeeBook->Country }}</div>
                    @endif
                    @if($ezeeBook->SubBookingId)
                    <div style="font-size:12px;font-family:monospace;margin-top:6px;color:var(--teal)">{{ $ezeeBook->SubBookingId }}{{ $ezeeBook->folio_no ? ' · ' . $ezeeBook->folio_no : '' }}</div>
                    @endif
                @else
                    <div class="text-secondary" style="font-size:13px">Guest information not available.</div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
