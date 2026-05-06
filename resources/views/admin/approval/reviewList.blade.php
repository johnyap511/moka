@extends('admin.layout')
@section('title', 'Review Approval')
@section('page-title', 'Review Approval')

@section('content')

<div class="page-header">
    <div>
        <h1>Review Approval</h1>
        <p>Moderate guest reviews before publishing</p>
    </div>
</div>

@if(session('success'))
    <div class="badge badge-green" style="margin-bottom:16px;display:block;padding:10px">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-header">
        <h2>Pending Reviews</h2>
        <span class="badge badge-blue">{{ $reviews->count() }} total</span>
    </div>
    <div class="table-wrap">
        @if($reviews->isEmpty())
            <div class="empty-state">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                <p>No reviews to approve</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Listing</th>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reviews as $review)
                    <tr>
                        <td>{{ $review->id }}</td>
                        <td>{{ $review->listing_id }}</td>
                        <td>{{ $review->user_id }}</td>
                        <td>
                            <span style="color:var(--yellow)">
                                @for($i=1;$i<=5;$i++){{ $i <= ($review->listing_rate ?? 0) ? '★' : '☆' }}@endfor
                            </span>
                            {{ $review->listing_rate ?? '—' }}
                        </td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            {{ $review->listing_review ?? '—' }}
                        </td>
                        <td>
                            @if($review->status == 1)
                                <span class="badge badge-green">Approved</span>
                            @elseif($review->status == 0)
                                <span class="badge badge-yellow">Pending</span>
                            @else
                                <span class="badge badge-red">Rejected</span>
                            @endif
                        </td>
                        <td class="actions">
                            <a href="/admin/approval/review/{{ $review->id }}/edit" class="btn btn-secondary btn-sm">Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
