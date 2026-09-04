@extends('admin.layout')
@section('title', 'Edit Booking')
@section('page-title', 'Edit Booking')

@php
    $ezee    = $book->ezeeBooking && (int) $book->ezeeBooking->status !== 1 ? $book->ezeeBooking : null;
    $listing = $book->listing;
    $nights  = max(0, (int) \Carbon\Carbon::parse($book->check_in)->diffInDays(\Carbon\Carbon::parse($book->check_out)));
    $channel = \App\Support\Channel::canonical($ezee->Source ?? $book->source);
    // Every piece of this stay: same MOKA folio, in the same unit or with touching dates.
    $pieces = \App\Booking::withoutGlobalScopes()->with('listing')->where('status', '<>', 1)
        ->where(fn ($q) => $q->where('id', $book->id)->orWhere(fn ($w) => $w->where('folio_no', $book->folio_no)->where('folio_no', '<>', '')
            ->where(fn ($x) => $x->where('listing_id', $book->listing_id)->orWhere('check_in', $book->check_out)->orWhere('check_out', $book->check_in))))
        ->orderBy('check_in')->get();
    $units = \App\Listing::orderBy('name')->get(['id', 'name']);
    $fmt   = fn ($d) => \Carbon\Carbon::parse($d)->format('D d M Y');
@endphp

@push('styles')
<style>
.eb-strip{display:flex;flex-wrap:wrap;gap:8px 22px;align-items:center;padding:14px 20px;border-bottom:1px solid var(--border);font-size:13px}
.eb-strip .k{color:var(--text-secondary);font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:2px}
.eb-strip .v{font-weight:600}
.eb-note{display:flex;gap:10px;align-items:flex-start;padding:12px 20px;background:#fffbeb;border-bottom:1px solid #fde68a;color:#92400e;font-size:13px;line-height:1.5}
.eb-note button{margin-left:auto;white-space:nowrap}
.eb-section{padding:18px 20px 4px}
.eb-section + .eb-section{border-top:1px solid var(--border)}
.eb-section h3{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);margin:0 0 14px}
.eb-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0 18px}
.eb-grid .span2{grid-column:span 2}
.eb-grid .span4{grid-column:1 / -1}
.eb-hint{font-size:11.5px;color:var(--text-secondary);margin-top:4px}
.eb-total{display:flex;flex-wrap:wrap;gap:6px 14px;align-items:baseline;padding:12px 14px;border:1px dashed var(--border);border-radius:8px;background:#f9fafb;font-size:13px;margin:0 20px 16px}
.eb-total b{font-size:18px}
.eb-actions{position:sticky;bottom:0;display:flex;gap:10px;padding:12px 20px;background:var(--surface);border-top:1px solid var(--border);z-index:2}
.eb-locked .form-input[readonly]{background:#f3f4f6;color:#6b7280}
.eb-pieces{overflow-x:auto;margin-bottom:14px}
.eb-pieces table{width:100%;min-width:520px;font-size:12.5px}
.eb-pieces tr.cur td{font-weight:600;background:var(--teal-light)}
.eb-move{display:grid;grid-template-columns:1fr auto;gap:10px;align-items:end;max-width:520px}
.eb-split{display:grid;grid-template-columns:1fr 1fr 1.4fr auto;gap:10px;align-items:end}
.eb-split label,.eb-move label{display:grid;gap:4px;font-size:12.5px;color:var(--text-secondary)}
.eb-split label .form-input,.eb-move label .form-input{font-size:13px;padding:8px 10px}
.eb-quick{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px;align-items:center;font-size:12.5px;color:var(--text-secondary)}
#split-summary{margin-top:10px;font-size:13px;font-weight:600;min-height:18px}
@media (max-width:1100px){.eb-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.eb-grid .span2{grid-column:span 2}}
@media (max-width:700px){
  .eb-grid,.eb-split,.eb-move{grid-template-columns:1fr}
  .eb-grid .span2,.eb-grid .span4{grid-column:auto}
  .eb-strip{gap:10px 16px;padding:12px 14px}
  .eb-section,.eb-actions{padding-left:14px;padding-right:14px}
  .eb-total{margin:0 14px 14px}
  .eb-actions .btn{flex:1 1 auto;justify-content:center}
  .eb-split .btn,.eb-move .btn{width:100%;justify-content:center}
  .eb-quick .btn{flex:1 1 45%;justify-content:center}
  .eb-note{flex-direction:column}
  .eb-note button{margin-left:0;width:100%}
}
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1>Edit Booking #{{ $book->id }}</h1>
        <p>{{ $listing->name ?? 'No unit' }} · {{ $fmt($book->check_in) }} → {{ $fmt($book->check_out) }} · {{ $nights }} night{{ $nights == 1 ? '' : 's' }}</p>
    </div>
    <div class="flex gap-2">
        <a href="/admin/book/{{ $book->id }}" class="btn btn-secondary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-error" style="margin-bottom:16px">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-error" style="margin-bottom:16px">
        <ul style="margin:0;padding-left:18px">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="card {{ $ezee ? 'eb-locked' : '' }}" id="edit-card">
    {{-- What this booking is, at a glance --}}
    <div class="eb-strip">
        <div><span class="k">Guest</span><span class="v">{{ trim(($ezee->FirstName ?? $user->name ?? '') . ' ' . ($ezee->LastName ?? $user->last_name ?? '')) ?: '—' }}</span></div>
        <div><span class="k">Unit</span><span class="v">{{ $listing->name ?? '—' }}</span></div>
        <div><span class="k">Channel</span><span class="badge badge-blue">{{ $channel ?: '—' }}</span></div>
        <div><span class="k">Folio</span><span class="v">{{ $ezee->folio_no ?? $book->server_folio_no ?? $book->folio_no ?? '—' }}</span></div>
        <div><span class="k">eZee</span>
            @if($ezee)
                <span class="badge badge-teal">{{ $ezee->SubBookingId }} · {{ $ezee->RoomName }} · {{ $ezee->ezee_current_status ?: $ezee->ezee_status }}</span>
            @else
                <span class="badge badge-gray">MOKA-only, not linked</span>
            @endif
        </div>
        <div><span class="k">Status</span><span class="badge {{ (int) $book->status === 5 ? 'badge-green' : 'badge-gray' }}">{{ (int) $book->status === 5 ? 'Confirmed' : 'Status ' . $book->status }}</span></div>
    </div>

    @if($ezee)
    <div class="eb-note" id="eb-note">
        <span>🔒</span>
        <span><b>Amounts follow eZee.</b> Rate, cleaning fee, SST and total on this booking are set from eZee's record and re-applied by the hourly sync, so a figure changed here is overwritten unless eZee changes too. To correct a figure, change it in eZee. Guest details, dates and remarks can be edited here.</span>
        <button type="button" class="btn btn-secondary btn-sm" onclick="unlockAmounts(this)">Edit amounts anyway</button>
    </div>
    @endif

    <form action="/admin/book/{{ $book->id }}" method="POST" id="edit-booking-form">
        @csrf
        @method('PUT')

        <div class="eb-section">
            <h3>Guest</h3>
            <div class="eb-grid">
                <div class="form-group">
                    <label class="form-label">First name</label>
                    <input type="text" name="name" class="form-input" value="{{ old('name', $user->name ?? '') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Last name</label>
                    <input type="text" name="last_name" class="form-input" value="{{ old('last_name', $user->last_name ?? '') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="{{ old('email', $user->email ?? '') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-input" value="{{ old('phone', $user->phone ?? '') }}">
                </div>
            </div>
        </div>

        <div class="eb-section">
            <h3>Stay</h3>
            <div class="eb-grid">
                <div class="form-group">
                    <label class="form-label">Check in</label>
                    <input type="date" name="check_in" class="form-input" value="{{ old('check_in', $book->check_in) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Check out</label>
                    <input type="date" name="check_out" class="form-input" value="{{ old('check_out', $book->check_out) }}" required>
                    <div class="eb-hint" id="nights-hint">{{ $nights }} night{{ $nights == 1 ? '' : 's' }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Adults</label>
                    <input type="number" name="adult" class="form-input" min="0" value="{{ old('adult', $book->adult ?? 1) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Infants</label>
                    <input type="number" name="infant" class="form-input" min="0" value="{{ old('infant', $book->infant ?? 0) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Folio no.</label>
                    <input type="text" name="folio_no" class="form-input" value="{{ old('folio_no', $book->folio_no) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Reservation source</label>
                    <select name="source" class="form-input">
                        @foreach(\App\Support\BookingOptions::SOURCES as $s)
                            <option value="{{ $s }}" {{ old('source', $book->source) === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                        @if($book->source && !in_array($book->source, \App\Support\BookingOptions::SOURCES, true))
                            <option value="{{ $book->source }}" selected>{{ $book->source }}</option>
                        @endif
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Booking category</label>
                    <select name="category" class="form-input">
                        @foreach(\App\Support\BookingOptions::CATEGORIES as $c)
                            <option value="{{ $c }}" {{ old('category', $book->category) === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="eb-section">
            <h3>Charges (RM)</h3>
            <div class="eb-grid">
                <div class="form-group">
                    <label class="form-label">Rate per night</label>
                    <input type="number" step="0.01" name="price_night" class="form-input" value="{{ old('price_night', $book->price_night) }}" {{ $ezee ? 'readonly' : '' }}>
                    <div class="eb-hint">Before tax</div>
                </div>
                <div class="form-group">
                    <label class="form-label">SST</label>
                    <input type="number" step="0.01" name="sst" class="form-input" value="{{ old('sst', $book->sst) }}" {{ $ezee ? 'readonly' : '' }}>
                    <div class="eb-hint">8% of room charge</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Cleaning fee</label>
                    <input type="number" step="0.01" name="cleaning_fee" class="form-input" value="{{ old('cleaning_fee', $book->cleaning_fee) }}" {{ $ezee ? 'readonly' : '' }}>
                    <div class="eb-hint">On the first night of the stay</div>
                </div>
                <div class="form-group">
                    <label class="form-label">SST on cleaning</label>
                    <input type="number" step="0.01" name="sst_cf" class="form-input" value="{{ old('sst_cf', $book->sst_cf) }}" {{ $ezee ? 'readonly' : '' }}>
                    <div class="eb-hint">Only where eZee taxed it</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Discount</label>
                    <input type="number" step="0.01" name="discount_fee" class="form-input" value="{{ old('discount_fee', $book->discount_fee) }}" {{ $ezee ? 'readonly' : '' }}>
                </div>
                <div class="form-group">
                    <label class="form-label">M&amp;A fee</label>
                    <input type="number" step="0.01" name="ota_fee" class="form-input" value="{{ old('ota_fee', $book->ota_fee) }}" {{ $ezee ? 'readonly' : '' }}>
                    <div class="eb-hint">Shown as OTA fee in exports; not part of the total</div>
                </div>
                <div class="form-group span2">
                    <label class="form-label">Total</label>
                    <input type="number" step="0.01" name="price" class="form-input" value="{{ old('price', $book->price) }}" {{ $ezee ? 'readonly' : '' }}>
                    <div class="eb-hint">Rate × nights + SST + cleaning + SST on cleaning − discount</div>
                </div>
                <div class="form-group span4">
                    <label class="form-label">Remark</label>
                    <textarea name="remark" class="form-input" rows="2">{{ old('remark', $book->remark ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="eb-actions">
            <button type="submit" class="btn btn-primary">Update booking</button>
            <a href="/admin/book" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-header"><h2>Unit &amp; room moves</h2></div>
    <div class="card-body" style="font-size:13px">
        <div class="eb-pieces">
            <table>
                <thead><tr><th>Booking</th><th>Unit</th><th>Check in</th><th>Check out</th><th>Nights</th><th>Total</th></tr></thead>
                <tbody>
                @foreach($pieces as $s)
                    <tr class="{{ $s->id == $book->id ? 'cur' : '' }}"><td>#{{ $s->id }}{{ $s->id == $book->id ? ' (this)' : '' }}</td><td>{{ $s->listing->name ?? '' }}</td><td>{{ $s->check_in }}</td><td>{{ $s->check_out }}</td><td>{{ $s->nights }}</td><td>RM {{ number_format($s->price, 2) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="eb-section" style="padding:0 0 14px">
            <h3>Reassign: move this booking to another unit as it is</h3>
            <div class="eb-move">
                <label>Unit
                    <select id="move-unit" class="form-input">
                        @foreach($units as $u)<option value="{{ $u->id }}" @if($u->id == $book->listing_id) selected @endif>{{ $u->name }}</option>@endforeach
                    </select>
                </label>
                <button type="button" class="btn btn-secondary" onclick="moveBooking(this)">Reassign</button>
            </div>
        </div>

        <div class="eb-section" style="padding:14px 0 0;border-top:1px solid var(--border)">
            <h3>Split: move some nights to another unit (room move)</h3>
            <div class="eb-split">
                <label>Nights from (check-in)<input type="date" id="split-from" class="form-input" value="{{ $book->check_in }}" min="{{ $book->check_in }}" max="{{ $book->check_out }}" oninput="splitSummary()"></label>
                <label>to (check-out)<input type="date" id="split-to" class="form-input" value="{{ \Carbon\Carbon::parse($book->check_in)->addDay()->format('Y-m-d') }}" min="{{ $book->check_in }}" max="{{ $book->check_out }}" oninput="splitSummary()"></label>
                <label>Where
                    <select id="split-unit" class="form-input">
                        <option value="">Extra room (no unit)</option>
                        @foreach($units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                    </select>
                </label>
                <button type="button" class="btn btn-primary" onclick="splitStay(this)">Move those nights</button>
            </div>
            <div class="eb-quick">
                <span>Quick pick:</span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="splitPick('first')">First night</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="splitPick('last')">Last night</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="splitPick('from2')">All but the first</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="splitPick('butlast')">All but the last</button>
            </div>
            <div id="split-summary"></div>
            <div class="eb-hint" style="margin-top:6px">Amounts follow the stamped nightly rate, cleaning stays with the first night, the channel fee follows the nights. A clash with another booking is refused before anything is saved.</div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function unlockAmounts(btn) {
    if (!confirm('Amounts on an eZee-linked booking are re-applied by the hourly sync.\n\nEdit them here anyway?')) { return; }
    document.querySelectorAll('#edit-booking-form .form-input[readonly]').forEach(function (el) { el.removeAttribute('readonly'); });
    document.getElementById('edit-card').classList.remove('eb-locked');
    btn.disabled = true; btn.textContent = 'Amounts unlocked';
}
(function () {
    var form = document.getElementById('edit-booking-form'), ci = form.querySelector('[name=check_in]'), co = form.querySelector('[name=check_out]'), hint = document.getElementById('nights-hint');
    function upd() {
        var n = ci.value && co.value ? Math.round((new Date(co.value + 'T00:00:00') - new Date(ci.value + 'T00:00:00')) / 86400000) : 0;
        hint.textContent = n > 0 ? n + ' night' + (n === 1 ? '' : 's') : 'Check-out must be after check-in';
        hint.style.color = n > 0 ? '' : '#b91c1c';
    }
    ci.addEventListener('input', upd); co.addEventListener('input', upd);
})();
async function postJson(btn, url, body) {
    btn.disabled = true;
    try {
        const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: JSON.stringify(body) });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) { throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Request failed')); }
        alert(data.message); window.location.reload();
    } catch (e) { alert('Not done: ' + e.message); btn.disabled = false; }
}
function moveBooking(btn) {
    var sel = document.getElementById('move-unit');
    if (!sel.value) { alert('Pick the unit.'); return; }
    if (sel.value == '{{ $book->listing_id }}') { alert('The booking is already in that unit.'); return; }
    if (!confirm('Move booking #{{ $book->id }} ({{ $book->check_in }} to {{ $book->check_out }}) to ' + sel.options[sel.selectedIndex].textContent + '?')) { return; }
    postJson(btn, '/admin/booking/{{ $book->id }}/reassign', { listing_id: sel.value });
}
var stayIn = '{{ $book->check_in }}', stayOut = '{{ $book->check_out }}';
function addDays(d, n) { var x = new Date(d + 'T00:00:00'); x.setDate(x.getDate() + n); return x.toISOString().slice(0, 10); }
function nightsBetween(a, b) { return Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000); }
function splitPick(which) {
    var f = document.getElementById('split-from'), t = document.getElementById('split-to');
    if (which === 'first')   { f.value = stayIn; t.value = addDays(stayIn, 1); }
    if (which === 'last')    { f.value = addDays(stayOut, -1); t.value = stayOut; }
    if (which === 'from2')   { f.value = addDays(stayIn, 1); t.value = stayOut; }
    if (which === 'butlast') { f.value = stayIn; t.value = addDays(stayOut, -1); }
    splitSummary();
}
function splitSummary() {
    var f = document.getElementById('split-from').value, t = document.getElementById('split-to').value, el = document.getElementById('split-summary');
    var n = (f && t) ? nightsBetween(f, t) : 0, total = nightsBetween(stayIn, stayOut);
    if (n <= 0 || f < stayIn || t > stayOut) { el.textContent = 'Pick a check-in and check-out inside ' + stayIn + ' to ' + stayOut + '.'; el.style.color = '#b91c1c'; return; }
    el.style.color = '';
    if (n >= total) { el.textContent = 'That is the whole booking (' + n + ' night' + (n > 1 ? 's' : '') + '). Use Reassign instead.'; return; }
    var stays = [];
    if (f > stayIn) stays.push(stayIn + ' to ' + f + ' (' + nightsBetween(stayIn, f) + ')');
    if (t < stayOut) stays.push(t + ' to ' + stayOut + ' (' + nightsBetween(t, stayOut) + ')');
    el.textContent = 'Moves ' + n + ' night' + (n > 1 ? 's' : '') + ': ' + f + ' to ' + t + '. Stays here: ' + stays.join(' and ') + '.';
}
document.addEventListener('DOMContentLoaded', splitSummary);
function splitStay(btn) {
    var from = document.getElementById('split-from').value, to = document.getElementById('split-to').value, unit = document.getElementById('split-unit').value;
    if (!from || !to || to <= from) { alert('Pick the check-in and check-out of the nights to move.'); return; }
    if (nightsBetween(from, to) >= nightsBetween(stayIn, stayOut)) { alert('That is the whole booking. Use Reassign instead.'); return; }
    if (!confirm('Move ' + from + ' to ' + to + ' to ' + (unit ? document.querySelector('#split-unit option:checked').textContent : 'an extra room (no unit)') + '?')) { return; }
    postJson(btn, '/admin/booking/{{ $book->id }}/split', { from: from, to: to, listing_id: unit || null });
}
</script>
{{-- Rates changed over time, so an old booking must recalculate against the
     date it was created, not today. --}}
@include('admin.listing.book._fees', [
    'formId'   => 'edit-booking-form',
    'bookedOn' => optional($book->created_at)->format('Y-m-d') ?? date('Y-m-d'),
])
@endpush
