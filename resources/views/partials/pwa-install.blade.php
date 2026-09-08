{{--
    "Add MOKA to your home screen" hint for phones, plus service-worker registration.
    Android: waits for the browser's install prompt and offers an Install button.
    iPhone: shows the Share > Add to Home Screen steps. Hidden once installed, and for
    30 days after "Not now". Force for testing with ?a2hs=ios or ?a2hs=android.
--}}
<style>
.a2hs{position:fixed;left:12px;right:12px;bottom:12px;z-index:9000;background:#fff;color:#1d2b2a;border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.28);padding:18px 18px 16px;font-family:-apple-system,"Segoe UI",Roboto,sans-serif;display:none;animation:a2hs-in .35s ease}
.a2hs.show{display:block}
@keyframes a2hs-in{from{transform:translateY(24px);opacity:0}to{transform:none;opacity:1}}
.a2hs__row{display:flex;gap:14px;align-items:center}
.a2hs__row img{width:52px;height:52px;border-radius:13px;flex:none}
.a2hs__row b{display:block;font-size:16px;color:#004a49;margin-bottom:2px}
.a2hs__row span{font-size:14px;color:#5c6b6a;line-height:1.4}
.a2hs__steps{margin:14px 0 0;padding:12px 14px;background:#faf8f4;border-radius:12px;font-size:14px;line-height:1.55;color:#33403f}
.a2hs__steps svg{width:18px;height:18px;vertical-align:-4px;margin:0 2px;stroke:#004a49;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.a2hs__actions{display:flex;gap:10px;margin-top:14px}
.a2hs__actions button{flex:1;border:0;border-radius:999px;padding:12px 16px;font-size:15px;font-weight:700;cursor:pointer}
.a2hs__go{background:#ff6b35;color:#fff}
.a2hs__later{background:#eef2f1;color:#004a49}
@media (min-width:768px){.a2hs{display:none !important}}
</style>
<div class="a2hs" id="a2hs" role="dialog" aria-label="Add MOKA to your home screen">
    <div class="a2hs__row">
        <img src="{{ asset('images/app/icon-192.png') }}" alt="">
        <div><b>Add MOKA to your home screen</b><span>Open your bookings with one tap, like an app.</span></div>
    </div>
    <div class="a2hs__steps" id="a2hs-ios" hidden>
        Tap <svg viewBox="0 0 24 24"><path d="M12 3v12"/><path d="M8 7l4-4 4 4"/><path d="M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg> <b>Share</b> at the bottom of Safari, then choose <b>Add to Home Screen</b>.
    </div>
    <div class="a2hs__actions">
        <button type="button" class="a2hs__later" id="a2hs-later">Not now</button>
        <button type="button" class="a2hs__go" id="a2hs-go" hidden>Install</button>
    </div>
</div>
<script>
(function () {
    if ('serviceWorker' in navigator) { navigator.serviceWorker.register('/sw.js').catch(function () {}); }
    var box = document.getElementById('a2hs'); if (!box) return;
    var force = new URLSearchParams(location.search).get('a2hs');
    var standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    var ua = navigator.userAgent;
    var ios = /iPhone|iPad|iPod/.test(ua) && !window.MSStream;
    var dismissedAt = 0; try { dismissedAt = parseInt(localStorage.getItem('moka-a2hs-dismissed') || '0', 10); } catch (e) {}
    var recentlyDismissed = Date.now() - dismissedAt < 30 * 24 * 3600 * 1000;
    if (!force && (standalone || recentlyDismissed)) return;
    function dismiss() { box.classList.remove('show'); try { localStorage.setItem('moka-a2hs-dismissed', String(Date.now())); } catch (e) {} }
    document.getElementById('a2hs-later').addEventListener('click', dismiss);
    if (ios || force === 'ios') {
        document.getElementById('a2hs-ios').hidden = false;
        setTimeout(function () { box.classList.add('show'); }, 1200);
        return;
    }
    var deferred = null;
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault(); deferred = e;
        document.getElementById('a2hs-go').hidden = false;
        setTimeout(function () { box.classList.add('show'); }, 1200);
    });
    if (force === 'android') { document.getElementById('a2hs-go').hidden = false; box.classList.add('show'); }
    document.getElementById('a2hs-go').addEventListener('click', function () {
        if (!deferred) { dismiss(); return; }
        deferred.prompt();
        deferred.userChoice.then(function () { deferred = null; dismiss(); });
    });
    window.addEventListener('appinstalled', dismiss);
})();
</script>
