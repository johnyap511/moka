{{-- Cloudflare "Verify you are human" box. Renders nothing until both keys are set in .env.
     The box is 300 px wide at its smallest, and the forms on a phone are narrower than that,
     so on a narrow form it is drawn at its fixed size and scaled down to fit. --}}
@if(\App\Support\Turnstile::enabled())
    <div class="moka-ts" style="margin:0 0 16px;max-width:100%;overflow:hidden">
        <div class="moka-ts__box" data-sitekey="{{ config('moka.turnstile_site') }}"></div>
    </div>
    @once
    <script>
    window.mokaTurnstileReady = function () {
        document.querySelectorAll('.moka-ts').forEach(function (wrap) {
            var box = wrap.querySelector('.moka-ts__box');
            if (!box || box.dataset.done) { return; }
            box.dataset.done = '1';
            var opts = { sitekey: box.dataset.sitekey, theme: 'light' };
            if (wrap.clientWidth >= 300) { opts.size = 'flexible'; turnstile.render(box, opts); return; }
            // Narrow form: fixed 300 px box, scaled to the space there is, and re-fitted if that changes.
            var fit = function () {
                var s = Math.min(1, Math.max(0.7, wrap.clientWidth / 300));
                box.style.transform = s < 1 ? 'scale(' + s + ')' : 'none';
                wrap.style.height = Math.ceil(65 * s) + 'px';
            };
            box.style.width = '300px';
            box.style.transformOrigin = '0 0';
            fit();
            if ('ResizeObserver' in window) { new ResizeObserver(fit).observe(wrap); } else { window.addEventListener('resize', fit); }
            opts.size = 'normal';
            turnstile.render(box, opts);
        });
    };
    </script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=mokaTurnstileReady&render=explicit" async defer></script>
    @endonce
@endif
