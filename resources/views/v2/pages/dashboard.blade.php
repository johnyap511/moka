@extends('v2.partial.layout')
@section('title', 'Owner Dashboard — MOKA')

@section('content')
<section style="padding:100px 20px 60px;max-width:1200px;margin:0 auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:40px;flex-wrap:wrap;gap:16px;">
        <div>
            <h1 style="font-family:'Playfair Display',serif;font-size:2.2rem;color:#003d3c;margin:0;">
                Welcome, {{ auth()->user()->name }}
            </h1>
            <p style="color:#6b7280;margin:6px 0 0;">Your MOKA owner dashboard</p>
        </div>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit" style="padding:10px 24px;border:2px solid #003d3c;background:transparent;color:#003d3c;border-radius:10px;font-weight:600;cursor:pointer;">
                Sign Out
            </button>
        </form>
    </div>

    {{-- Stats --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:48px;">
        @php
            $stats = [
                ['label'=>'Active Properties','value'=>$listings->count(),'icon'=>'🏠'],
                ['label'=>'Avg. Rating','value'=>number_format($listings->avg('rating'),1),'icon'=>'⭐'],
                ['label'=>'Total Reviews','value'=>$listings->sum('review_count'),'icon'=>'💬'],
                ['label'=>'Revenue This Month','value'=>'RM '.number_format($listings->sum('price_per_night')*22),'icon'=>'💰'],
            ];
        @endphp
        @foreach($stats as $s)
        <div style="background:#fff;border-radius:16px;padding:28px;box-shadow:0 4px 20px rgba(0,0,0,.08);">
            <div style="font-size:2rem;margin-bottom:8px;">{{ $s['icon'] }}</div>
            <div style="font-size:1.8rem;font-weight:700;color:#003d3c;">{{ $s['value'] }}</div>
            <div style="font-size:.9rem;color:#6b7280;margin-top:4px;">{{ $s['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Quick nav --}}
    <div style="display:flex;gap:12px;margin-bottom:40px;flex-wrap:wrap;">
        @foreach([['Bookings','/user/bookings'],['Revenue','/user/revenue'],['Calendar','/user/calendar']] as [$label,$url])
        <a href="{{ $url }}" style="padding:10px 24px;background:#003d3c;color:#fff;text-decoration:none;border-radius:10px;font-weight:600;font-size:.9rem;">
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- Listings table --}}
    <h2 style="font-family:'Playfair Display',serif;font-size:1.5rem;color:#003d3c;margin-bottom:20px;">Your Properties</h2>
    <div style="background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.08);overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f9fafb;">
                    <th style="padding:16px 20px;text-align:left;font-size:.85rem;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Property</th>
                    <th style="padding:16px 20px;text-align:left;font-size:.85rem;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Zone</th>
                    <th style="padding:16px 20px;text-align:right;font-size:.85rem;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Rate/Night</th>
                    <th style="padding:16px 20px;text-align:right;font-size:.85rem;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Rating</th>
                    <th style="padding:16px 20px;text-align:right;font-size:.85rem;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Reviews</th>
                </tr>
            </thead>
            <tbody>
                @foreach($listings as $listing)
                <tr style="border-top:1px solid #f3f4f6;">
                    <td style="padding:16px 20px;">
                        <a href="/listing/{{ $listing->slug }}" style="font-weight:600;color:#003d3c;text-decoration:none;">
                            {{ $listing->title }}
                        </a>
                        <div style="font-size:.8rem;color:#9ca3af;margin-top:2px;">{{ $listing->bedrooms }}bd · {{ $listing->bathrooms }}ba · {{ $listing->max_guests }} guests</div>
                    </td>
                    <td style="padding:16px 20px;color:#374151;">{{ $listing->zone }}</td>
                    <td style="padding:16px 20px;text-align:right;font-weight:600;color:#003d3c;">RM {{ number_format($listing->price_per_night) }}</td>
                    <td style="padding:16px 20px;text-align:right;">
                        <span style="background:#fef3c7;color:#d97706;padding:4px 10px;border-radius:20px;font-size:.85rem;font-weight:600;">
                            {{ $listing->rating }}
                        </span>
                    </td>
                    <td style="padding:16px 20px;text-align:right;color:#6b7280;">{{ $listing->review_count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection
