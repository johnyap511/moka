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

@if(session('dup'))
    @php $dup = session('dup'); @endphp
    <div class="dupbox">
        <b>We already have your request #{{ $dup['id'] }}, sent {{ $dup['when'] }}.</b>
        Refund for {{ $dup['unit'] }} is due by {{ $dup['due'] }}. There is no need to submit again, and sending twice does not make it faster.
        <label class="chk" style="margin-top:8px"><input type="checkbox" name="confirm_duplicate" value="1" form="rf"> <span>This is a different stay, submit anyway</span></label>
    </div>
@endif
@if($errors->any())
<div class="alert">Some details need a second look. Please check the fields marked in red.</div>
@endif

<form method="post" action="{{ route('refund.store', $link->token) }}" novalidate id="rf" enctype="multipart/form-data">
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
            <div id="more-units">
                @foreach((array) old('units', []) as $i => $u)
                <div class="unit-row"><label for="units_{{ $i }}">Unit {{ $loop->iteration + 1 }}</label>
                <div class="unit-in"><input type="text" id="units_{{ $i }}" name="units[{{ $i }}]" value="{{ $u }}" class="{{ $e('units.' . $i) }}" autocapitalize="characters" autocomplete="off" maxlength="40" style="text-transform:uppercase"><button type="button" class="unit-rm" aria-label="Remove this unit">&times;</button></div>
                @error('units.' . $i)<div class="err">{{ $message }}</div>@enderror</div>
                @endforeach
            </div>
            @error('units')<div class="err">{{ $message }}</div>@enderror
            <button type="button" class="addunit" id="add-unit"><b>+</b> Add another unit <small>if you stayed in more than one, up to 10</small></button>
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
            <datalist id="banks"><option>Maybank</option><option>CIMB Bank</option><option>Public Bank</option><option>RHB Bank</option><option>Hong Leong Bank</option><option>AmBank</option><option>Bank Islam</option><option>Bank Rakyat</option><option>OCBC Bank</option><option>UOB</option><option>HSBC</option><option>Standard Chartered</option><option>Affin Bank</option><option>Alliance Bank</option><option>Touch 'n Go eWallet</option><option>Alipay</option></datalist>
            @error('bank_name')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label class="chk" for="overseas" style="margin-top:8px"><input type="checkbox" id="overseas" name="overseas" value="1" {{ old('overseas') ? 'checked' : '' }}> <span>My bank is outside Malaysia</span></label>
            <div id="overseas-fields" {{ old('overseas') ? '' : 'hidden' }}>
                <div class="field" style="margin-top:12px"><label for="bank_country"><span class="req">Bank country</span></label>
                <input type="text" id="bank_country" name="bank_country" value="{{ old('bank_country') }}" class="{{ $e('bank_country') }}" maxlength="60" placeholder="e.g. Singapore, China, Australia">
                @error('bank_country')<div class="err">{{ $message }}</div>@enderror</div>
                <div class="field" style="margin-top:12px"><label for="bank_swift"><span class="req">SWIFT / BIC code</span> <small>8 or 11 characters, on your bank's website</small></label>
                <input type="text" id="bank_swift" name="bank_swift" value="{{ old('bank_swift') }}" class="{{ $e('bank_swift') }}" maxlength="20" autocapitalize="characters" style="text-transform:uppercase">
                @error('bank_swift')<div class="err">{{ $message }}</div>@enderror</div>
                <div class="field" style="margin-top:12px"><label for="bank_extra_code">Routing / sort / BSB / IFSC code <small>Only if your bank uses one</small></label>
                <input type="text" id="bank_extra_code" name="bank_extra_code" value="{{ old('bank_extra_code') }}" class="{{ $e('bank_extra_code') }}" maxlength="40">
                @error('bank_extra_code')<div class="err">{{ $message }}</div>@enderror</div>
                <div class="field" style="margin-top:12px"><label for="bank_address">Bank address <small>Optional, helps the transfer go through</small></label>
                <input type="text" id="bank_address" name="bank_address" value="{{ old('bank_address') }}" class="{{ $e('bank_address') }}" maxlength="200">
                @error('bank_address')<div class="err">{{ $message }}</div>@enderror</div>
            </div>
        </div>
        <div class="field">
            <label for="bank_holder"><span class="req">Account holder name</span> <small>Must match who paid the deposit</small></label>
            <input type="text" id="bank_holder" name="bank_holder" value="{{ old('bank_holder') }}" class="{{ $e('bank_holder') }}" required maxlength="120">
            @error('bank_holder')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="bank_account"><span class="req" id="acct-req">Account number</span> <small id="acct-hint">Digits only</small></label>
            <input type="text" id="bank_account" name="bank_account" value="{{ old('bank_account') }}" class="{{ $e('bank_account') }}" inputmode="numeric" autocomplete="off" required maxlength="40">
            @error('bank_account')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field" id="qr-field" hidden>
            <label for="qr">QR code <small>Alipay or Touch 'n Go: upload your receive-money QR instead of the account number</small></label>
            <input type="file" id="qr" name="qr" accept="image/png,image/jpeg,image/webp" class="{{ $e('qr') }}">
            @error('qr')<div class="err">{{ $message }}</div>@enderror
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
    var more=document.getElementById('more-units'),add=document.getElementById('add-unit'),MAX=9,seq=more.children.length;
    function relabel(){ var rows=more.querySelectorAll('.unit-row'); rows.forEach(function(r,i){ r.querySelector('label').textContent='Unit '+(i+2); }); add.style.display=rows.length>=MAX?'none':''; }
    function addRow(){ if(more.querySelectorAll('.unit-row').length>=MAX) return; var i=seq++; var d=document.createElement('div'); d.className='unit-row';
        d.innerHTML='<label for="units_'+i+'">Unit</label><div class="unit-in"><input type="text" id="units_'+i+'" name="units['+i+']" autocapitalize="characters" autocomplete="off" maxlength="40" style="text-transform:uppercase"><button type="button" class="unit-rm" aria-label="Remove this unit">&times;</button></div>';
        more.appendChild(d); relabel(); d.querySelector('input').focus(); }
    add.addEventListener('click',addRow);
    more.addEventListener('click',function(e){ var b=e.target.closest('.unit-rm'); if(b){ b.closest('.unit-row').remove(); relabel(); } });
    document.getElementById('rf').addEventListener('input',function(e){ if(e.target.matches('#unit_no, #more-units input')) e.target.value=e.target.value.toUpperCase(); });
    relabel();
    var bank = document.getElementById('bank_name'), qrf = document.getElementById('qr-field'), acct = document.getElementById('bank_account');
    function walletMode() { var w = /alipay|touch|tng|e-?wallet/i.test(bank.value); qrf.hidden = !w; acct.required = !w;
        document.getElementById('acct-req').className = w ? '' : 'req'; document.getElementById('acct-hint').textContent = w ? 'Digits only, or upload your QR code below' : 'Digits only'; }
    bank.addEventListener('input', walletMode); bank.addEventListener('change', walletMode); walletMode();
    var ov = document.getElementById('overseas'), ovf = document.getElementById('overseas-fields');
    function overseasMode() { ovf.hidden = !ov.checked; var lab = document.querySelector('label[for=bank_account] .req, label[for=bank_account] #acct-req'); if (lab) lab.textContent = ov.checked ? 'Account number or IBAN' : 'Account number'; if (!/alipay|touch|tng/i.test(bank.value)) document.getElementById('acct-hint').textContent = ov.checked ? 'Letters and digits, as printed by your bank' : 'Digits only'; }
    ov.addEventListener('change', overseasMode); overseasMode();
    var s=document.getElementById('staff_user_id'),m=document.getElementById('staff-more');
    s&&s.addEventListener('change',function(){m.style.display=this.value?'':'none';});
    var first=document.querySelector('.is-err');first&&first.scrollIntoView({block:'center'});
})();
</script>
@endsection
