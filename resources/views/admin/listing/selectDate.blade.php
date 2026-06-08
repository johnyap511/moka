@extends('admin.layout')
@section('page-title', 'Select Month')

@section('content')
<div class="page-header">
    <div>
        <h1>Listing Approval</h1>
        <p>Select a month to view utility charges</p>
    </div>
</div>

<div class="card" style="max-width:400px;">
    <div class="card-body">
        <form method="POST" action="{{ route('approval.month') }}">
            @csrf
            <div style="display:flex;flex-direction:column;gap:16px;">
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:6px;">Select Month</label>
                    <input type="month" name="date" class="form-input" value="{{ date('Y-m') }}" required style="width:100%">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">View Approval</button>
            </div>
        </form>
    </div>
</div>
@endsection
