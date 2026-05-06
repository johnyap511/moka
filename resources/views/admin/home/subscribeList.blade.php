@extends('admin.layout')
@section('title', 'Subscribers')
@section('page-title', 'Subscribers')

@section('content')

<div class="page-header">
    <div>
        <h1>Subscribers</h1>
        <p>Email newsletter subscribers</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>All Subscribers</h2>
        <span class="badge badge-blue">{{ count($subs) }} subscribers</span>
    </div>
    <div class="table-wrap">
        @if(count($subs) == 0)
            <div class="empty-state">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <p>No subscribers yet</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subs as $sub)
                    <tr>
                        <td>{{ $sub->id }}</td>
                        <td>{{ $sub->email ?? $sub->name ?? '—' }}</td>
                        <td class="text-sm">{{ isset($sub->created_at) ? \Carbon\Carbon::parse($sub->created_at)->format('d M Y') : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
