@extends('admin.layout')
@section('title', 'Newsletter Lists')
@section('page-title', 'Newsletter Lists')

@push('styles')
<style>
.nl-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:16px}
.nl-card{padding:20px}.nl-card h2{margin:0 0 4px;font-size:16px}.nl-num{font-size:30px;font-weight:700;color:#111827;line-height:1.1;margin:10px 0 2px}
.nl-sub{font-size:13px;color:#6b7280;margin:0 0 14px;line-height:1.45}
.nl-rules{padding:18px 20px}.nl-rules h2{margin:0 0 8px;font-size:15px}.nl-rules ol{margin:0;padding-left:20px;font-size:14px;line-height:1.6;color:#374151}
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>Newsletter Lists</h1>
        <p>The only two lists that go into the email service. Download a fresh copy before each newsletter.</p>
    </div>
</div>

<div class="nl-grid">
    <div class="card nl-card">
        <h2>Owners</h2>
        <div class="nl-num">{{ number_format($owners) }}</div>
        <p class="nl-sub">owners with a live unit, {{ number_format($ownerUnits) }} units between them. One row per email address, with their buildings.
            @if($skipped['no_email'] || $skipped['ours'])<br>Left out: {{ $skipped['no_email'] }} with no usable email, {{ $skipped['ours'] }} of our own addresses.@endif</p>
        <a class="btn btn-primary btn-sm" href="/admin/sales/lists/owners">Download owners (Excel)</a>
    </div>
    <div class="card nl-card">
        <h2>Guests</h2>
        <div class="nl-num">{{ number_format($guests) }}</div>
        <p class="nl-sub">guests who ticked "Send me MOKA offers and news" on the deposit refund form. {{ number_format($guestsMonth) }} joined in the last 30 days. Comes with their building and last check-out.</p>
        @if(admin_can('refunds.view'))
            <a class="btn btn-primary btn-sm" href="{{ route('admin.refunds.contacts') }}">Download guests (Excel)</a>
        @else
            <span class="nl-sub">Ask Finance for this list: it is kept with the deposit refunds.</span>
        @endif
    </div>
</div>

<div class="card nl-rules">
    <h2>Rules, so our emails stay out of spam</h2>
    <ol>
        <li>Send only to these two lists. Older guest emails and website sign-ups from 2021 have no consent record.</li>
        <li>Send from the email service, never from a staff mailbox.</li>
        <li>Load the whole file each time. The service remembers who unsubscribed and will not email them again.</li>
        <li>Never remove the unsubscribe link, and never add people by hand who did not ask.</li>
    </ol>
</div>
@endsection
