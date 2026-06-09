@extends('admin.layout')
@section('page-title', 'Monthly Report')

@push('styles')
<style>
.util-val { font-size:12.5px; color:var(--text); }
.util-empty { font-size:12px; color:#ccc; }
#edit-modal, #preview-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:300; align-items:center; justify-content:center; }
#edit-modal.open, #preview-modal.open { display:flex; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>Monthly Report</h1>
        <p>Utility charges &amp; report preview for all listings</p>
    </div>
    {{-- Month picker --}}
    <form method="GET" action="/admin/listing/chart/report" style="display:flex;align-items:center;gap:10px;">
        <input type="month" name="date" class="form-input" style="width:180px"
               value="{{ $selDate->format('Y-m') }}"
               onchange="this.form.submit()">
        <button type="submit" class="btn btn-secondary">Go</button>
    </form>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error" style="margin-bottom:16px">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-header">
        <h2>{{ $selDate->format('F Y') }} <span class="badge badge-blue">{{ count($allListings) }} listings</span></h2>
        <span style="font-size:12px;color:var(--text-secondary)">
            {{ $utilities->count() }} of {{ count($allListings) }} have utility data entered
        </span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;min-width:1000px">
            <thead>
                <tr>
                    @foreach(['#','Listing','Water','Internet','Electricity','MF + SF','Adjustment 1','Adjustment 2','Adjustment 3','Actions'] as $h)
                    <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border);white-space:nowrap;background:var(--bg)">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($allListings as $i => $l)
                @php $u = $utilities->get($l->id); @endphp
                <tr style="border-bottom:1px solid var(--border)" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                    <td style="padding:10px 14px;font-size:13px;color:var(--text-secondary)">{{ $i + 1 }}</td>
                    <td style="padding:10px 14px">
                        <div style="font-weight:500;font-size:13px">{{ $l->name }}</div>
                        @if($u)
                        <div style="font-size:11px;color:var(--text-secondary)">{{ \Carbon\Carbon::parse($u->excel_date)->format('M Y') }}</div>
                        @endif
                    </td>
                    <td style="padding:10px 14px">
                        @if($u && $u->water) <span class="util-val">{{ number_format($u->water, 2) }}</span>
                        @else <span class="util-empty">—</span> @endif
                    </td>
                    <td style="padding:10px 14px">
                        @if($u && $u->internet) <span class="util-val">{{ number_format($u->internet, 2) }}</span>
                        @else <span class="util-empty">—</span> @endif
                    </td>
                    <td style="padding:10px 14px">
                        @if($u && $u->electricity) <span class="util-val">{{ number_format($u->electricity, 2) }}</span>
                        @else <span class="util-empty">—</span> @endif
                    </td>
                    <td style="padding:10px 14px">
                        @if($u && $u->mfsf) <span class="util-val">{{ number_format($u->mfsf, 2) }}</span>
                        @else <span class="util-empty">—</span> @endif
                    </td>
                    <td style="padding:10px 14px">
                        @if($u && $u->adjustment1) <span class="util-val">{{ number_format($u->adjustment1, 2) }}</span>
                        @else <span class="util-empty">—</span> @endif
                    </td>
                    <td style="padding:10px 14px">
                        @if($u && $u->adjustment2) <span class="util-val">{{ number_format($u->adjustment2, 2) }}</span>
                        @else <span class="util-empty">—</span> @endif
                    </td>
                    <td style="padding:10px 14px">
                        @if($u && $u->adjustment3) <span class="util-val">{{ number_format($u->adjustment3, 2) }}</span>
                        @else <span class="util-empty">—</span> @endif
                    </td>
                    <td style="padding:10px 14px">
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            @if($u)
                            <button type="button" class="btn btn-primary btn-sm"
                                onclick="openPreview({{ $u->id }}, {{ $l->id }})">
                                Preview
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm"
                                onclick="openEdit({{ $l->id }}, '{{ addslashes($l->name) }}', {{ $u->id }})">
                                Edit
                            </button>
                            @else
                            <button type="button" class="btn btn-secondary btn-sm"
                                onclick="openEdit({{ $l->id }}, '{{ addslashes($l->name) }}', 0)">
                                + Add
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" style="padding:40px;text-align:center;color:var(--text-secondary)">No active listings found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Edit / Add Modal --}}
<div id="edit-modal">
    <div style="background:#fff;border-radius:16px;padding:28px;width:680px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.25)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
            <h3 style="font-size:16px;font-weight:600" id="edit-modal-title">Utility Charges</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeEdit()">Close</button>
        </div>
        <form method="POST" id="edit-form" action="/admin/import/approval">
            @csrf
            <input type="hidden" name="listing_id" id="ef-listing-id">
            <input type="hidden" name="date" value="{{ $selDate->format('Y-m-d') }}">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label class="form-label">Water (RM)</label>
                    <input type="number" step="0.01" name="water_amount" id="ef-water" class="form-input" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Internet (RM)</label>
                    <input type="number" step="0.01" name="internet_amount" id="ef-internet" class="form-input" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Electricity (RM)</label>
                    <input type="number" step="0.01" name="electricity_amount" id="ef-electricity" class="form-input" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">MF + SF (RM)</label>
                    <input type="number" step="0.01" name="mfsf_amount" id="ef-mfsf" class="form-input" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Adjustment 1 (RM)</label>
                    <input type="text" name="adjustment1_text" id="ef-adj1-label" class="form-input" placeholder="Label" style="margin-bottom:6px">
                    <input type="number" step="0.01" name="adjustment1_amount" id="ef-adj1" class="form-input" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Adjustment 2 (RM)</label>
                    <input type="text" name="adjustment2_text" id="ef-adj2-label" class="form-input" placeholder="Label" style="margin-bottom:6px">
                    <input type="number" step="0.01" name="adjustment2_amount" id="ef-adj2" class="form-input" placeholder="0.00">
                </div>
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Adjustment 3 (RM)</label>
                    <input type="text" name="adjustment3_text" id="ef-adj3-label" class="form-input" placeholder="Label" style="margin-bottom:6px">
                    <input type="number" step="0.01" name="adjustment3_amount" id="ef-adj3" class="form-input" placeholder="0.00">
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
                <button type="button" class="btn btn-secondary" onclick="closeEdit()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Preview Modal --}}
<div id="preview-modal">
    <div style="background:#fff;border-radius:16px;width:92vw;max-width:1100px;height:88vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.3);">
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
var csrfToken = '{{ csrf_token() }}';
var selDate   = '{{ $selDate->format("Y-m-d") }}';

// ---- Edit / Add modal ----
var utilData = @json($utilities->values()->keyBy('listing_id'));

function openEdit(listingId, listingName, utilId) {
    document.getElementById('edit-modal-title').textContent = listingName + ' — Utility Charges';
    document.getElementById('ef-listing-id').value = listingId;

    var u = utilData[listingId] || {};
    document.getElementById('ef-water').value       = u.water || '';
    document.getElementById('ef-internet').value    = u.internet || '';
    document.getElementById('ef-electricity').value = u.electricity || '';
    document.getElementById('ef-mfsf').value        = u.mfsf || '';
    document.getElementById('ef-adj1-label').value  = u.adjustment1_text || '';
    document.getElementById('ef-adj1').value        = u.adjustment1 || '';
    document.getElementById('ef-adj2-label').value  = u.adjustment2_text || '';
    document.getElementById('ef-adj2').value        = u.adjustment2 || '';
    document.getElementById('ef-adj3-label').value  = u.adjustment3_text || '';
    document.getElementById('ef-adj3').value        = u.adjustment3 || '';

    document.getElementById('edit-modal').classList.add('open');
}

function closeEdit() {
    document.getElementById('edit-modal').classList.remove('open');
}

// ---- Preview modal ----
function openPreview(utilId, listingId) {
    document.getElementById('preview-frame').srcdoc = '<p style="font-family:sans-serif;padding:40px;color:#666">Loading…</p>';
    document.getElementById('preview-modal').classList.add('open');

    fetch('/admin/import/pdf/approval', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ id: utilId, listing_id: listingId, date: selDate, preview: 'preview' })
    })
    .then(r => r.json())
    .then(function(res) {
        if (res.data && res.data !== 101) {
            document.getElementById('preview-frame').srcdoc = res.data;
        } else {
            document.getElementById('preview-frame').srcdoc = '<p style="font-family:sans-serif;padding:40px;color:#666">No bookings found for this period.</p>';
        }
    })
    .catch(function() {
        document.getElementById('preview-frame').srcdoc = '<p style="font-family:sans-serif;padding:40px;color:red">Error loading preview.</p>';
    });
}

function closePreview() {
    document.getElementById('preview-modal').classList.remove('open');
}

document.getElementById('edit-modal').addEventListener('click', function(e) { if (e.target===this) closeEdit(); });
document.getElementById('preview-modal').addEventListener('click', function(e) { if (e.target===this) closePreview(); });
</script>
@endpush
