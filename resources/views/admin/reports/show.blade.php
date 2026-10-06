@extends('admin.layout')
@section('title', 'Owner reports · ' . $run->monthLabel())
@section('page-title', 'Owner reports')

@section('content')
@php
    $rm = fn ($v) => $v === null ? '—' : 'RM ' . number_format($v, 2);
    $editable = in_array($run->status, ['draft', 'error']);
    $busy = in_array($run->status, ['queued', 'running']);
@endphp

<div class="or-head">
    <div>
        <h1>{{ $run->monthLabel() }} report pack</h1>
        <p class="or-sub"><a href="{{ route('admin.reports.index') }}">← All reports</a> · uploaded {{ $run->created_at->format('j M Y H:i') }} by {{ $run->user->name ?? '—' }}</p>
    </div>
</div>

@if($run->status === 'error')
<div class="or-err"><b>Failed:</b> {{ $run->error }} — check the settings below and try again.</div>
@endif

@if($busy)
<div class="card"><div class="card-body">
    <div class="or-progress"><div class="or-spin"></div><div><b>{{ $run->status === 'queued' ? 'Waiting to start' : 'Generating' }}</b> — about 2 to 10 minutes. This page refreshes itself; you can leave and come back.</div></div>
    <p class="or-files" style="margin:10px 0 0">Master <b>{{ $run->master_name }}</b> · tab <b>{{ $run->master_sheet }}</b> · bookings <b>{{ $run->recon_name }}</b>@if($run->prev_name) · previous <b>{{ $run->prev_name }}</b>@endif</p>
</div></div>
<script>
(function(){var u=@json(route('admin.reports.status', $run));function t(){fetch(u,{headers:{'Accept':'application/json'}}).then(r=>r.json()).then(j=>{if(j.status!=='queued'&&j.status!=='running')location.reload();}).catch(()=>{});}setInterval(t,5000);})();
</script>
@endif

@if($run->status === 'done')
<div class="or-tiles">
    <div class="or-tile or-tile--main"><span>Total payable to owners</span><b>{{ $rm($run->total_payable) }}</b></div>
    <div class="or-tile"><span>Moka net profit</span><b>{{ $rm($run->total_moka_np) }}</b></div>
    <div class="or-tile"><span>Flags to review</span><b>{{ count($run->flags ?? []) }}</b></div>
    <div class="or-tile"><span>Zip</span><b style="font-size:14px">{{ $run->zip_name }}</b><span>{{ number_format(($run->size ?? 0) / 1048576, 1) }} MB · {{ $run->finished_at?->format('j M H:i') }}</span></div>
</div>
<div class="card"><div class="card-body">
    <div class="or-actions">
        <a class="btn btn-primary" href="{{ route('admin.reports.download', $run) }}">Download zip</a>
        <span class="or-files">Master <b>{{ $run->master_name }}</b> · tab <b>{{ $run->master_sheet }}</b> · bookings <b>{{ $run->recon_name }}</b>@if($run->prev_name) · previous <b>{{ $run->prev_name }}</b>@endif</span>
    </div>
    @if($run->flags)
    <h3 class="or-h3" style="margin-top:14px">Review before sending</h3>
    <ul class="or-flags">@foreach($run->flags as $f)<li>{{ $f }}</li>@endforeach</ul>
    @else
    <p class="or-help" style="margin-top:12px">No flags. Still read the compilation before sending.</p>
    @endif
    @if($run->overrides)
    <details class="or-adv" style="margin-top:10px"><summary>Settings used this month</summary><pre class="or-log">{{ json_encode($run->overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details>
    @endif
    @if($run->log)
    <details class="or-adv" style="margin-top:10px"><summary>Engine printout</summary><pre class="or-log">{{ $run->log }}</pre></details>
    @endif
</div></div>
@endif

@if($editable)
<div class="card"><div class="card-body">
    <form method="post" action="{{ route('admin.reports.generate', $run) }}" class="or-form">
        @csrf
        <p class="or-files" style="margin:0">Master <b>{{ $run->master_name }}</b> · bookings <b>{{ $run->recon_name }}</b>@if($run->prev_name) · previous <b>{{ $run->prev_name }}</b>@else · no previous master (new/removed units will not be flagged)@endif</p>

        <div>
            <div style="font-weight:600;margin-bottom:4px">Which tab in the master holds {{ $run->monthLabel() }}'s data?</div>
            <p class="or-help">Tab names are often a month behind — pick by content, not name.</p>
            <div class="or-radios">
                @foreach($run->sheets ?? [] as $s)
                <label><input type="radio" name="master_sheet" value="{{ $s }}" {{ old('master_sheet', $run->master_sheet) === $s ? 'checked' : '' }} required> {{ $s }}</label>
                @endforeach
            </div>
        </div>

        @if($run->prev_name && $run->prev_sheets)
        <label>Tab in the previous master
            <select name="prev_sheet">
                @foreach($run->prev_sheets as $s)<option value="{{ $s }}" {{ old('prev_sheet', $run->prev_sheet) === $s ? 'selected' : '' }}>{{ $s }}</option>@endforeach
            </select>
        </label>
        @endif

        <details class="or-adv" {{ $errors->any() || session('error') ? 'open' : '' }}>
            <summary>Advanced: monthly judgment settings</summary>
            <p class="or-help">Minimum-guarantee units, owner stays, office rents, Alinea fixed rents, bookings to re-tag as short-term, units forced to long-term. {{ $defaultsKnown ? 'Pre-filled with the current standard settings; most months leave as is.' : 'Leave empty to use the standard settings.' }}</p>
            <textarea name="overrides" spellcheck="false" placeholder='{"mg_units": {"EkoCheras J-22-06": 2000}, "owner_stay": [], "office_rent": {}, "alinea_rent_ovr": {}, "str_retag": [], "lt_override": {}}'>{{ old('overrides', $overridesJson) }}</textarea>
        </details>

        <div class="or-actions" style="margin-top:0">
            <button class="btn btn-primary" type="submit">{{ $run->status === 'error' ? 'Try again' : 'Generate reports' }}</button>
            <button class="btn btn-danger" type="submit" form="or-del" onclick="return confirm('Remove this upload?')">Remove</button>
        </div>
    </form>
    <form id="or-del" method="post" action="{{ route('admin.reports.destroy', $run) }}">@csrf</form>
    @if($run->log)
    <details class="or-adv" style="margin-top:10px"><summary>Engine printout from the failed run</summary><pre class="or-log">{{ $run->log }}</pre></details>
    @endif
</div></div>
@endif
@endsection

@push('scripts')
@include('admin.reports._style')
@endpush
