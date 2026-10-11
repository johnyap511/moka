<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="no-referrer">
<meta name="theme-color" content="#0f766e">
<title>@yield('title', 'Deposit Refund') — MOKA</title>
<style>
:root{--teal:#0f766e;--teal-d:#0a5f58;--teal-l:#e6f4f1;--ink:#1f2937;--muted:#6b7280;--line:#e5e7eb;--bg:#f4f5f3;--card:#fff;--orange:#e8732a;--red:#b91c1c;--amber:#b45309;--green:#166534}
*{box-sizing:border-box}html{-webkit-text-size-adjust:100%}
body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}
.top{background:var(--teal);color:#fff;padding:12px 16px 44px}
.top .in{max-width:560px;margin:0 auto}
.top .row{display:flex;align-items:center;justify-content:space-between;gap:12px}
.top img{height:34px;filter:brightness(0) invert(1)}
.top .t{font-size:13px;opacity:.9}
.verified{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.35);border-radius:999px;padding:4px 10px;font-size:12px;white-space:nowrap}
.verified svg{width:14px;height:14px}
.domain{margin-top:10px;font-size:12.5px;opacity:.9;display:flex;gap:6px;align-items:center;flex-wrap:wrap}.domain code{background:rgba(0,0,0,.18);border-radius:6px;padding:2px 7px;font-size:12px}
.wrap{max-width:560px;margin:-30px auto 0;padding:0 14px 40px}
.card{background:var(--card);border-radius:16px;box-shadow:0 1px 2px rgba(0,0,0,.05),0 8px 24px -16px rgba(15,118,110,.35);padding:20px 18px;margin-bottom:14px}
h1{font-size:22px;line-height:1.25;margin:0 0 10px}
h2{font-size:13px;margin:0 0 14px;color:var(--teal);text-transform:uppercase;letter-spacing:.06em;display:flex;align-items:center;gap:8px}
h2 .n{display:inline-flex;width:22px;height:22px;border-radius:50%;background:var(--teal);color:#fff;align-items:center;justify-content:center;font-size:12px}
p{margin:0 0 10px}.muted{color:var(--muted);font-size:14px}
.notice{background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:12px 14px;font-size:14px;color:#78350f;margin-top:12px}
.notice > b{display:block;margin-bottom:4px}.notice ul{margin:0;padding-left:18px}.notice li{margin:3px 0}
.steps{display:flex;gap:6px;margin:0 0 14px;font-size:12px;color:var(--muted);flex-wrap:wrap}.steps span{background:#fff;border:1px solid var(--line);border-radius:999px;padding:3px 10px}
label{display:block;font-weight:600;font-size:15px;margin:0 0 5px}label small{display:block;font-weight:400;color:var(--muted);font-size:13px;margin-top:1px}
.field{margin-bottom:18px}.field:last-child{margin-bottom:4px}
.req::after{content:" *";color:var(--red)}
input[type=text],input[type=email],input[type=tel],input[type=date],select,textarea{width:100%;padding:13px 14px;font:inherit;border:1.5px solid var(--line);border-radius:12px;background:#fff;color:var(--ink);min-height:48px}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--teal);box-shadow:0 0 0 3px rgba(15,118,110,.15)}
.is-err{border-color:var(--red)!important;background:#fff7f7}
textarea{min-height:92px;resize:vertical}
select{appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236b7280' viewBox='0 0 16 16'%3E%3Cpath d='M4 6l4 4 4-4'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;padding-right:40px}
.err{color:var(--red);font-size:13.5px;margin-top:5px;display:flex;gap:5px}.err::before{content:"!";display:inline-flex;width:16px;height:16px;border-radius:50%;background:var(--red);color:#fff;font-size:11px;align-items:center;justify-content:center;flex:none;margin-top:3px}
.scale{display:flex;gap:6px;align-items:stretch}
.scale label{position:relative;flex:1;margin:0}
.scale input{position:absolute;opacity:0;width:0;height:0}
.scale .opt{display:flex;align-items:center;justify-content:center;height:48px;border:1.5px solid var(--line);border-radius:12px;cursor:pointer;font-weight:700;font-size:17px;background:#fff;transition:all .12s}
.scale input:checked+.opt{background:var(--teal);color:#fff;border-color:var(--teal);transform:translateY(-1px)}
.scale input:focus-visible+.opt{box-shadow:0 0 0 3px rgba(15,118,110,.25)}
.scale-l{display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-top:5px}
.btn{display:block;width:100%;padding:16px;border:0;border-radius:14px;background:var(--teal);color:#fff;font:inherit;font-weight:700;font-size:17px;cursor:pointer;box-shadow:0 6px 16px -8px rgba(15,118,110,.7)}
.btn:hover{background:var(--teal-d)}.btn[disabled]{opacity:.65;cursor:wait}
.hp{position:absolute;left:-9999px;opacity:0;height:0;overflow:hidden}
.lock{display:flex;gap:8px;align-items:flex-start;font-size:13px;color:var(--muted);margin-top:12px;justify-content:center}
.co{text-align:center;margin-top:14px;color:var(--muted);font-size:12.5px}.co-name{display:flex;align-items:center;justify-content:center;gap:6px;font-weight:600;color:var(--ink);font-size:13px}.co-name img{height:18px}
.co-icons{display:flex;justify-content:center;gap:10px;margin:10px 0 8px}.co-icons a{width:42px;height:42px;border-radius:50%;background:#fff;border:1px solid var(--line);display:inline-flex;align-items:center;justify-content:center;color:var(--teal)}.co-icons svg{width:20px;height:20px;fill:currentColor}
.co-foot{font-size:12px}
.tips{margin:8px 0 0;padding:0;list-style:none;display:grid;gap:6px;font-size:14.5px}.tips li{display:flex;gap:8px;align-items:flex-start}.tips li::before{content:'✓';color:var(--teal);font-weight:700;flex:none}
.ok{width:72px;height:72px;border-radius:50%;background:#dcfce7;color:#166534;display:flex;align-items:center;justify-content:center;margin:4px auto 16px;font-size:34px}
.center{text-align:center}
.next{display:grid;gap:10px;margin-top:16px;text-align:left}.next div{display:flex;gap:10px;align-items:flex-start;font-size:14.5px}.next .n{display:inline-flex;flex:none;width:24px;height:24px;border-radius:50%;background:var(--teal-l);color:var(--teal);font-weight:700;font-size:13px;align-items:center;justify-content:center;margin-top:1px}
.alert{background:#fef2f2;border:1.5px solid #fecaca;color:var(--red);border-radius:12px;padding:12px 14px;font-size:14.5px;margin-bottom:14px}
@media (min-width:600px){.wrap{padding-bottom:60px}}
</style>
</head>
<body>
<div class="top"><div class="in">
    <div class="row">
        <img src="{{ asset('images/layout/logo-orange.svg') }}" alt="MOKA">
        <span class="verified"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l7 3v6c0 5-3.5 8.5-7 11-3.5-2.5-7-6-7-11V5l7-3z"/><path d="M9 12l2 2 4-4"/></svg>Official MOKA form</span>
    </div>
    <div class="domain">Secure page · <code>homemoka.com</code></div>
</div></div>
<div class="wrap">
    @yield('body')

    <div class="co">
        <div class="co-name"><img src="{{ asset('images/layout/logo-orange.svg') }}" alt=""> Moka Venture Sdn Bhd · Petaling Jaya</div>
        <div class="co-icons">
            <a href="tel:+60367892288" aria-label="Call +603 6789 2288" title="+603 6789 2288"><svg viewBox="0 0 24 24"><path d="M6.6 10.8a15 15 0 006.6 6.6l2.2-2.2a1 1 0 011-.25 11.4 11.4 0 003.6.6 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1 11.4 11.4 0 00.6 3.6 1 1 0 01-.25 1z"/></svg></a>
            <a href="https://wa.me/message/GJMYMABOT7CSG1" rel="noopener" aria-label="WhatsApp" title="WhatsApp"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm0 18.2a8.2 8.2 0 01-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1112 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.6.8-.8 1-.3.2-.5.1a6.7 6.7 0 01-3.3-2.9c-.3-.4.3-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 00-.7.3 3 3 0 00-.9 2.2 5.2 5.2 0 001.1 2.7 11.9 11.9 0 004.5 4c1.7.7 2.3.8 3.1.7a2.7 2.7 0 001.8-1.3 2.2 2.2 0 00.1-1.2c0-.1-.2-.2-.4-.3z"/></svg></a>
            <a href="mailto:hello@homemoka.com" aria-label="Email hello@homemoka.com" title="hello@homemoka.com"><svg viewBox="0 0 24 24"><path d="M3 5h18a1 1 0 011 1v12a1 1 0 01-1 1H3a1 1 0 01-1-1V6a1 1 0 011-1zm1 2.5V17h16V7.5l-8 5-8-5zM4.6 7l7.4 4.6L19.4 7H4.6z"/></svg></a>
            <a href="https://instagram.com/mokahomemy" rel="noopener" aria-label="Instagram" title="Instagram"><svg viewBox="0 0 24 24"><path d="M7.5 2h9A5.5 5.5 0 0122 7.5v9a5.5 5.5 0 01-5.5 5.5h-9A5.5 5.5 0 012 16.5v-9A5.5 5.5 0 017.5 2zm0 2A3.5 3.5 0 004 7.5v9A3.5 3.5 0 007.5 20h9a3.5 3.5 0 003.5-3.5v-9A3.5 3.5 0 0016.5 4h-9zM12 7a5 5 0 110 10 5 5 0 010-10zm0 2a3 3 0 100 6 3 3 0 000-6zm5.3-3.5a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"/></svg></a>
            <a href="https://facebook.com/mokahomemy" rel="noopener" aria-label="Facebook" title="Facebook"><svg viewBox="0 0 24 24"><path d="M13.5 22v-8h2.7l.4-3.2h-3.1V8.8c0-.9.3-1.6 1.6-1.6h1.7V4.4a22 22 0 00-2.5-.1c-2.5 0-4.2 1.5-4.2 4.3v2.2H7.3V14h2.8v8h3.4z"/></svg></a>
        </div>
        <div class="co-foot">© {{ date('Y') }} Moka Venture Sdn Bhd · homemoka.com</div>
    </div>
</div>
</body>
</html>
