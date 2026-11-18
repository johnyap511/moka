<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare Turnstile ("Verify you are human") for the sign-in and password-reset forms.
 * It is on only when both keys are set in .env (MOKA_TURNSTILE_SITE, MOKA_TURNSTILE_SECRET);
 * without them the forms work exactly as before. The widget itself is the Blade partial
 * partials.turnstile.
 */
class Turnstile
{
    const FIELD = 'cf-turnstile-response';
    const MESSAGE = 'Please complete the "I am human" check and try again.';

    public static function enabled(): bool
    {
        return (bool) config('moka.turnstile_site') && (bool) config('moka.turnstile_secret');
    }

    /**
     * True when the check passed or is not switched on. If Cloudflare cannot be reached the
     * visitor is let through, so an outage there never locks staff or owners out; the account
     * lock and the request limits still apply.
     */
    public static function passes(Request $request): bool
    {
        if (! self::enabled()) {
            return true;
        }
        if (! $request->filled(self::FIELD)) {
            return false;
        }
        try {
            $r = Http::asForm()->timeout(6)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('moka.turnstile_secret'), 'response' => $request->input(self::FIELD), 'remoteip' => $request->ip(),
            ]);
            return $r->ok() && $r->json('success') === true;
        } catch (\Throwable $e) {
            Log::warning('Turnstile verification unavailable: ' . $e->getMessage());
            return true;
        }
    }
}
