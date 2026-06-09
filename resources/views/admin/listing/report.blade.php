@extends('admin.layout')
@section('page-title', 'Listing Report')

@push('styles')
<style>
.report-filter-wrap { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.report-table th { font-size:11.5px; }
.utility-section label { font-size:12px; font-weight:600; color:var(--text-secondary); }
.utility-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; }
.utility-item { display:flex; flex-direction:column; gap:6px; }
.utility-item input[type=number] { width:100%; }
.badge-channel { background:#eff6ff; color:#1d4ed8; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:500; }
#preview-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:300; align-items:center; justify-content:center; }
#preview-modal.open { display:flex; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>Listing Report</h1>
        <p>Monthly booking summary &amp; utility charges per listing</p>
    </div>
    @if($listing)
    <div style="display:flex;gap:10px;align-items:center">
        <button type="button" class="btn btn-secondary" onclick="submitAs('excel')">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export Excel
        </button>
        <button type="button" class="btn btn-primary" id="btn-preview" onclick="previewReport()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            Preview Report
        </button>
    </div>
    @endif
</div>

{{-- Filter Bar --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <form method="GET" action="/admin/listing/chart/report" class="report-filter-wrap" id="filter-form">
            <div style="display:flex;flex-direction:column;gap:4px;">
                <label style="font-size:11px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;">Listing</label>
                <select name="listing_id" class="form-input" style="width:220px" onchange="this.form.submit()">
                    @foreach($allListings as $l)
                        <option value="{{ $l->id }}" {{ $l->id == $id ? 'selected' : '' }}>{{ $l->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px;">
                <label style="font-size:11px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;">Month</label>
                <input type="month" name="date" class="form-input" style="width:180px"
                    value="{{ $selDate instanceof \Carbon\Carbon ? $selDate->format('Y-m') : date_format($selDate,'Y-m') }}"
                    onchange="this.form.submit()">
            </div>
            <div style="display:flex;align-items:flex-end;">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>
</div>

@if($listing)
{{-- Bookings Table --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h2>{{ $listing->name }} — {{ $selDate instanceof \Carbon\Carbon ? $selDate->format('F Y') : date_format($selDate,'F Y') }}</h2>
    </div>
    <div class="table-wrap" id="bookings-wrap">
        <table style="width:100%;border-collapse:collapse;" id="bookings-table">
            <thead>
                <tr>
                    @foreach(['#','Guest','Channel','Check In','Check Out','Nights','Price','Status'] as $h)
                    <th style="padding:10px 14px;text-align:left;font-size:11.5px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border);white-space:nowrap">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($books as $i => $b)
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:10px 14px;font-size:13px">{{ $i + 1 }}</td>
                    <td style="padding:10px 14px;font-size:13px">{{ $b->name ?? '—' }}</td>
                    <td style="padding:10px 14px;font-size:13px"><span class="badge-channel">{{ $b->channel ?? 'Direct' }}</span></td>
                    <td style="padding:10px 14px;font-size:13px">{{ $b->check_in }}</td>
                    <td style="padding:10px 14px;font-size:13px">{{ $b->check_out }}</td>
                    <td style="padding:10px 14px;font-size:13px">{{ \Carbon\Carbon::parse($b->check_in)->diffInDays(\Carbon\Carbon::parse($b->check_out)) }}</td>
                    <td style="padding:10px 14px;font-size:13px">RM {{ number_format($b->price ?? 0, 2) }}</td>
                    <td style="padding:10px 14px;font-size:13px">{{ $b->status }}</td>
                </tr>
                @empty
                <tr><td colspan="8" style="padding:20px;text-align:center;color:var(--text-secondary);">No bookings found for this month.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Utility Charges Form --}}
<div class="card">
    <div class="card-header">
        <h2>Utility Charges</h2>
        <p style="font-size:13px;color:var(--text-secondary);margin:0;">Enter charges then preview or export the report.</p>
    </div>
    <div class="card-body">
        <form method="POST" action="/admin/listing/report/{{ $id }}" id="export-form" target="_blank">
            @csrf
            <input type="hidden" name="date" value="{{ $selDate instanceof \Carbon\Carbon ? $selDate->format('Y-m') : date_format($selDate,'Y-m') }}">

            <div class="utility-grid">
                <div class="utility-item">
                    <label>Water</label>
                    <input type="text" name="water" class="form-input" placeholder="Description" value="{{ old('water') }}">
                    <input type="number" step="0.01" name="water_amount" class="form-input" placeholder="Amount (RM)" value="{{ old('water_amount') }}">
                </div>
                <div class="utility-item">
                    <label>Internet</label>
                    <input type="text" name="internet" class="form-input" placeholder="Description" value="{{ old('internet') }}">
                    <input type="number" step="0.01" name="internet_amount" class="form-input" placeholder="Amount (RM)" value="{{ old('internet_amount') }}">
                </div>
                <div class="utility-item">
                    <label>Electricity</label>
                    <input type="text" name="electricity" class="form-input" placeholder="Description" value="{{ old('electricity') }}">
                    <input type="number" step="0.01" name="electricity_amount" class="form-input" placeholder="Amount (RM)" value="{{ old('electricity_amount') }}">
                </div>
                <div class="utility-item">
                    <label>MF + SF</label>
                    <input type="text" name="mfsf" class="form-input" placeholder="Description" value="{{ old('mfsf') }}">
                    <input type="number" step="0.01" name="mfsf_amount" class="form-input" placeholder="Amount (RM)" value="{{ old('mfsf_amount') }}">
                </div>
                <div class="utility-item">
                    <label>Adjustment 1</label>
                    <input type="text" name="adjustment1" class="form-input" placeholder="Label" value="{{ old('adjustment1') }}">
                    <input type="text" name="adjustment1_text" class="form-input" placeholder="Description" value="{{ old('adjustment1_text') }}">
                    <input type="number" step="0.01" name="adjustment1_amount" class="form-input" placeholder="Amount (RM)" value="{{ old('adjustment1_amount') }}">
                </div>
                <div class="utility-item">
                    <label>Adjustment 2</label>
                    <input type="text" name="adjustment2" class="form-input" placeholder="Label" value="{{ old('adjustment2') }}">
                    <input type="text" name="adjustment2_text" class="form-input" placeholder="Description" value="{{ old('adjustment2_text') }}">
                    <input type="number" step="0.01" name="adjustment2_amount" class="form-input" placeholder="Amount (RM)" value="{{ old('adjustment2_amount') }}">
                </div>
                <div class="utility-item">
                    <label>Adjustment 3</label>
                    <input type="text" name="adjustment3" class="form-input" placeholder="Label" value="{{ old('adjustment3') }}">
                    <input type="text" name="adjustment3_text" class="form-input" placeholder="Description" value="{{ old('adjustment3_text') }}">
                    <input type="number" step="0.01" name="adjustment3_amount" class="form-input" placeholder="Amount (RM)" value="{{ old('adjustment3_amount') }}">
                </div>
            </div>

            <div style="margin-top:24px;display:flex;gap:12px;">
                <button type="button" class="btn btn-secondary" onclick="submitAs('excel')">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Excel
                </button>
                <button type="button" class="btn btn-primary" id="btn-preview-bottom" onclick="previewReport()">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Preview Report
                </button>
            </div>
        </form>
    </div>
</div>
@else
<div class="card">
    <div class="card-body" style="text-align:center;padding:40px;color:var(--text-secondary);">
        No active listings found.
    </div>
</div>
@endif

{{-- Preview Modal --}}
<div id="preview-modal">
    <div style="background:#fff;border-radius:16px;width:90vw;max-width:1100px;height:85vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);flex-shrink:0">
            <h3 style="font-size:15px;font-weight:600">Report Preview</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closePreview()">Close</button>
        </div>
        <iframe id="preview-frame" style="flex:1;border:none;border-radius:0 0 16px 16px;width:100%"></iframe>
    </div>
</div>

@endsection

@push('scripts')
<script>
var listingId = '{{ $id ?? "" }}';
var csrfToken = '{{ csrf_token() }}';

function getFormData() {
    var form = document.getElementById('export-form');
    var data = new FormData(form);
    return data;
}

function previewReport() {
    var btn1 = document.getElementById('btn-preview');
    var btn2 = document.getElementById('btn-preview-bottom');
    if (btn1) { btn1.disabled = true; btn1.textContent = 'Loading…'; }
    if (btn2) { btn2.disabled = true; btn2.textContent = 'Loading…'; }

    var data = getFormData();
    data.append('preview', 'preview');

    fetch('/admin/listings/reports/' + listingId, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: data
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (btn1) { btn1.disabled = false; btn1.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg> Preview Report'; }
        if (btn2) { btn2.disabled = false; btn2.innerHTML = btn1 ? btn1.innerHTML : 'Preview Report'; }
        if (!res.ok) { alert(res.message || 'Error generating preview.'); return; }
        document.getElementById('preview-frame').srcdoc = res.data;
        document.getElementById('preview-modal').classList.add('open');
    })
    .catch(function(err) {
        if (btn1) { btn1.disabled = false; btn1.textContent = 'Preview Report'; }
        if (btn2) { btn2.disabled = false; btn2.textContent = 'Preview Report'; }
        alert('Error: ' + err.message);
    });
}

function closePreview() {
    document.getElementById('preview-modal').classList.remove('open');
}

function submitAs(type) {
    var form = document.getElementById('export-form');
    form.action = '/admin/listing/report/' + listingId;
    form.target = '_blank';
    form.submit();
}

document.getElementById('preview-modal').addEventListener('click', function(e) {
    if (e.target === this) closePreview();
});
</script>
@endpush
