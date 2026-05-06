@extends('admin.layout')
@section('title', 'System Logs')
@section('page-title', 'System Logs')

@section('content')

<div class="page-header">
    <div>
        <h1>System Logs</h1>
        <p>Recent activity and data change logs</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Logs</h2>
        <span class="badge badge-blue">{{ $logs->count() }} entries</span>
    </div>
    <div class="table-wrap">
        @if($logs->isEmpty())
            <div class="empty-state">
                <p>No log entries found.</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Related ID</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>{{ $log->title }}</td>
                        <td>{{ $log->related_id ?? '—' }}</td>
                        <td>
                            @if($log->status == 1)
                                <span class="badge badge-green">Success</span>
                            @elseif($log->status == 0)
                                <span class="badge badge-red">Error</span>
                            @else
                                <span class="badge badge-gray">{{ $log->status }}</span>
                            @endif
                        </td>
                        <td class="text-sm">{{ $log->created_at ? $log->created_at->format('d M Y H:i') : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
