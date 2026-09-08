@extends('admin.layout')
@section('title', 'Mail settings')
@section('content')
<div class="page-header"><div><h1>Mail settings</h1><p>The account homemoka.com sends from: estimate requests, guest emails, password resets.</p></div></div>
<div class="card" style="max-width:720px">
    <div class="card-header"><h2>Outgoing mail (SMTP)</h2><span class="badge {{ $hasPassword ? 'badge-green' : 'badge-red' }}">{{ $hasPassword ? 'Password set (' . $source . ')' : 'No password' }}</span></div>
    <div class="card-body">
        <p style="font-size:13px;color:var(--text-secondary);margin-bottom:14px">Server {{ $host }}, port {{ $port }}. The password is stored encrypted and never shown again. For Zoho, use an <b>app password</b> from Zoho Mail → Settings → Security → App Passwords (two-factor must be on), or ask the Zoho admin to allow IMAP/SMTP access for this mailbox.</p>
        <form method="POST" action="/admin/settings/mail" style="display:grid;gap:14px;max-width:420px">
            @csrf
            <div class="form-group" style="margin:0"><label class="form-label">Mailbox</label><input type="email" name="username" class="form-input" value="{{ old('username', $username) }}" required></div>
            <div class="form-group" style="margin:0"><label class="form-label">Password / app password</label><input type="password" name="password" class="form-input" autocomplete="new-password" required></div>
            <div><button type="submit" class="btn btn-primary">Save</button></div>
        </form>
        <hr style="border:0;border-top:1px solid var(--border);margin:20px 0">
        <form method="POST" action="/admin/settings/mail/test" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
            @csrf
            <div class="form-group" style="margin:0;flex:1;min-width:220px"><label class="form-label">Send a test to</label><input type="email" name="to" class="form-input" value="{{ $username }}"></div>
            <button type="submit" class="btn btn-secondary">Send test email</button>
        </form>
    </div>
</div>
@endsection
