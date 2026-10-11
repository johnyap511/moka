@extends('refund._shell')
@section('title', 'Deposit Refund Form')

@section('body')
<div class="card">
    <h1>MOKA Deposit Refund Form</h1>
    <p>Dear Guest, it is our pleasure to host you at our home. We will always provide the best in order for you to feel as comfortable as your home away from home. We hope that you are satisfied with our services, and choose MOKA as your preferred accommodation provider for your next trip. Hope to see you again soon!</p>
    <div class="notice"><b>Kind reminder</b>
        <ol>
            <li>Please submit this form before <b>3pm on the day of check-out</b> to speed up your refund.</li>
            <li>Your security deposit will be refunded within <b>3 working days</b> from the day you submit this form.</li>
            <li>Unclaimed security deposits are forfeited if not claimed within one year of your check-out date.</li>
        </ol>
    </div>
</div>

<form method="post" action="{{ route('refund.store', $link->token) }}" novalidate>
    @csrf
    <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

    <div class="card">
        <h2>Your stay</h2>
        <div class="field">
            <label for="name">Name as per reservation <small>Example: Wong Xin Rou</small></label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="name">
            @error('name')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="email">Email <small>Example: xinrou@gmail.com</small></label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email">
            @error('email')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="phone">Contact number <small>The number used for your booking. Example: 019-6668126</small></label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required autocomplete="tel">
            @error('phone')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="property">Property</label>
            <select id="property" name="property" required>
                <option value="" disabled {{ old('property') ? '' : 'selected' }}>Choose the property</option>
                @foreach($properties as $p)
                <option value="{{ $p }}" {{ old('property') === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
            @error('property')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="unit_no">Unit number <small>Example: E-23A-01</small></label>
            <input type="text" id="unit_no" name="unit_no" value="{{ old('unit_no') }}" required autocapitalize="characters">
            @error('unit_no')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="check_out">Date of check-out</label>
            <input type="date" id="check_out" name="check_out" value="{{ old('check_out', date('Y-m-d')) }}" max="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
            @error('check_out')<div class="err">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="card">
        <h2>Refund to</h2>
        <div class="field">
            <label for="bank_name">Bank name <small>Example: Public Bank</small></label>
            <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name') }}" required>
            @error('bank_name')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="bank_holder">Bank account holder name <small>Please make sure the account name matches the person who paid the deposit.</small></label>
            <input type="text" id="bank_holder" name="bank_holder" value="{{ old('bank_holder') }}" required>
            @error('bank_holder')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="bank_account">Bank account number <small>Example: 24637958947</small></label>
            <input type="text" id="bank_account" name="bank_account" value="{{ old('bank_account') }}" inputmode="numeric" required>
            @error('bank_account')<div class="err">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="card">
        <h2>How did we do?</h2>
        @foreach([['rating_clean', 'How would you rate our room cleanliness?'], ['rating_service', 'How would you rate our customer service?']] as [$f, $q])
        <div class="field">
            <label>{{ $q }}</label>
            <div class="scale"><span>Very poor</span>
                <div class="opts">
                    @for($i = 1; $i <= 5; $i++)
                    <label style="margin:0;flex:1"><input type="radio" name="{{ $f }}" value="{{ $i }}" {{ (int) old($f) === $i ? 'checked' : '' }} required><span class="opt">{{ $i }}</span></label>
                    @endfor
                </div>
                <span>Excellent</span>
            </div>
            @error($f)<div class="err">{{ $message }}</div>@enderror
        </div>
        @endforeach
        <div class="field">
            <label for="comments">Comments <small>We appreciate your time, so we can keep improving. Thank you!</small></label>
            <textarea id="comments" name="comments">{{ old('comments') }}</textarea>
        </div>
    </div>

    @if($staff->isNotEmpty())
    <div class="card">
        <h2>Who served you?</h2>
        <p class="muted">Optional. Pick the team member who looked after you and tell us how they did. Compliments reach them directly.</p>
        <div class="field">
            <label for="staff_user_id">Team member</label>
            <select id="staff_user_id" name="staff_user_id">
                <option value="">— Not sure / skip —</option>
                @foreach($staff as $s)
                <option value="{{ $s->id }}" {{ (int) old('staff_user_id') === (int) $s->id ? 'selected' : '' }}>{{ trim($s->name . ' ' . $s->last_name) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label>Their service</label>
            <div class="scale"><span>Very poor</span>
                <div class="opts">
                    @for($i = 1; $i <= 5; $i++)
                    <label style="margin:0;flex:1"><input type="radio" name="staff_rating" value="{{ $i }}" {{ (int) old('staff_rating') === $i ? 'checked' : '' }}><span class="opt">{{ $i }}</span></label>
                    @endfor
                </div>
                <span>Excellent</span>
            </div>
        </div>
        <div class="field">
            <label for="staff_comment">Compliment or feedback for them</label>
            <textarea id="staff_comment" name="staff_comment">{{ old('staff_comment') }}</textarea>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="card" style="border:1px solid #fecaca;background:#fef2f2"><b>Please check the highlighted fields above.</b></div>
    @endif

    <button class="btn" type="submit">Submit refund request</button>
    <p class="muted center" style="margin-top:10px">Your bank details are encrypted and seen only by our Finance team.</p>
</form>
@endsection
