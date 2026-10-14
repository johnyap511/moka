<?php

namespace App\Http\Controllers;

use App\Models\RefundLink;
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
            'bank_account'   => ['required', 'string', 'max:40', 'regex:/^[\d\s-]{6,40}$/'],
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
            'check_out.after'    => 'Please check the check-out date.',
            'check_out.before'   => 'Please check the check-out date.',
            'staff_user_id.in'   => 'Please pick a team member from the list.',
        ]);

        Refunds::submitMany($in, array_merge([$in['unit_no']], array_values($in['units'] ?? [])), $link, $request->ip());

        return redirect()->route('refund.thanks');
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
