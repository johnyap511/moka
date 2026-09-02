@extends('admin.layout')
@section('title', 'Group Units')
@section('page-title', 'Group Units')

@section('content')
<div class="page-header">
    <div>
        <h1>{{ $group->name }}</h1>
        <p>{{ $group->description ?: 'Units in this group' }}</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/group" class="btn btn-secondary">← Back to Groups</a>
        <a href="/admin/group/{{ $group->id }}/edit" class="btn btn-primary">Edit Group</a>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card"><div class="val">{{ $listings->count() }}</div><div class="lbl">Units</div></div>
    <div class="stat-card">
        <div class="val" style="color:var(--teal)">{{ $listings->whereNull('archived_at')->count() }}</div>
        <div class="lbl">Still managed</div>
    </div>
    <div class="stat-card">
        <div class="val" style="color:var(--text-secondary)">{{ $listings->whereNotNull('archived_at')->count() }}</div>
        <div class="lbl">Archived</div>
    </div>
    <div class="stat-card"><div class="val">{{ $types->count() }}</div><div class="lbl">Room types</div></div>
</div>

@if($types->isNotEmpty())
<div class="card" style="margin-bottom:20px">
    <div class="card-body">
        <strong style="font-size:13px">Room types and weights</strong>
        <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap">
            @foreach($types as $t)
                <span class="badge badge-gray">{{ $t->type }} @if($t->weight !== null)· {{ $t->weight }}@endif</span>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Unit</th>
                    <th>Title</th>
                    <th>Owner</th>
                    <th style="text-align:center">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($listings as $listing)
                <tr>
                    <td class="mono">{{ $listing->id }}</td>
                    <td style="font-weight:600">{{ $listing->name }}</td>
                    <td>{{ $listing->title ?? '—' }}</td>
                    <td>{{ $owners[$listing->user_id] ?? '—' }}</td>
                    <td style="text-align:center">
                        @if($listing->archived_at)
                            <span class="badge badge-gray">Archived</span>
                        @elseif($listing->status == 1)
                            <span class="badge badge-green">Active</span>
                        @else
                            <span class="badge badge-red">Inactive</span>
                        @endif
                    </td>
                    <td class="actions">
                        <a href="/admin/listing/{{ $listing->id }}/edit" class="btn btn-secondary btn-sm">Edit</a>
                        <a href="/admin/calendar?listing_id={{ $listing->id }}" class="btn btn-secondary btn-sm">Calendar</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:var(--text-secondary)">
                        No units are in this group yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
