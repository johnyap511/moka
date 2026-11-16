@extends('admin.layout')
@section('title', 'Job Applications')
@section('page-title', 'Job Applications')

@section('content')

<div class="page-header">
    <div>
        <h1>Job Applications</h1>
        <p>Applications sent from the careers page on the website. Each one is also emailed when it arrives.</p>
    </div>
</div>

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
    <a href="/admin/careers" class="btn btn-sm {{ $show === 'real' ? 'btn-primary' : 'btn-secondary' }}">Applications ({{ $counts['real'] }})</a>
    <a href="/admin/careers?show=filtered" class="btn btn-sm {{ $show === 'filtered' ? 'btn-primary' : 'btn-secondary' }}">Filtered as spam ({{ $counts['filtered'] }})</a>
</div>

<div class="card">
    <div class="card-header">
        <h2>{{ $show === 'filtered' ? 'Filtered as spam' : 'Applications' }}</h2>
        <span class="badge badge-blue">{{ $applications->count() }} shown</span>
    </div>
    <div class="table-wrap">
        @if($applications->isEmpty())
            <div class="empty-state">
                <p>{{ $show === 'filtered' ? 'Nothing has been filtered.' : 'No applications yet.' }}</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Received</th>
                        <th>Role</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>About</th>
                        <th>{{ $show === 'filtered' ? 'Why filtered' : 'CV' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($applications as $a)
                    <tr>
                        <td style="white-space:nowrap">{{ optional($a->created_at)->format('j M Y, H:i') }}</td>
                        <td>{{ $a->role_title }}</td>
                        <td>{{ $a->name }}</td>
                        <td style="white-space:nowrap">{{ $a->phone ?: '—' }}</td>
                        <td>{{ $a->email }}</td>
                        <td style="max-width:420px">
                            <div style="white-space:pre-wrap;font-size:13px;line-height:1.5">{{ \Illuminate\Support\Str::limit($a->message, 400) }}</div>
                            @if($a->link)<div style="font-size:12px;margin-top:4px;word-break:break-all;color:#6b7280">Link: {{ $a->link }}</div>@endif
                        </td>
                        <td style="white-space:nowrap">
                            @if($show === 'filtered')
                                <span class="badge badge-orange">{{ $a->spam_reason }}</span>
                            @elseif($a->cv_path)
                                <a href="/admin/careers/{{ $a->id }}/cv" class="btn btn-secondary btn-sm">Download CV</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
