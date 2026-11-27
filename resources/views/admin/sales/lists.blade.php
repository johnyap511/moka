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
        <p>One owner list and one guest list. These are the only two that go into the email service. Download a fresh copy before each newsletter.</p>
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
        <p class="nl-sub">guests who asked for our news, in one list: {{ number_format($fromRefund) }} ticked "Send me MOKA offers and news" on the deposit refund form, {{ number_format($fromWebsite) }} signed up on the website. {{ number_format($guestsMonth) }} joined in the last 30 days. Each row shows where the guest joined from, with their building and last check-out where we know it.</p>
        <a class="btn btn-primary btn-sm" href="/admin/sales/lists/guests">Download guests (Excel)</a>
    </div>
</div>

<div class="card nl-rules">
    <h2>Rules, so our emails stay out of spam</h2>
    <ol>
        <li>Everyone belongs to one of these two lists. A new guest joins by ticking the offers box on the refund form or signing up on the website; nobody is added by hand.</li>
        <li>Send from the email service, never from a staff mailbox.</li>
        <li>Load the whole file each time. The service remembers who unsubscribed and will not email them again.</li>
        <li>Never remove the unsubscribe link.</li>
        <li>Guests from the old Google refund form (2022 to 2026) were loaded once, from files kept by Sam. They are not in this download.</li>
    </ol>
</div>
@endsection
