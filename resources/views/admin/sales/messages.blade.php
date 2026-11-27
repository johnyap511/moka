@extends('admin.layout')
@section('title', 'Contact Messages')
@section('page-title', 'Contact Messages')

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
        <h1>Contact Messages</h1>
        <p>Messages sent from the contact form on the website. Mark each one once the sender has been answered.</p>
    </div>
</div>

<div class="sl-filters">
    @foreach(['new' => 'New', 'done' => 'Followed up', 'all' => 'All'] as $key => $label)
        <a href="/admin/sales/messages{{ $key === 'new' ? '' : '?show=' . $key }}"
           class="btn btn-sm {{ $show === $key ? 'btn-primary' : 'btn-secondary' }}">{{ $label }} ({{ $counts[$key] }})</a>
    @endforeach
    @if($counts['spam'] > 0 || $show === 'spam')
    <a href="/admin/sales/messages?show=spam" class="sl-spam" @if($show === 'spam') style="font-weight:600" @endif>Caught as spam ({{ number_format($counts['spam']) }})</a>
    @endif
</div>

<div class="card">
    <div class="card-header">
        <h2>{{ $show === 'spam' ? 'Caught as spam' : 'Messages' }}</h2>
        <span class="badge badge-blue">{{ $messages->count() }} shown</span>
    </div>
    @if($messages->isEmpty())
        <div class="empty-state">
            <p>{{ $show === 'new' ? 'No new messages. All caught up.' : 'No messages here.' }}</p>
        </div>
    @else
    <div class="sl-list">
        @foreach($messages as $m)
        @php $isDone = !empty($m->followed_up_at); $w = $wa($m->phone); @endphp
        <div class="sl-row">
            <div class="sl-who">
                <div class="sl-name">{{ $m->name ?: 'No name given' }}</div>
                @if($m->phone)<div class="sl-line"><a href="tel:{{ preg_replace('/[^\d+]/', '', $m->phone) }}">{{ $m->phone }}</a>@if($w)<a class="sl-wa" href="https://wa.me/{{ $w }}" target="_blank" rel="noopener">WhatsApp</a>@endif</div>@endif
                @if($m->email)<div class="sl-line"><a href="mailto:{{ $m->email }}">{{ $m->email }}</a></div>@endif
                <div class="sl-when">{{ $m->created_at ? \Carbon\Carbon::parse($m->created_at)->format('j M Y, g:i a') : '' }}</div>
            </div>
            <div class="sl-body">{{ \Illuminate\Support\Str::limit(trim((string) $m->message), $show === 'spam' ? 200 : 1500) }}@if($show === 'spam' && $m->spam_reason)<span class="sl-sub">Why: {{ $m->spam_reason }}</span>@endif</div>
            <div class="sl-do">
                @if($show !== 'spam')
                    @if($isDone)
                        <span class="badge badge-green">Followed up</span>
                        <span class="sl-by">{{ $names[$m->followed_up_by] ?? 'Staff' }}, {{ \Carbon\Carbon::parse($m->followed_up_at)->format('j M, g:i a') }}</span>
                    @else
                        <span class="badge badge-orange">New</span>
                    @endif
                @endif
                <div class="sl-btns">
                    @if($show !== 'spam')
                    <form action="/admin/sales/messages/{{ $m->id }}/follow-up" method="POST">
                        @csrf
                        <input type="hidden" name="done" value="{{ $isDone ? 0 : 1 }}">
                        <button type="submit" class="btn btn-sm {{ $isDone ? 'btn-secondary' : 'btn-primary' }}">{{ $isDone ? 'Undo' : 'Mark followed up' }}</button>
                    </form>
                    @endif
                    <form action="/admin/sales/messages/{{ $m->id }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this message? This cannot be undone.')">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection
