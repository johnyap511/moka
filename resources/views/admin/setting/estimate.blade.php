@extends('admin.layout')
@section('title', 'Owner Estimate Requests')
@section('page-title', 'Owner Estimate Requests')

@section('content')

<div class="page-header">
    <div>
        <h1>Owner Estimate Requests</h1>
        <p>Requests sent by owners from the estimate form on the website. Mark each one once the owner has been contacted.</p>
    </div>
</div>

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
    @foreach(['all' => 'All', 'new' => 'New', 'done' => 'Followed up'] as $key => $label)
        <a href="/admin/setting/estimate{{ $key === 'all' ? '' : '?show=' . $key }}"
           class="btn btn-sm {{ $show === $key ? 'btn-primary' : 'btn-secondary' }}">{{ $label }} ({{ $counts[$key] }})</a>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <h2>Requests</h2>
        <span class="badge badge-blue">{{ $estimates->count() }} shown</span>
    </div>
    <div class="table-wrap">
        @if($estimates->isEmpty())
            <div class="empty-state">
                <p>No requests here.</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Received</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Property</th>
                        <th>Bedrooms</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($estimates as $estimate)
                    @php $isDone = (int) $estimate->status === \App\OtherModel\PropertyEstimate::STATUS_FOLLOWED_UP; @endphp
                    <tr>
                        <td style="white-space:nowrap">{{ optional($estimate->created_at)->format('j M Y, H:i') ?? '—' }}</td>
                        <td>{{ $estimate->name ?: '—' }}</td>
                        <td style="white-space:nowrap">{{ $estimate->phone ?: '—' }}</td>
                        <td>{{ $estimate->email ?: '—' }}</td>
                        <td>{{ $estimate->address ?: '—' }}</td>
                        <td>{{ $estimate->bedroom ?: '—' }}</td>
                        <td>
                            @if($isDone)
                                <span class="badge badge-green">Followed up</span>
                                <div style="font-size:12px;color:#6b7280;margin-top:4px;white-space:nowrap">
                                    {{ $names[$estimate->followed_up_by] ?? 'Staff' }}{{ $estimate->followed_up_at ? ', ' . $estimate->followed_up_at->format('j M, H:i') : '' }}
                                </div>
                            @else
                                <span class="badge badge-orange">New</span>
                            @endif
                        </td>
                        <td class="actions" style="white-space:nowrap">
                            <form action="/admin/setting/estimate/{{ $estimate->id }}/follow-up" method="POST" style="display:inline">
                                @csrf
                                <input type="hidden" name="done" value="{{ $isDone ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm {{ $isDone ? 'btn-secondary' : 'btn-primary' }}">{{ $isDone ? 'Undo' : 'Mark followed up' }}</button>
                            </form>
                            <form action="/admin/setting/estimate/{{ $estimate->id }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Delete this request? This cannot be undone.')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
