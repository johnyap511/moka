@extends('refund._shell')
@section('title', 'Deposit Refund Form')

@section('body')
@php $e = fn ($f) => $errors->has($f) ? 'is-err' : ''; @endphp
<div class="card">
    <h1>Deposit refund request</h1>
    <p class="muted" style="margin:0 0 4px">Thank you for staying with MOKA. Tell us where to send your deposit.</p>
    <ul class="tips">
        <li>Submit before <b>3pm</b> on check-out day</li>
        <li>Refund within <b>3 working days</b></li>
        <li>Unclaimed after 1 year is forfeited</li>
    </ul>
</div>

<div class="steps"><span>1 · Your stay</span><span>2 · Bank details</span><span>3 · Your feedback</span><span>4 · Who served you</span></div>

@if($errors->any())
<div class="alert">Some details need a second look. Please check the fields marked in red.</div>
@endif

<form method="post" action="{{ route('refund.store', $link->token) }}" novalidate id="rf">
    @csrf
    <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

    <div class="card">
        <h2><span class="n">1</span>Your stay</h2>
        <div class="field">
            <label for="name"><span class="req">Name as per reservation</span></label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" class="{{ $e('name') }}" required autocomplete="name" maxlength="120">
            @error('name')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="phone"><span class="req">Contact number</span> <small>Used for your booking, e.g. 012-345 6789</small></label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" class="{{ $e('phone') }}" required autocomplete="tel" inputmode="tel" maxlength="40">
            @error('phone')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="email"><span class="req">Email</span></label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" class="{{ $e('email') }}" required autocomplete="email" inputmode="email" maxlength="150">
            @error('email')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="property"><span class="req">Property</span></label>
            <select id="property" name="property" class="{{ $e('property') }}" required>
                <option value="" disabled {{ old('property') ? '' : 'selected' }}>Choose where you stayed</option>
                @foreach($properties as $p)
                <option value="{{ $p }}" {{ old('property') === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
            @error('property')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="unit_no"><span class="req">Unit number</span> <small>e.g. E-23A-01</small></label>
            <input type="text" id="unit_no" name="unit_no" value="{{ old('unit_no') }}" class="{{ $e('unit_no') }}" required autocapitalize="characters" autocomplete="off" maxlength="40" style="text-transform:uppercase">
            @error('unit_no')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="check_out"><span class="req">Check-out date</span></label>
            <input type="date" id="check_out" name="check_out" value="{{ old('check_out', date('Y-m-d')) }}" class="{{ $e('check_out') }}" max="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
            @error('check_out')<div class="err">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="card">
        <h2><span class="n">2</span>Refund to</h2>
        <div class="field">
            <label for="bank_name"><span class="req">Bank name</span></label>
            <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name') }}" class="{{ $e('bank_name') }}" required maxlength="80" list="banks">
            <datalist id="banks"><option>Maybank</option><option>CIMB Bank</option><option>Public Bank</option><option>RHB Bank</option><option>Hong Leong Bank</option><option>AmBank</option><option>Bank Islam</option><option>Bank Rakyat</option><option>OCBC Bank</option><option>UOB</option><option>HSBC</option><option>Standard Chartered</option><option>Affin Bank</option><option>Alliance Bank</option><option>Touch 'n Go eWallet</option></datalist>
            @error('bank_name')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="bank_holder"><span class="req">Account holder name</span> <small>Must match who paid the deposit</small></label>
            <input type="text" id="bank_holder" name="bank_holder" value="{{ old('bank_holder') }}" class="{{ $e('bank_holder') }}" required maxlength="120">
            @error('bank_holder')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="bank_account"><span class="req">Account number</span> <small>Digits only</small></label>
            <input type="text" id="bank_account" name="bank_account" value="{{ old('bank_account') }}" class="{{ $e('bank_account') }}" inputmode="numeric" autocomplete="off" required maxlength="40">
            @error('bank_account')<div class="err">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="card">
        <h2><span class="n">3</span>Your feedback</h2>
        @foreach([['rating_clean', 'How clean was the unit?'], ['rating_service', 'How was our customer service?']] as [$f, $q])
        <div class="field">
            <label><span class="req">{{ $q }}</span></label>
            <div class="scale">
                @for($i = 1; $i <= 5; $i++)
                <label><input type="radio" name="{{ $f }}" value="{{ $i }}" {{ (int) old($f) === $i ? 'checked' : '' }} required><span class="opt">{{ $i }}</span></label>
                @endfor
            </div>
            <div class="scale-l"><span>Very poor</span><span>Excellent</span></div>
            @error($f)<div class="err">{{ $message }}</div>@enderror
        </div>
        @endforeach
        <div class="field">
            <label for="comments">Comments <small>Optional</small></label>
            <textarea id="comments" name="comments" maxlength="2000">{{ old('comments') }}</textarea>
        </div>
    </div>

    @if($staff->isNotEmpty())
    <div class="card">
        <h2><span class="n">4</span>Who served you?</h2>
        <p class="muted">Optional. Compliments reach them directly.</p>
        <div class="field">
            <label for="staff_user_id">Team member</label>
            <select id="staff_user_id" name="staff_user_id">
                <option value="">Not sure / skip</option>
                @foreach($staff as $s)
                <option value="{{ $s->id }}" {{ (int) old('staff_user_id') === (int) $s->id ? 'selected' : '' }}>{{ trim($s->name . ' ' . $s->last_name) }}</option>
                @endforeach
            </select>
        </div>
        <div id="staff-more" style="{{ old('staff_user_id') ? '' : 'display:none' }}">
            <div class="field">
                <label>How did they do?</label>
                <div class="scale">
                    @for($i = 1; $i <= 5; $i++)
                    <label><input type="radio" name="staff_rating" value="{{ $i }}" {{ (int) old('staff_rating') === $i ? 'checked' : '' }}><span class="opt">{{ $i }}</span></label>
                    @endfor
                </div>
                <div class="scale-l"><span>Very poor</span><span>Excellent</span></div>
            </div>
            <div class="field">
                <label for="staff_comment">A few words for them</label>
                <textarea id="staff_comment" name="staff_comment" maxlength="1000">{{ old('staff_comment') }}</textarea>
            </div>
        </div>
    </div>
    @endif

    @if(config('moka.turnstile_site'))
    <div class="card" style="padding:14px 18px"><div class="cf-turnstile" data-sitekey="{{ config('moka.turnstile_site') }}" data-theme="light"></div>
        @error('cf-turnstile-response')<div class="err">{{ $message }}</div>@enderror
    </div>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif

    <label class="consent"><input type="checkbox" name="marketing_opt_in" value="1" {{ old('marketing_opt_in', '1') ? 'checked' : '' }}><span>Send me MOKA offers and news by email or WhatsApp. Unsubscribe any time.</span></label>

    <button class="btn" type="submit" id="rf-submit">Submit refund request</button>
    <div class="lock"><span>🔒</span><span>Encrypted. Seen only by MOKA Finance. We never ask for OTP, passwords or payment.</span></div>
</form>

<script>
(function(){
    var f=document.getElementById('rf'),b=document.getElementById('rf-submit');
    f.addEventListener('submit',function(){b.disabled=true;b.textContent='Sending…';});
    var u=document.getElementById('unit_no');u&&u.addEventListener('input',function(){this.value=this.value.toUpperCase();});
    var s=document.getElementById('staff_user_id'),m=document.getElementById('staff-more');
    s&&s.addEventListener('change',function(){m.style.display=this.value?'':'none';});
    var first=document.querySelector('.is-err');first&&first.scrollIntoView({block:'center'});
})();
</script>
@endsection
