@extends('admin.layout')
@section('title', 'Property Estimates')
@section('page-title', 'Property Estimates')

@section('content')

<div class="page-header">
    <div>
        <h1>Property Estimates</h1>
        <p>Manage expense estimate templates</p>
    </div>
</div>

@if(session('success'))
    <div class="badge badge-green" style="margin-bottom:16px;display:block;padding:10px">{{ session('success') }}</div>
@endif

{{-- Filter --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h2>Filter by Type</h2></div>
    <div class="card-body">
        <form method="GET" action="/admin/setting/estimate">
            <div class="form-row">
                <div class="form-group">
                    <label>Type</label>
                    <input type="text" name="type" class="form-input" value="{{ request('type') }}" placeholder="e.g. water, electricity">
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Estimates</h2>
        <span class="badge badge-blue">{{ $estimates->count() }} records</span>
    </div>
    <div class="table-wrap">
        @if($estimates->isEmpty())
            <div class="empty-state">
                <p>No estimates found{{ request('type') ? ' for type "'.request('type').'"' : '' }}.</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($estimates as $estimate)
                    <tr>
                        <td>{{ $estimate->id }}</td>
                        <td>{{ $estimate->type }}</td>
                        <td>{{ $estimate->amount ?? '—' }}</td>
                        <td class="actions">
                            <form action="/admin/setting/estimate/{{ $estimate->id }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Delete this estimate?')">Delete</button>
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
