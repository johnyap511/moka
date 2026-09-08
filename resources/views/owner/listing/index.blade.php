@extends('owner.layout')

@section('title', 'My Units')
@section('page-title', 'My Units')

@php
    $hotelTone = function ($name) {
        $h = strtolower(strtok((string) $name, ' '));
        return ['ekocheras' => '#0a8a72', 'bell' => '#1d4ed8', 'forum' => '#7c3aed', 'damai' => '#7c3aed', 'alinea' => '#F36523', 'arte' => '#d97706', 'queensville' => '#d97706', 'kl' => '#d97706'][$h] ?? '#475569';
    };
@endphp

@push('styles')
<style>
.ls-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px}
.ls-head h1{font-size:24px;font-weight:700;letter-spacing:-.4px}
.ls-head p{color:var(--text-secondary);margin-top:4px;font-size:14px}
.ls-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px}
.ls-card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;display:flex;flex-direction:column;transition:transform .15s,box-shadow .15s}
.ls-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.08),0 0 0 1px rgba(0,0,0,.04)}
.ls-band{height:6px}
.ls-body{padding:16px 18px 14px;display:flex;flex-direction:column;gap:10px;flex:1}
.ls-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
.ls-name{font-size:16px;font-weight:700;letter-spacing:-.2px;line-height:1.25}
.ls-addr{font-size:12.5px;color:var(--text-secondary);line-height:1.45;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.ls-meta{display:flex;flex-wrap:wrap;gap:6px}
.ls-facts{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:2px}
.ls-fact{background:#f8f9fb;border-radius:10px;padding:9px 11px}
.ls-fact .k{font-size:11px;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em}
.ls-fact .v{font-size:14px;font-weight:600;margin-top:2px}
.ls-actions{display:flex;gap:8px;padding:12px 18px 16px;border-top:1px solid #f0f0f2}
.ls-actions .btn{flex:1;justify-content:center;font-weight:600}
.ls-actions .btn svg{width:15px;height:15px}
@media (max-width:700px){.ls-head h1{font-size:20px}.ls-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="ls-head">
    <div>
        <h1>My Units</h1>
        <p>{{ $listings->count() }} {{ $listings->count() == 1 ? 'unit' : 'units' }} on your account</p>
    </div>
</div>

@if($listings->isEmpty())
<div class="card">
    <div class="empty-state">
        <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
        <p>No units assigned yet</p>
        <small>Contact MOKA to have your units added.</small>
    </div>
</div>
@else
<div class="ls-grid">
    @foreach($listings as $listing)
    @php $tone = $hotelTone($listing->name); $isPool = $listing->type === 'group'; @endphp
    <div class="ls-card">
        <div class="ls-band" style="background:{{ $tone }}"></div>
        <div class="ls-body">
            <div class="ls-top">
                <div class="ls-name">{{ $listing->title ?? $listing->name }}</div>
                @if($listing->status == 1)<span class="badge badge-green">Active</span>@else<span class="badge badge-gray">Inactive</span>@endif
            </div>
            @if($listing->address)<div class="ls-addr">{{ $listing->address }}</div>@endif
            <div class="ls-meta">
                <span class="badge" style="background:{{ $tone }}18;color:{{ $tone }}">{{ strtok((string) $listing->name, ' ') }}</span>
                @if($isPool)<span class="badge badge-orange" title="Revenue is shared across the pool by unit size">Pool profit sharing</span>@else<span class="badge badge-gray">Individual</span>@endif
            </div>
            <div class="ls-facts">
                <div class="ls-fact"><div class="k">Base rate</div><div class="v">RM {{ number_format($listing->default_price ?? 0) }}<span style="font-weight:400;font-size:11px;color:var(--text-secondary)"> / night</span></div></div>
                <div class="ls-fact"><div class="k">Unit no.</div><div class="v">#{{ $listing->id }}</div></div>
            </div>
        </div>
        <div class="ls-actions">
            <a href="/owner/dashboard?listing_id={{ $listing->id }}" class="btn btn-secondary btn-sm">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Dashboard
            </a>
            <a href="/owner/listing/{{ $listing->id }}/calendar" class="btn btn-primary btn-sm">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Calendar
            </a>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
