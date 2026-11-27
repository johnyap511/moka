@extends('admin.layout')
@section('title', 'Contact Messages')
@section('page-title', 'Contact Messages')

@push('styles')
<style>
/* On a phone each message becomes a card, so the text and the button stay on screen. */
@media (max-width:760px){
  .cm-table thead{display:none}.cm-table,.cm-table tbody,.cm-table tr,.cm-table td{display:block;width:auto}
  .cm-table tr{padding:14px 16px;border-top:1px solid #e5e7eb}.cm-table td{padding:0 0 6px!important;border:0!important;white-space:normal!important;min-width:0!important;max-width:none!important}
  .cm-table td.cm-when{font-size:12px;color:#6b7280}.cm-table td.cm-name{font-weight:600}.cm-table td.cm-msg{padding:6px 0 10px!important}
  .cm-table td.actions{padding-top:8px!important}
}
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1>Contact Messages</h1>
        <p>Messages sent from the contact form on the website. Mark each one once the sender has been answered.</p>
    </div>
</div>

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;align-items:center">
    @foreach(['new' => 'New', 'done' => 'Followed up', 'all' => 'All'] as $key => $label)
        <a href="/admin/sales/messages{{ $key === 'new' ? '' : '?show=' . $key }}"
           class="btn btn-sm {{ $show === $key ? 'btn-primary' : 'btn-secondary' }}">{{ $label }} ({{ $counts[$key] }})</a>
    @endforeach
    <a href="/admin/sales/messages?show=spam" style="margin-left:auto;font-size:13px;color:#6b7280{{ $show === 'spam' ? ';font-weight:600' : '' }}">Caught as spam ({{ number_format($counts['spam']) }})</a>
</div>

<div class="card">
    <div class="card-header">
        <h2>{{ $show === 'spam' ? 'Caught as spam' : 'Messages' }}</h2>
        <span class="badge badge-blue">{{ $messages->count() }} shown</span>
    </div>
    <div class="table-wrap">
        @if($messages->isEmpty())
            <div class="empty-state">
                <p>{{ $show === 'new' ? 'No new messages. All caught up.' : 'No messages here.' }}</p>
            </div>
        @else
            <table class="cm-table" style="min-width:0">
                <thead>
                    <tr>
                        <th>Received</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Message</th>
                        <th>{{ $show === 'spam' ? 'Why' : 'Status' }}</th>
                        @if($show !== 'spam')<th>Actions</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($messages as $m)
                    @php $isDone = !empty($m->followed_up_at); @endphp
                    <tr>
                        <td class="cm-when" style="white-space:nowrap">{{ $m->created_at ? \Carbon\Carbon::parse($m->created_at)->format('j M Y, H:i') : '—' }}</td>
                        <td class="cm-name">{{ $m->name ?: '—' }}</td>
                        <td style="white-space:nowrap">{{ $m->phone ?: '—' }}</td>
                        <td>{{ $m->email ?: '—' }}</td>
                        <td class="cm-msg" style="min-width:260px;max-width:460px;white-space:pre-line;overflow-wrap:anywhere">{{ \Illuminate\Support\Str::limit((string) $m->message, $show === 'spam' ? 160 : 1200) }}</td>
                        @if($show === 'spam')
                        <td style="font-size:12.5px;color:#6b7280">{{ $m->spam_reason ?: '—' }}</td>
                        @else
                        <td>
                            @if($isDone)
                                <span class="badge badge-green">Followed up</span>
                                <div style="font-size:12px;color:#6b7280;margin-top:4px;white-space:nowrap">
                                    {{ $names[$m->followed_up_by] ?? 'Staff' }}, {{ \Carbon\Carbon::parse($m->followed_up_at)->format('j M, H:i') }}
                                </div>
                            @else
                                <span class="badge badge-orange">New</span>
                            @endif
                        </td>
                        <td class="actions" style="white-space:nowrap">
                            <form action="/admin/sales/messages/{{ $m->id }}/follow-up" method="POST" style="display:inline">
                                @csrf
                                <input type="hidden" name="done" value="{{ $isDone ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm {{ $isDone ? 'btn-secondary' : 'btn-primary' }}">{{ $isDone ? 'Undo' : 'Mark followed up' }}</button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
