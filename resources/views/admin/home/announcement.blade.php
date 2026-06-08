@extends('admin.layout')
@section('page-title', 'Announcement')

@section('content')
<div class="page-header">
    <div>
        <h1>Announcement</h1>
        <p>Send announcements to all property owners</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">
    {{-- Create Form --}}
    <div class="card">
        <div class="card-header"><h2>New Announcement</h2></div>
        <div class="card-body">
            <form method="POST" action="/admin/create/announcement">
                @csrf
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-input" placeholder="Announcement title" required maxlength="150" value="{{ old('title') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea name="text" class="form-textarea" placeholder="Write your announcement here..." required maxlength="150">{{ old('text') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Link (optional)</label>
                    <input type="text" name="link" class="form-input" placeholder="https://..." maxlength="200" value="{{ old('link', '#') }}">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    Send Announcement
                </button>
            </form>
        </div>
    </div>

    {{-- Recent Announcements --}}
    <div class="card">
        <div class="card-header"><h2>Recent Announcements</h2></div>
        <div class="card-body">
            @php
                $announcements = \App\Announcement::latest()->take(10)->get();
            @endphp
            @forelse($announcements as $ann)
            <div style="padding:14px 0;border-bottom:1px solid var(--border)">
                <div style="font-weight:600;font-size:14px;margin-bottom:4px">{{ $ann->title }}</div>
                <div style="font-size:13px;color:var(--text-secondary);margin-bottom:4px">{{ $ann->text }}</div>
                <div style="font-size:11.5px;color:var(--text-secondary)">{{ $ann->created_at->diffForHumans() }}</div>
            </div>
            @empty
            <div class="empty-state">
                <p>No announcements yet</p>
                <small>Create your first announcement on the left</small>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
