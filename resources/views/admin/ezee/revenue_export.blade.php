@extends('admin.layout')
@section('title', 'Revenue Export (EZEE format)')
@section('page-title', 'Revenue Export (EZEE format)')

@section('content')
<div class="page-header">
    <div>
        <h1>Revenue Export (EZEE format)</h1>
        <p>One line per reservation and room, for the nights inside the calendar month, in the same shape as EZEE's Detail Revenue Report.</p>
    </div>
</div>

<div class="card" style="max-width:760px">
    <div class="card-body">
        <form method="post" action="{{ route('admin.ezee.revenue-export.download') }}" enctype="multipart/form-data" style="display:grid;gap:16px">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <label style="display:grid;gap:6px;font-size:13px">
                    <span>Month</span>
                    <input type="month" name="month" value="{{ $month }}" required style="padding:8px;font-size:14px">
                </label>
                <label style="display:grid;gap:6px;font-size:13px">
                    <span>Property</span>
                    <select name="hotel" style="padding:8px;font-size:14px">
                        <option value="">All five properties</option>
                        @foreach($hotels as $code => $name)
                            <option value="{{ $code }}">{{ $name }} ({{ $code }})</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <label style="display:grid;gap:6px;font-size:13px">
                <span>EZEE report files (optional, CSV): the Detail Revenue Report and the Daily Extra Charge report, one of each per property</span>
                <input type="file" name="ezee_files[]" accept=".csv,text/csv" multiple style="font-size:13px">
                <span style="color:var(--text-secondary);font-size:12px">With the revenue reports attached, each line gains EZEE's total, commission and deposit, the difference, and the reason. With the extra-charge reports attached as well, cleaning fees are compared, deposits separated, and late check-out, early check-in, extra cleaning, utilities and other charges land in the company column before the difference is taken. Lines on EZEE's files with no MOKA counterpart are appended.</span>
            </label>
            <div>
                <button type="submit" class="btn btn-primary">Download CSV</button>
            </div>
        </form>
    </div>
</div>

<div class="card" style="max-width:760px;margin-top:16px">
    <div class="card-body" style="font-size:13px;line-height:1.6">
        <b>How the figures are cut, to match EZEE</b>
        <ul style="margin:8px 0 0 18px;padding:0">
            <li>Room charges and SST: the nights inside the month at the stay's average nightly rate (exclusive of tax; SST 8% on top).</li>
            <li>Cleaning fee: in the arrival month, once.</li>
            <li>OTA fee: the channel fee for the nights inside the month, the same rule as the owner statement and the owner portal. EZEE books the whole fee in the departure month; that figure is shown in its own EZEE column and is not part of the difference.</li>
            <li>Damage deposit: not held in MOKA; EZEE's deposit is shown separately and excluded from the difference.</li>
            <li>Extras posted in EZEE after booking (utilities, late cleaning, "Other"): company revenue, shown in their own column when EZEE's file is attached.</li>
            <li>Extra rooms: listed with status "Extra room (company)". Reservations marked "No unit" and bookings entered by hand ("MOKA only") are listed too, so nothing is hidden.</li>
        </ul>
    </div>
</div>
@endsection
