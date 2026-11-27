@extends('admin.layout')
@section('title', 'Owner Estimate Requests')
@section('page-title', 'Owner Estimate Requests')

@push('styles')
@include('admin.sales._style')
@endpush

@section('content')
@php
    // A Malaysian mobile number gets a WhatsApp link beside it.
    $wa = function ($phone) { $d = preg_replace('/\D/', '', (string) $phone); if (preg_match('/^01\d{8,9}$/', $d)) { $d = '6' . $d; } return preg_match('/^601\d{8,9}$/', $d) ? $d : null; };
@endphp

<div class="page-header">
    <div>
        <h1>Owner Estimate Requests</h1>
        <p>Requests sent by owners from the estimate form on the website. Mark each one once the owner has been contacted.</p>
    </div>
</div>

<div class="sl-filters">
    @foreach(['new' => 'New', 'done' => 'Followed up', 'all' => 'All'] as $key => $label)
        <a href="/admin/setting/estimate{{ $key === 'new' ? '' : '?show=' . $key }}"
           class="btn btn-sm {{ $show === $key ? 'btn-primary' : 'btn-secondary' }}">{{ $label }} ({{ $counts[$key] }})</a>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <h2>Requests</h2>
        <span class="badge badge-blue">{{ $estimates->count() }} shown</span>
    </div>
    @if($estimates->isEmpty())
        <div class="empty-state">
            <p>{{ $show === 'new' ? 'No new requests. All caught up.' : 'No requests here.' }}</p>
        </div>
    @else
    <div class="sl-list">
        @foreach($estimates as $estimate)
        @php $isDone = (int) $estimate->status === \App\OtherModel\PropertyEstimate::STATUS_FOLLOWED_UP; $w = $wa($estimate->phone); @endphp
        <div class="sl-row">
            <div class="sl-who">
                <div class="sl-name">{{ $estimate->name ?: 'No name given' }}</div>
                @if($estimate->phone)<div class="sl-line"><a href="tel:{{ preg_replace('/[^\d+]/', '', $estimate->phone) }}">{{ $estimate->phone }}</a>@if($w)<a class="sl-wa" href="https://wa.me/{{ $w }}" target="_blank" rel="noopener">WhatsApp</a>@endif</div>@endif
                @if($estimate->email)<div class="sl-line"><a href="mailto:{{ $estimate->email }}">{{ $estimate->email }}</a></div>@endif
                <div class="sl-when">{{ optional($estimate->created_at)->format('j M Y, g:i a') }}</div>
            </div>
            <div class="sl-body"><span class="sl-label">Property</span>{{ trim((string) $estimate->address) ?: 'No address given' }}@if($estimate->bedroom)<span class="sl-sub">{{ is_numeric($estimate->bedroom) ? $estimate->bedroom . ' bedroom' . ((int) $estimate->bedroom === 1 ? '' : 's') : $estimate->bedroom }}</span>@endif</div>
            <div class="sl-do">
                @if($isDone)
                    <span class="badge badge-green">Followed up</span>
                    <span class="sl-by">{{ $names[$estimate->followed_up_by] ?? 'Staff' }}{{ $estimate->followed_up_at ? ', ' . $estimate->followed_up_at->format('j M, g:i a') : '' }}</span>
                @else
                    <span class="badge badge-orange">New</span>
                @endif
                <div class="sl-btns">
                    <form action="/admin/setting/estimate/{{ $estimate->id }}/follow-up" method="POST">
                        @csrf
                        <input type="hidden" name="done" value="{{ $isDone ? 0 : 1 }}">
                        <button type="submit" class="btn btn-sm {{ $isDone ? 'btn-secondary' : 'btn-primary' }}">{{ $isDone ? 'Undo' : 'Mark followed up' }}</button>
                    </form>
                    <form action="/admin/setting/estimate/{{ $estimate->id }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this request? This cannot be undone.')">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection
