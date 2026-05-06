@extends('owner.layout')

@section('title', 'Change Password')
@section('page-title', 'Change Password')

@section('content')
<div class="page-header">
    <div>
        <h1>Change Password</h1>
        <p>Update your account password below.</p>
    </div>
</div>

<div class="card" style="max-width:520px">
    <div class="card-header">
        <h2>Account Security</h2>
    </div>
    <div class="card-body">
        <div style="margin-bottom:20px;padding:14px;background:var(--bg);border-radius:10px;font-size:13.5px">
            <div class="flex gap-2 items-center" style="margin-bottom:4px">
                <span class="font-600">{{ $user_detail->name ?? '' }} {{ $user_detail->last_name ?? '' }}</span>
            </div>
            <div class="text-secondary">{{ $user_detail->email ?? '' }}</div>
        </div>

        <form method="POST" action="/owner/update/change_password/{{ $user_detail->id ?? '' }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-input" placeholder="Enter new password" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="c_password" class="form-input" placeholder="Confirm new password" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                Update Password
            </button>
        </form>
    </div>
</div>
@endsection
