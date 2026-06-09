@extends('admin.layout')
@section('page-title', 'Listing Approval')

@push('styles')
<style>
.date-picker-wrap { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.utility-table th { font-size:11.5px; }
.status-sent { background:#d1fae5; color:#065f46; padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:500; }
.status-pending { background:#fff7ed; color:#c2410c; padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:500; }
.cb-row { accent-color: var(--teal); width:16px; height:16px; cursor:pointer; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>Listing Approval</h1>
        <p>Utility charges by listing per month</p>
    </div>
    <button type="button" id="btn-send-mail" class="btn btn-danger" onclick="sendMails()">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        Send Mail
    </button>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <form method="POST" action="{{ route('approval.month') }}" id="filter-form" class="date-picker-wrap">
            @csrf
            @method('POST')
            <input type="month" name="date" class="form-input" style="width:200px"
                value="{{ request('date', date('Y-m')) }}"
                onchange="this.form.submit()">
            <button type="submit" class="btn btn-primary">Update</button>
            <button type="button" class="btn btn-secondary" onclick="selectAll()">Select All</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>{{ \Carbon\Carbon::parse(request('date', date('Y-m')))->format('F Y') }} — Utility Charges</h2>
        <span class="badge badge-blue">{{ count($utilities) }} listings</span>
    </div>
    <div class="table-wrap">
        <table class="utility-table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    @foreach(['#','Listing Name','Month','Water','Internet','Electricity','MF + SF','Adjustment1','Adjustment2','Adjustment3','Action'] as $h)
                    <th style="padding:10px 14px;text-align:left;font-size:11.5px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--border);white-space:nowrap">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @forelse($utilities as $utility)
                    @if(!empty($utility->water) || !empty($utility->internet) || !empty($utility->electricity) || !empty($utility->mfsf) || !empty($utility->adjustment1) || !empty($utility->adjustment2) || !empty($utility->adjustment3))
                    <tr id="tr{{ $utility->id }}" style="border-bottom:1px solid #f0f0f2;transition:background .1s" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                        <td style="padding:12px 14px;font-size:13.5px;vertical-align:middle">
                            @if($utility->status == 0)
                            <input type="checkbox" class="cb-row report tb-check"
                                id="cb{{ $utility->id }}"
                                value="{{ $utility->id }}"
                                data-listing-id="{{ $utility->listing_id }}"
                                data-listing-name="{{ $utility->listing_name }}"
                                data-date="{{ $utility->excel_date }}"
                                data-water="{{ $utility->water }}"
                                data-internet="{{ $utility->internet }}"
                                data-electricity="{{ $utility->electricity }}"
                                data-mfsf="{{ $utility->mfsf }}"
                                data-adj1="{{ $utility->adjustment1 }}"
                                data-adj2="{{ $utility->adjustment2 }}"
                                data-adj3="{{ $utility->adjustment3 }}">
                            @else
                            <span class="status-sent">Sent</span>
                            @endif
                        </td>
                        <td style="padding:12px 14px;font-size:13.5px;font-weight:500">{{ $utility->listing_name }}</td>
                        <td style="padding:12px 14px;font-size:13px;color:var(--text-secondary)">{{ $utility->excel_date }}</td>
                        <td style="padding:12px 14px;font-size:13px">{{ $utility->water ? 'RM '.number_format($utility->water,2) : '—' }}</td>
                        <td style="padding:12px 14px;font-size:13px">{{ $utility->internet ? 'RM '.number_format($utility->internet,2) : '—' }}</td>
                        <td style="padding:12px 14px;font-size:13px">{{ $utility->electricity ? 'RM '.number_format($utility->electricity,2) : '—' }}</td>
                        <td style="padding:12px 14px;font-size:13px">{{ $utility->mfsf ? 'RM '.number_format($utility->mfsf,2) : '—' }}</td>
                        <td style="padding:12px 14px;font-size:13px">{{ $utility->adjustment1 ? 'RM '.number_format($utility->adjustment1,2) : '—' }}</td>
                        <td style="padding:12px 14px;font-size:13px">{{ $utility->adjustment2 ? 'RM '.number_format($utility->adjustment2,2) : '—' }}</td>
                        <td style="padding:12px 14px;font-size:13px">{{ $utility->adjustment3 ? 'RM '.number_format($utility->adjustment3,2) : '—' }}</td>
                        <td style="padding:12px 14px;font-size:13px">
                            <div style="display:flex;gap:8px">
                                <button type="button" class="btn btn-secondary" style="padding:4px 10px;font-size:12px"
                                    onclick="editUtility({{ $utility->id }},'{{ addslashes($utility->listing_name) }}','{{ $utility->excel_date }}','{{ $utility->water }}','{{ $utility->internet }}','{{ $utility->electricity }}','{{ $utility->mfsf }}','{{ $utility->adjustment1 }}','{{ $utility->adjustment2 }}','{{ $utility->adjustment3 }}')">
                                    Edit
                                </button>
                                <button type="button" class="btn btn-primary" style="padding:4px 10px;font-size:12px" onclick="previewUtility({{ $utility->id }})">
                                    Preview
                                </button>
                            </div>
                        </td>
                    </tr>
                    @php $no++; @endphp
                    @endif
                @empty
                    <tr>
                        <td colspan="11" style="padding:60px;text-align:center;color:var(--text-secondary)">
                            No utility data found for this month.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Preview Modal --}}
<div id="preview-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:300;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;width:90vw;max-width:860px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.3)">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-bottom:1px solid #e5e7eb">
            <h2 style="font-size:16px;font-weight:600;margin:0">Report Preview</h2>
            <button onclick="closePreview()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#6b7280">&times;</button>
        </div>
        <div id="preview-body" style="overflow-y:auto;flex:1;padding:0">
            <div id="preview-loading" style="padding:60px;text-align:center;color:#9ca3af">Loading preview…</div>
            <iframe id="preview-frame" style="width:100%;height:70vh;border:none;display:none"></iframe>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:200;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:28px;width:560px;max-width:90vw;box-shadow:0 20px 60px rgba(0,0,0,.2)">
        <h2 style="font-size:17px;font-weight:600;margin-bottom:4px">Update Utility</h2>
        <p id="edit-listing-label" style="font-size:13px;color:var(--text-secondary);margin-bottom:20px"></p>
        <form method="POST" id="edit-form">
            @csrf
            @method('PUT')
            @php
            $utilOpts = ['' => 'Please Select', 'A' => 'Split by Profit %', 'B' => 'Split (Special)', 'C' => 'Host Pays All', 'D' => 'Owner Pays All', 'E' => 'Owner Pays All (Alt)'];
            @endphp
            @foreach([
                ['water',       'Water'],
                ['internet',    'Internet'],
                ['electricity', 'Electricity'],
                ['mfsf',        'MF + SF'],
            ] as [$field, $label])
            <div style="display:grid;grid-template-columns:130px 1fr 1fr;gap:12px;align-items:center;margin-bottom:10px">
                <label style="font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px">
                    <input type="checkbox" id="cb-{{ $field }}" onchange="toggleField('{{ $field }}')" style="accent-color:var(--teal)">
                    {{ $label }}
                </label>
                <select name="{{ $field }}_option" id="opt-{{ $field }}" class="form-input" style="font-size:12px" disabled>
                    @foreach($utilOpts as $v => $t)
                    <option value="{{ $v }}">{{ $t }}</option>
                    @endforeach
                </select>
                <input type="number" step="0.01" name="{{ $field }}" id="edit-{{ $field }}" class="form-input" placeholder="Amount (RM)" disabled>
            </div>
            @endforeach

            @foreach([1,2,3] as $n)
            <div style="display:grid;grid-template-columns:130px 1fr 1fr;gap:12px;align-items:center;margin-bottom:10px">
                <label style="font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px">
                    <input type="checkbox" id="cb-adj{{ $n }}" onchange="toggleField('adj{{ $n }}')" style="accent-color:var(--teal)">
                    Adjustment {{ $n }}
                </label>
                <input type="text" name="adjustment{{ $n }}_name" id="edit-adj{{ $n }}-name" class="form-input" placeholder="Label (e.g. Rental)" disabled style="font-size:12px">
                <input type="number" step="0.01" name="adjustment{{ $n }}" id="edit-adj{{ $n }}" class="form-input" placeholder="Amount (RM)" disabled>
            </div>
            @endforeach
            <div class="flex gap-2" style="margin-top:8px;justify-content:flex-end">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function selectAll() {
    document.querySelectorAll('.tb-check').forEach(cb => { cb.checked = true; });
}

function toggleField(field) {
    var cb = document.getElementById('cb-' + field);
    var isChecked = cb.checked;
    var amtEl = document.getElementById('edit-' + field);
    if (amtEl) amtEl.disabled = !isChecked;
    var optEl = document.getElementById('opt-' + field);
    if (optEl) optEl.disabled = !isChecked;
    var nameEl = document.getElementById('edit-' + field + '-name');
    if (nameEl) nameEl.disabled = !isChecked;
}

function setField(field, value) {
    var el = document.getElementById('edit-' + field);
    if (!el) return;
    el.value = value || '';
    var cb = document.getElementById('cb-' + field);
    if (cb) {
        cb.checked = value > 0;
        toggleField(field);
    }
}

function editUtility(id, name, date, water, internet, electricity, mfsf, adj1, adj2, adj3) {
    document.getElementById('edit-listing-label').textContent = name + ' · ' + date;
    document.getElementById('edit-form').action = '/admin/utility/update/' + id;

    setField('water',       water);
    setField('internet',    internet);
    setField('electricity', electricity);
    setField('mfsf',        mfsf);
    setField('adj1',        adj1);
    setField('adj2',        adj2);
    setField('adj3',        adj3);

    document.getElementById('edit-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('edit-modal').style.display = 'none';
}

function previewUtility(id) {
    document.getElementById('preview-modal').style.display = 'flex';
    document.getElementById('preview-loading').style.display = 'block';
    document.getElementById('preview-frame').style.display = 'none';

    fetch('/admin/import/pdf/approval', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ id: id, preview: 'preview' })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 200 && res.data) {
            var frame = document.getElementById('preview-frame');
            document.getElementById('preview-loading').style.display = 'none';
            frame.style.display = 'block';
            frame.srcdoc = res.data;
        } else {
            document.getElementById('preview-loading').textContent = 'Failed to load preview. ' + (res.message || '');
        }
    })
    .catch(() => {
        document.getElementById('preview-loading').textContent = 'Error loading preview.';
    });
}

function closePreview() {
    document.getElementById('preview-modal').style.display = 'none';
    document.getElementById('preview-frame').srcdoc = '';
}

function sendMails() {
    const checked = document.querySelectorAll('.tb-check:checked');
    if (checked.length === 0) {
        alert('Please select at least one listing to send mail.');
        return;
    }
    const ids = Array.from(checked).map(cb => cb.value);
    if (!confirm('Send utility report to ' + ids.length + ' owner(s)?')) return;

    fetch('/admin/send/approval', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ ids })
    }).then(r => r.json()).then(data => {
        alert(data.message || 'Mail sent successfully!');
        location.reload();
    }).catch(() => alert('Error sending mail.'));
}

document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endpush
