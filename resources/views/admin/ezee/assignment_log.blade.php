@extends('admin.layout')
@section('title', 'Assignment Log')
@section('page-title', 'Assignment Log')

@section('content')
<div class="page-header">
    <div>
        <h1>Assignment Log</h1>
        <p>Audit trail for all EZEE booking assignments and reassignments</p>
    </div>
    <a href="/admin/ezee/room-mapping" class="btn btn-secondary">← Back to Room Mapping</a>
</div>

<div class="card">
    <div class="card-header">
        <h2>All Assignments</h2>
        @if(method_exists($logs, 'total'))
        <span class="text-sm text-secondary">{{ number_format($logs->total()) }} records</span>
        @endif
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Guest</th>
                    <th>Room Type</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Assigned To</th>
                    <th>Old Listing</th>
                    <th>Method</th>
                    <th>By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                    $eb = $ezeeMap[$log->ezee_booking_id] ?? null;
                    $methodColors = ['auto'=>'badge-blue','manual'=>'badge-teal','reassign'=>'badge-orange'];
                @endphp
                <tr>
                    <td class="mono">#{{ $log->ezee_booking_id }}</td>
                    <td>{{ $eb ? $eb->FirstName.' '.$eb->LastName : '—' }}</td>
                    <td>{{ $eb->RoomTypeName ?? '—' }}</td>
                    <td>{{ $eb->Start ?? '—' }}</td>
                    <td>{{ $eb->End ?? '—' }}</td>
                    <td>{{ $log->listing->name ?? '—' }}</td>
                    <td>
                        @if($log->old_listing_id)
                            @php $oldListing = \App\Listing::find($log->old_listing_id); @endphp
                            <span style="color:var(--text-secondary)">{{ $oldListing->name ?? '#'.$log->old_listing_id }}</span>
                        @else
                            <span style="color:var(--text-secondary)">—</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $methodColors[$log->method] ?? 'badge-gray' }}">{{ ucfirst($log->method) }}</span></td>
                    <td>{{ $log->assignedBy->name ?? 'System' }}</td>
                    <td style="white-space:nowrap">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center;padding:40px;color:var(--text-secondary)">No assignments logged yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($logs, 'lastPage') && $logs->lastPage() > 1)
    <div class="card-body" style="padding-top:0;display:flex;align-items:center;justify-content:space-between;gap:12px">
        <span class="text-sm text-secondary">Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }}</span>
        <div style="display:flex;gap:6px">
            @if(!$logs->onFirstPage())
                <a href="{{ $logs->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Prev</a>
            @endif
            @if($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
