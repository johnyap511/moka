@extends('admin.layout')
@section('title', $s->no)
@section('page-title', 'Tenancy statement ' . $s->no)

@push('styles')
<style>
.tv{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(300px,1fr);gap:16px;align-items:start}@media (max-width:980px){.tv{grid-template-columns:minmax(0,1fr)}}
.tv-card{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:16px}
.tv-top{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;margin-bottom:12px}.tv-top h1{margin:0;font-size:20px}.tv-top h1 small{font-size:13px;color:var(--text-secondary,#64748b);font-weight:500;margin-left:8px}
.tv-void{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;border-radius:10px;padding:10px 12px;font-size:13px;margin-bottom:12px}
.tv-meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px 16px;margin-bottom:14px}@media (max-width:640px){.tv-meta{grid-template-columns:1fr 1fr}}.tv-meta span{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-secondary,#64748b)}.tv-meta b{font-size:14px}
.tv-grp{display:flex;justify-content:space-between;align-items:baseline;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#0f766e;padding:12px 0 4px;border-bottom:1px solid #e5e7eb}.tv-grp small{font-weight:500;text-transform:none;letter-spacing:0;color:var(--text-secondary,#64748b)}
.tv-line{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:2px 12px;padding:7px 0;border-bottom:1px solid #f1f5f9;font-size:13.5px}.tv-line .amt{font-variant-numeric:tabular-nums;white-space:nowrap;text-align:right}.tv-line .calc{grid-column:1/-1;font-size:11.5px;color:var(--text-secondary,#64748b)}
.tv-sub{display:flex;justify-content:space-between;font-size:12.5px;font-weight:600;color:var(--text-secondary,#64748b);padding:8px 0 2px}
.tv-tot{margin-top:12px}.tv-tot div{display:flex;justify-content:space-between;padding:5px 0;font-size:13.5px;color:var(--text-secondary,#64748b)}.tv-tot div b{color:#111827;font-variant-numeric:tabular-nums}.tv-tot .big{border-top:2px solid #111827;margin-top:4px;padding-top:9px;color:#111827;font-weight:700}.tv-tot .big b{font-size:18px}
.tv-verdict{margin-top:12px;border-radius:10px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;font-weight:700}.tv-verdict.ok{background:#dcfce7;color:#166534}.tv-verdict.bad{background:#fee2e2;color:#991b1b}.tv-verdict b{font-size:22px}
.tv-rem{margin-top:12px;font-size:13px;color:#374151;white-space:pre-wrap}
.tv-act{display:grid;gap:8px}.tv-act .btn{width:100%;text-align:center}.tv-act form{display:grid;gap:6px}.tv-act input{padding:7px 10px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px}
.tv-pdf{font-size:12.5px;color:var(--text-secondary,#64748b);margin-top:8px}.tv-pdf code{font-size:11px}
.tv-warn{background:#fef3c7;color:#92400e;border-radius:8px;padding:10px 12px;font-size:12.5px}
</style>
@endpush

@section('content')
@php $m = fn ($n) => 'RM ' . number_format((float) $n, 2); @endphp
<div class="tv-top">
    <h1>{{ $s->no }} <small>issued {{ $s->created_at->format('j M Y \a\t H:i') }} by {{ $s->creator->name ?? '—' }}</small></h1>
    <a href="{{ route('admin.tenancy.index') }}" class="btn btn-sm">← All statements</a>
</div>
@if($s->isVoid())
<div class="tv-void"><b>VOID</b> · {{ $s->voided_at->format('j M Y H:i') }} by {{ $s->voider->name ?? '—' }} · {{ $s->void_reason }}. Kept for the record; do not send this to the tenant.</div>
@endif

<div class="tv">
    <div class="tv-card">
        <div class="tv-meta">
            <div><span>Statement to</span><b>{{ $s->tenant_name }}</b></div>
            <div><span>Stay</span><b>{{ $d['when'] ?: '—' }}</b>@if($s->stay_days)<div style="font-size:12px;color:#64748b">{{ $s->stay_days }} {{ $s->inclusive ? 'days' : 'nights' }}@if($d['unitCount'] > 1), {{ $d['unitCount'] }} units @endif</div>@endif</div>
            <div><span>Booking reference</span><b>{{ $s->booking_ref ?: '—' }}</b></div>
            <div><span>Property</span><b>{{ $s->property ?: '—' }}</b></div>
            <div><span>Units</span><b>{{ $s->unit_names ?: '—' }}</b></div>
            <div><span>Prepared by</span><b>{{ $s->prepared_by ?: '—' }}</b></div>
        </div>

        @foreach($d['units'] as $u)
            <div class="tv-grp"><span>Unit {{ $u['letter'] }} · {{ $u['name'] }}</span><small>{{ $u['meta'] }}</small></div>
            @foreach($u['lines'] as $l)
                <div class="tv-line"><span>{{ $l['label'] }}</span><span class="amt">{{ $m($l['amt']) }}</span><span class="calc">{{ $l['period'] }}@if($l['period']) · @endif{{ $l['basis'] }}</span></div>
            @endforeach
            @if(count($d['units']) > 1)<div class="tv-sub"><span>Subtotal — {{ $u['name'] }}</span><span>{{ $m($u['total']) }}</span></div>@endif
        @endforeach
        @if($d['addons'])
            <div class="tv-grp"><span>Fixed charges</span><small>charged in full, not split by days</small></div>
            @foreach($d['addons'] as $a)
                <div class="tv-line"><span>{{ $a['label'] }}</span><span class="amt">{{ $m($a['amt']) }}</span><span class="calc">{{ $a['calc'] }}</span></div>
            @endforeach
        @endif

        <div class="tv-tot">
            <div><span>Bills split by days</span><b>{{ $m($s->bill_total) }}</b></div>
            <div><span>Fixed charges</span><b>{{ $m($s->addon_total) }}</b></div>
            @if($s->arrears > 0)<div><span>Rent arrears / other</span><b>{{ $m($s->arrears) }}</b></div>@endif
            <div class="big"><span>Total payable by tenant</span><b>{{ $m($s->total) }}</b></div>
            @if($s->deposit > 0)<div><span>Less security deposit held</span><b>{{ $m($s->deposit) }}</b></div>@endif
        </div>
        @if($s->balance !== null)
            <div class="tv-verdict {{ $s->balance >= 0 ? 'ok' : 'bad' }}"><span>{{ $s->balance >= 0 ? 'Refund due to tenant' : 'Balance still owed by tenant' }}</span><b>{{ $m(abs($s->balance)) }}</b></div>
        @endif
        @if($s->remarks)<div class="tv-rem"><b>Remarks.</b> {{ $s->remarks }}</div>@endif
    </div>

    <div class="tv-card tv-act">
        @if($s->pdf_path)
            <a href="{{ route('admin.tenancy.pdf', $s) }}" class="btn btn-primary">Download PDF</a>
            <div class="tv-pdf">PDF on file as issued · {{ number_format($s->pdf_size / 1024, 0) }} KB · <code>sha1 {{ substr($s->pdf_sha1, 0, 12) }}…</code></div>
        @elseif(admin_can('tenancy.manage') && !$s->isVoid())
            <div class="tv-warn">The PDF for this statement was not stored (the upload did not complete). Re-issue it from the figures on file; the statement itself is unchanged.</div>
            <button type="button" class="btn btn-primary" id="tv-reissue">Create and store the PDF</button>
        @else
            <div class="tv-warn">No PDF on file.</div>
        @endif
        @if(admin_can('tenancy.manage'))
            <a href="{{ route('admin.tenancy.create', ['from' => $s->id]) }}" class="btn">Copy as new statement</a>
            @if(!$s->isVoid())
            <form method="post" action="{{ route('admin.tenancy.void', $s) }}" onsubmit="return confirm('Void {{ $s->no }}? It stays on file, marked void, and cannot be undone.')">
                @csrf
                <input name="reason" placeholder="Reason for voiding (required)" maxlength="255" required>
                <button class="btn" type="submit" style="color:#b91c1c">Void this statement</button>
            </form>
            @endif
        @endif
        @if(session('error'))<div class="tv-warn">{{ session('error') }}</div>@endif
    </div>
</div>
@endsection

@push('scripts')
@if(!$s->pdf_path && admin_can('tenancy.manage') && !$s->isVoid())
@include('admin.tenancy._pdfjs')
<script>
(function(){
    var btn = document.getElementById('tv-reissue'); if (!btn) return;
    btn.addEventListener('click', function(){
        var built = window.MokaStatementPDF(@json($company), @json($d), @json($s->no), @json($s->created_at->format('j M Y \a\t H:i')));
        if (!built) { alert('The PDF builder did not load. Check the internet connection and try again.'); return; }
        btn.disabled = true; btn.textContent = 'Storing…';
        var fd = new FormData(); fd.append('pdf', built.blob, built.name);
        fetch(@json(route('admin.tenancy.attach-pdf', $s)), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }, body: fd })
            .then(function(r){ return r.json(); })
            .then(function(j){ if (j && j.ok) { built.doc.save(built.name); location.reload(); } else { alert((j && j.message) || 'Could not store the PDF.'); btn.disabled = false; btn.textContent = 'Create and store the PDF'; } })
            .catch(function(){ alert('Could not store the PDF.'); btn.disabled = false; btn.textContent = 'Create and store the PDF'; });
    });
})();
</script>
@endif
@endpush
