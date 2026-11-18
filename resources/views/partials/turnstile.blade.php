{{-- Cloudflare "Verify you are human" box. Renders nothing until both keys are set in .env. --}}
@if(\App\Support\Turnstile::enabled())
    <div class="cf-turnstile" data-sitekey="{{ config('moka.turnstile_site') }}" data-theme="light" data-size="flexible" style="margin:0 0 16px"></div>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
