@extends('admin.layout')
@section('title', 'User Verification')
@section('page-title', 'User Verification')

@section('content')

<div class="page-header">
    <div>
        <h1>User Verification</h1>
        <p>Review and approve identity verifications</p>
    </div>
</div>

@if(session('success'))
    <div class="badge badge-green" style="margin-bottom:16px;display:block;padding:10px">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-header">
        <h2>Verification Requests</h2>
        <span class="badge badge-blue">{{ $verifications->count() }} requests</span>
    </div>
    <div class="table-wrap">
        @if($verifications->isEmpty())
            <div class="empty-state">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <p>No verification requests</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Document Type</th>
                        <th>Document Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($verifications as $v)
                    <tr>
                        <td>{{ $v->id }}</td>
                        <td>{{ $v->user_id }}</td>
                        <td>{{ $v->document_type ?? '—' }}</td>
                        <td>{{ $v->document_number ?? '—' }}</td>
                        <td>
                            @if($v->status == 1)
                                <span class="badge badge-green">Approved</span>
                            @elseif($v->status == 0)
                                <span class="badge badge-yellow">Pending</span>
                            @elseif($v->status == 2)
                                <span class="badge badge-red">Rejected</span>
                            @else
                                <span class="badge badge-gray">{{ $v->status }}</span>
                            @endif
                        </td>
                        <td class="actions">
                            @if($v->document_image)
                                <a href="{{ asset('storage/'.$v->document_image) }}" target="_blank" class="btn btn-secondary btn-sm">View Doc</a>
                            @endif
                            <form action="/admin/user/verify/{{ $v->id }}/approve" method="POST" style="display:inline">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                            </form>
                            <form action="/admin/user/verify/{{ $v->id }}/reject" method="POST" style="display:inline">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm">Reject</button>
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
