@extends('admin.layout')
@section('page-title', 'Historical API')

@push('styles')
<style>
.badge-new      { background:#d1fae5;color:#065f46;padding:2px 9px;border-radius:20px;font-size:11.5px;font-weight:600; }
.badge-updated  { background:#fef3c7;color:#92400e;padding:2px 9px;border-radius:20px;font-size:11.5px;font-weight:600; }
.badge-unchanged{ background:#f3f4f6;color:#6b7280;padding:2px 9px;border-radius:20px;font-size:11.5px;font-weight:600; }
.log-row td { vertical-align:top; }
.changes-tag { display:inline-block;background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;padding:1px 6px;border-radius:4px;font-size:11px;margin:2px 2px 0 0; }
.detail-panel { display:none; background:#f9fafb; border-top:1px solid var(--border); }
.detail-panel.open { display:table-row; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>Historical API</h1>
        <p>Fetch and import historical booking data from EZEE</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error" style="margin-bottom:16px">{{ session('error') }}</div>
@endif

<div class="card" style="max-width:600px;margin-bottom:28px">
    <div class="card-header"><h2>Fetch Bookings</h2></div>
    <div style="padding:20px">
        <form method="POST" action="/admin/booking/historical/api" id="fetch-form">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px">
                <div class="form-group">
                    <label class="form-label">Date From</label>
                    <input type="date" name="from_date" class="form-input" required value="{{ old('from_date', date('Y-m-01')) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Date To</label>
                    <input type="date" name="to_date" class="form-input" required value="{{ old('to_date', date('Y-m-d')) }}">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" id="fetch-btn">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Fetch Bookings
            </button>
        </form>
    </div>
</div>

{{-- Sync History --}}
<div class="card">
    <div class="card-header">
        <h2>Sync History <span class="badge badge-blue">{{ count($logs) }}</span></h2>
        <span style="font-size:12px;color:var(--text-secondary)">Last 50 runs · click a row to see booking details</span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;min-width:700px">
            <thead>
                <tr>
                    <th style="padding:9px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);border-bottom:1px solid var(--border);background:var(--bg)">#</th>
                    <th style="padding:9px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);border-bottom:1px solid var(--border);background:var(--bg)">Date Range</th>
                    <th style="padding:9px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);border-bottom:1px solid var(--border);background:var(--bg);text-align:center">New</th>
                    <th style="padding:9px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);border-bottom:1px solid var(--border);background:var(--bg);text-align:center">Updated</th>
                    <th style="padding:9px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);border-bottom:1px solid var(--border);background:var(--bg);text-align:center">Unchanged</th>
                    <th style="padding:9px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);border-bottom:1px solid var(--border);background:var(--bg);text-align:center">Total</th>
                    <th style="padding:9px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);border-bottom:1px solid var(--border);background:var(--bg)">Duration</th>
                    <th style="padding:9px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);border-bottom:1px solid var(--border);background:var(--bg)">Run At</th>
                    <th style="padding:9px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);border-bottom:1px solid var(--border);background:var(--bg)">By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $i => $log)
                <tr class="log-row" style="border-bottom:1px solid var(--border);cursor:pointer"
                    onclick="toggleDetail({{ $log->id }})"
                    onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                    <td style="padding:10px 14px;font-size:12px;color:var(--text-secondary)">{{ $i+1 }}</td>
                    <td style="padding:10px 14px;font-size:13px;font-weight:500">{{ $log->from_date }} → {{ $log->to_date }}</td>
                    <td style="padding:10px 14px;text-align:center"><span class="badge-new">{{ $log->new_count }}</span></td>
                    <td style="padding:10px 14px;text-align:center"><span class="badge-updated">{{ $log->updated_count }}</span></td>
                    <td style="padding:10px 14px;text-align:center"><span class="badge-unchanged">{{ $log->unchanged_count }}</span></td>
                    <td style="padding:10px 14px;text-align:center;font-weight:600;font-size:13px">{{ $log->total_count }}</td>
                    <td style="padding:10px 14px;font-size:12px;color:var(--text-secondary)">{{ $log->duration_seconds }}s</td>
                    <td style="padding:10px 14px;font-size:12px;white-space:nowrap">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i') }}</td>
                    <td style="padding:10px 14px;font-size:12px">{{ $log->ranBy->name ?? 'System' }}</td>
                </tr>
                {{-- Expandable detail row --}}
                <tr id="detail-{{ $log->id }}" class="detail-panel">
                    <td colspan="9" style="padding:0">
                        @if($log->details && count($log->details))
                        <div style="padding:14px 20px">
                            <table style="width:100%;border-collapse:collapse;font-size:12.5px">
                                <thead>
                                    <tr style="background:#f3f4f6">
                                        <th style="padding:6px 10px;text-align:left;font-weight:600;color:var(--text-secondary)">Action</th>
                                        <th style="padding:6px 10px;text-align:left;font-weight:600;color:var(--text-secondary)">Booking ID</th>
                                        <th style="padding:6px 10px;text-align:left;font-weight:600;color:var(--text-secondary)">Guest</th>
                                        <th style="padding:6px 10px;text-align:left;font-weight:600;color:var(--text-secondary)">Room</th>
                                        <th style="padding:6px 10px;text-align:left;font-weight:600;color:var(--text-secondary)">Check In</th>
                                        <th style="padding:6px 10px;text-align:left;font-weight:600;color:var(--text-secondary)">Check Out</th>
                                        <th style="padding:6px 10px;text-align:left;font-weight:600;color:var(--text-secondary)">Amount</th>
                                        <th style="padding:6px 10px;text-align:left;font-weight:600;color:var(--text-secondary)">What Changed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($log->details as $d)
                                    <tr style="border-top:1px solid #e5e7eb">
                                        <td style="padding:6px 10px">
                                            <span class="badge-{{ $d['action'] }}">{{ ucfirst($d['action']) }}</span>
                                        </td>
                                        <td style="padding:6px 10px;font-family:monospace;font-size:11.5px">{{ $d['sub_booking_id'] ?? '—' }}</td>
                                        <td style="padding:6px 10px;font-weight:500">{{ $d['guest'] ?? '—' }}</td>
                                        <td style="padding:6px 10px;font-family:monospace;font-size:11.5px">{{ $d['room'] ?? '—' }}</td>
                                        <td style="padding:6px 10px">{{ $d['check_in'] ?? '—' }}</td>
                                        <td style="padding:6px 10px">{{ $d['check_out'] ?? '—' }}</td>
                                        <td style="padding:6px 10px">{{ isset($d['amount']) ? 'RM '.number_format($d['amount'],2) : '—' }}</td>
                                        <td style="padding:6px 10px">
                                            @if(!empty($d['changes']))
                                                @foreach($d['changes'] as $field => $change)
                                                <span class="changes-tag">{{ $field }}: {{ $change['from'] ?? '—' }} → {{ $change['to'] ?? '—' }}</span>
                                                @endforeach
                                            @else
                                                <span style="color:#9ca3af">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div style="padding:14px 20px;color:var(--text-secondary);font-size:13px">No new or updated bookings in this run.</div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" style="padding:40px;text-align:center;color:var(--text-secondary)">No sync runs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('fetch-form').addEventListener('submit', function() {
    var btn = document.getElementById('fetch-btn');
    btn.disabled = true;
    btn.textContent = 'Fetching… this may take a while';
});

function toggleDetail(id) {
    var row = document.getElementById('detail-' + id);
    row.classList.toggle('open');
}
</script>
@endpush
