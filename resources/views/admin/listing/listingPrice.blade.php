@extends('admin.layout')
@section('title', 'Listing Price')
@section('page-title', 'Listing Price')

@section('content')

<div class="page-header">
    <div>
        <h1>Listing Price</h1>
        <p>Manage custom pricing for specific dates</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/listing/{{ $id }}/edit" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Edit
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Custom Date Pricing</h2>
        <span class="badge badge-blue">{{ $dates->count() }} entries</span>
    </div>
    <div class="card-body">
        <form action="/admin/listing/{{ $id }}/price" method="POST">
            @csrf

            @if(session('success'))
                <div class="badge badge-green" style="margin-bottom:16px;display:block;padding:10px">{{ session('success') }}</div>
            @endif

            <div id="price-rows">
                @forelse($dates as $dateItem)
                <div class="form-row price-row" style="margin-bottom:10px">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="date[]" class="form-input" value="{{ $dateItem->date }}">
                    </div>
                    <div class="form-group">
                        <label>Price (RM)</label>
                        <input type="number" name="price[]" class="form-input" value="{{ $dateItem->price }}" step="0.01">
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end">
                        <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.price-row').remove()">Remove</button>
                    </div>
                </div>
                @empty
                <div class="form-row price-row" style="margin-bottom:10px">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="date[]" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Price (RM)</label>
                        <input type="number" name="price[]" class="form-input" step="0.01">
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end">
                        <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.price-row').remove()">Remove</button>
                    </div>
                </div>
                @endforelse
            </div>

            <div style="margin-bottom:20px">
                <button type="button" class="btn btn-secondary btn-sm" onclick="addRow()">+ Add Date</button>
            </div>

            <button type="submit" class="btn btn-primary">Save Pricing</button>
        </form>
    </div>
</div>

<script>
function addRow() {
    const row = `<div class="form-row price-row" style="margin-bottom:10px">
        <div class="form-group">
            <label>Date</label>
            <input type="date" name="date[]" class="form-input">
        </div>
        <div class="form-group">
            <label>Price (RM)</label>
            <input type="number" name="price[]" class="form-input" step="0.01">
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end">
            <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.price-row').remove()">Remove</button>
        </div>
    </div>`;
    document.getElementById('price-rows').insertAdjacentHTML('beforeend', row);
}
</script>

@endsection
