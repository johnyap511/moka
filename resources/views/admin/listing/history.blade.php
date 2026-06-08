@extends('admin.layout')
@section('page-title', 'Historical API')

@section('content')
<div class="page-header">
    <div>
        <h1>Historical API</h1>
        <p>Fetch and import historical booking data from EZEE</p>
    </div>
</div>

<div class="card" style="max-width:600px">
    <div class="card-header"><h2>Fetch Historical Bookings</h2></div>
    <div class="card-body">
        <form method="POST" action="/admin/booking/historical/api">
            @csrf
            <div class="form-group">
                <label class="form-label">Date From</label>
                <input type="date" name="from_date" class="form-input" required value="{{ old('from_date', date('Y-m-01')) }}">
            </div>
            <div class="form-group">
                <label class="form-label">Date To</label>
                <input type="date" name="to_date" class="form-input" required value="{{ old('to_date', date('Y-m-d')) }}">
            </div>
            <button type="submit" class="btn btn-primary">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Fetch Bookings
            </button>
        </form>
    </div>
</div>

@if(session('api_result'))
<div class="card" style="margin-top:20px">
    <div class="card-header"><h2>API Result</h2></div>
    <div class="card-body">
        <pre style="background:var(--bg);border-radius:8px;padding:16px;font-size:12px;overflow-x:auto">{{ json_encode(session('api_result'), JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
@endif
@endsection
