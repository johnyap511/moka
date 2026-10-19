<?php

namespace App\Http\Controllers;

use App\Models\RefundLink;
use App\Models\RefundRequest;
use App\Support\Refunds;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Guest side of the deposit refund portal. No login; the 48-hour link is the key.
 *
 * Defences, in order: route pattern on the token, per-IP throttle (routes),
 * honeypot field, optional Cloudflare Turnstile, strict validation, per-phone
 * daily cap (App\Support\Refunds). The guest never learns anything about a
 * booking: every submission ends on the same thank-you page.
 */
class RefundController extends Controller
{
    private function link(string $token): ?RefundLink
    {
        return RefundLink::where('token', $token)->first();
    }

    public function show(string $token)
    {
        $link = $this->link($token);
        if (!$link || !$link->isValid()) {
            return response()->view('refund.expired', [], 410);
        }
        return view('refund.form', [
            'link'       => $link,
            'properties' => array_keys(Refunds::PROPERTIES),
            'staff'      => Refunds::staff(),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $link = $this->link($token);
        if (!$link || !$link->isValid()) {
            return response()->view('refund.expired', [], 410);
        }

        // Honeypot: bots fill every field; people never see this one.
        if ($request->filled('website')) {
            return redirect()->route('refund.thanks');
        }

        // Control characters have no place in any of these fields.
        $request->merge(collect($request->all())->map(fn ($v) => is_string($v) ? trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v)) : $v)->all());

        $this->verifyTurnstile($request);

        $in = $request->validate([
            'name'           => ['required', 'string', 'min:2', 'max:120'],
            'email'          => ['required', 'email:rfc', 'max:150'],
            'phone'          => ['required', 'string', 'max:40', 'regex:/^\+?[\d\s().-]{7,30}$/'],
            'property'       => ['required', 'string', 'in:' . implode(',', array_keys(Refunds::PROPERTIES))],
            'unit_no'        => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9 \/.-]{2,40}$/'],
            'units'          => ['nullable', 'array', 'max:9'],
            'units.*'        => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9 \/.-]{2,40}$/'],
            'check_out'      => ['required', 'date_format:Y-m-d', 'after:' . now()->subDays(400)->toDateString(), 'before:' . now()->addDays(2)->toDateString()],
            'bank_name'      => ['required', 'string', 'min:2', 'max:80'],
            'bank_holder'    => ['required', 'string', 'min:2', 'max:120'],
            'bank_account'   => ['required_without:qr', 'nullable', 'string', 'max:40', $request->boolean('overseas') ? 'regex:/^[A-Za-z0-9 -]{6,40}$/' : 'regex:/^[\d\s-]{6,40}$/'],
            'overseas'       => ['nullable', 'in:1'],
            'bank_country'   => ['exclude_unless:overseas,1', 'required', 'string', 'min:2', 'max:60'],
            'bank_swift'     => ['exclude_unless:overseas,1', 'required', 'string', 'regex:/^[A-Za-z0-9]{8}([A-Za-z0-9]{3})?$/'],
            'bank_extra_code' => ['exclude_unless:overseas,1', 'nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9 -]{2,40}$/'],
            'bank_address'   => ['exclude_unless:overseas,1', 'nullable', 'string', 'max:200'],
            'holder_dob'     => ['exclude_unless:overseas,1', 'required', 'string', 'max:10', function ($attr, $value, $fail) {
                $d = self::parseDob($value);
                if (!$d) { $fail('Please enter the date of birth as dd/mm/yyyy, e.g. 25/08/1990.'); return; }
                if ($d->gt(now()->subYears(16)) || $d->lt(now()->subYears(100))) { $fail('Please check the date of birth.'); }
            }],
            'confirm_duplicate' => ['nullable', 'in:1'],
            'qr'             => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'rating_clean'   => ['required', 'integer', 'between:1,5'],
            'rating_service' => ['required', 'integer', 'between:1,5'],
            'comments'       => ['nullable', 'string', 'max:2000'],
            'staff_user_id'  => ['nullable', 'integer', 'in:' . (Refunds::staff()->pluck('id')->implode(',') ?: '0')],
            'staff_rating'   => ['nullable', 'integer', 'between:1,5'],
            'staff_comment'  => ['nullable', 'string', 'max:1000'],
            'marketing_opt_in' => ['nullable', 'in:1'],
        ], [
            // Plain wording whatever the site locale; the public form must never show "validation.required".
            'required'           => 'Please fill in this field.',
            'string'             => 'Please check this field.',
            'min'                => 'Please enter at least :min characters.',
            'max'                => 'Please keep this under :max characters.',
            'email'              => 'Please enter a valid email address.',
            'integer'            => 'Please pick a rating.',
            'between'            => 'Please pick a rating from 1 to 5.',
            'in'                 => 'Please choose from the list.',
            'date_format'        => 'Please check the date.',
            'phone.regex'        => 'Please enter a phone number, e.g. 012-345 6789.',
            'unit_no.regex'      => 'Please enter the unit number as shown on your door, e.g. E-23A-01.',
            'units.*.regex'      => 'Please enter the unit number as shown on your door, e.g. E-23A-01.',
            'units.max'          => 'You can list up to 10 units in one form.',
            'bank_account.regex' => 'Please enter the account number with digits only.',
            'bank_account.required_without' => 'Enter the account number, or upload your QR code for Alipay / Touch \'n Go.',
            'qr.mimes'           => 'Please upload the QR as a JPG, PNG or WebP image.',
            'bank_account.regex' => $request->boolean('overseas') ? 'Please enter the account number or IBAN with letters and digits only.' : 'Please enter the account number with digits only.',
            'bank_country.required' => 'Tell us which country your bank is in.',
            'holder_dob.required' => 'The transfer service needs the account holder\'s date of birth.',
            'bank_swift.required' => 'Please enter your bank\'s SWIFT / BIC code.',
            'bank_swift.regex'   => 'A SWIFT / BIC code has 8 or 11 letters and digits, e.g. DBSSSGSG.',
            'bank_extra_code.regex' => 'Please check the routing / sort / IFSC code.',
            'qr.max'             => 'The QR image is too large; please keep it under 5 MB.',
            'check_out.after'    => 'Please check the check-out date.',
            'check_out.before'   => 'Please check the check-out date.',
            'staff_user_id.in'   => 'Please pick a team member from the list.',
        ]);

        // A repeat of the same stay within 7 days is almost always a guest unsure the first one
        // went through (19 Oct 2026: 5 of the first 100 requests). Stop and show them, unless
        // they tick "different stay".
        if (!$request->boolean('confirm_duplicate')) {
            $digits = Refunds::digits($in['phone']);
            $prior = RefundRequest::where('created_at', '>=', now()->subDays(7))->where('status', '<>', 'rejected')
                ->whereDate('check_out', $in['check_out'])->where('phone_digits', $digits)->get()
                ->first(fn ($r) => Refunds::compact($r->unit_no) === Refunds::compact($in['unit_no']));
            if ($prior) {
                return back()->withInput()->with('dup', ['id' => $prior->id, 'when' => $prior->created_at->format('j M, H:i'), 'unit' => $prior->property . ' ' . $prior->unit_no, 'due' => $prior->due_on?->format('l, j M') ?? 'the 3rd working day']);
            }
        }
        if (!$request->boolean('overseas')) {
            $in['bank_country'] = $in['bank_swift'] = $in['bank_extra_code'] = $in['bank_address'] = $in['holder_dob'] = null;
        } else {
            $in['holder_dob'] = self::parseDob($in['holder_dob'])?->toDateString();
        }
        if ($request->hasFile('qr')) {
            // Kept privately under storage/app with a random name; Finance opens it from the request.
            $in['qr_path'] = $request->file('qr')->storeAs('refund-qr', \Illuminate\Support\Str::random(32) . '.' . strtolower($request->file('qr')->getClientOriginalExtension()), 'local');
        }
        $in['bank_account'] = $in['bank_account'] ?? '';
        $made = Refunds::submitMany($in, array_merge([$in['unit_no']], array_values($in['units'] ?? [])), $link, $request->ip());

        // Receipt so the guest knows it arrived (and does not submit again). Best effort.
        try {
            if (!empty($in['email'])) {
                \Illuminate\Support\Facades\Mail::to($in['email'])->send(new \App\Mail\RefundReceived($made));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('refund.thanks')->with('refs', ['ids' => array_map(fn ($r) => $r->id, $made), 'due' => $made[0]->due_on?->format('l, j M Y'), 'email' => $in['email'] ?? null]);
    }

    /** dd/mm/yyyy, d-m-yyyy, d.m.yyyy or yyyy-mm-dd. */
    private static function parseDob(?string $v): ?\Carbon\Carbon
    {
        $v = trim((string) $v);
        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d'] as $f) {
            $d = \DateTime::createFromFormat('!' . $f, $v);
            if ($d && $d->format($f) === $v) {
                return \Carbon\Carbon::instance($d);
            }
        }
        return null;
    }

    public function thanks()
    {
        return view('refund.thanks');
    }

    /** Cloudflare Turnstile, only when both keys are configured. */
    private function verifyTurnstile(Request $request): void
    {
        $secret = config('moka.turnstile_secret');
        if (!$secret || !config('moka.turnstile_site')) {
            return;
        }
        $tokenField = 'cf-turnstile-response';
        $fail = fn () => throw ValidationException::withMessages([$tokenField => 'Please complete the "I am human" check and try again.']);
        if (!$request->filled($tokenField)) {
            $fail();
        }
        try {
            $r = Http::asForm()->timeout(6)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret, 'response' => $request->input($tokenField), 'remoteip' => $request->ip(),
            ]);
            if (!$r->ok() || !($r->json('success') === true)) {
                $fail();
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // Cloudflare unreachable: let the submission through rather than lose a real guest;
            // the per-IP and per-phone caps still apply.
            Log::warning('Turnstile verification unavailable: ' . $e->getMessage());
        }
    }
}
