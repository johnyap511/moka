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
:root{--teal:#0f766e;--teal-d:#0a5f58;--teal-l:#e6f4f1;--ink:#1f2937;--muted:#6b7280;--line:#e5e7eb;--bg:#f4f5f3;--card:#fff;--orange:#e8732a;--red:#b91c1c;--amber:#b45309}
*{box-sizing:border-box}html{-webkit-text-size-adjust:100%}
body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}
.top{background:var(--teal);color:#fff;padding:14px 16px 42px}
.top .in{max-width:560px;margin:0 auto;display:flex;align-items:center;gap:12px}
.top img{height:34px;filter:brightness(0) invert(1)}
.top .t{font-size:13px;opacity:.9;letter-spacing:.02em}
.wrap{max-width:560px;margin:-30px auto 0;padding:0 14px 56px}
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
label small{display:block}.req::after{content:" *";color:var(--red)}
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
.foot{text-align:center;font-size:13px;color:var(--muted);margin-top:22px}.foot a{color:var(--teal);text-decoration:none;font-weight:600}
.ok{width:72px;height:72px;border-radius:50%;background:#dcfce7;color:#166534;display:flex;align-items:center;justify-content:center;margin:4px auto 16px;font-size:34px}
.center{text-align:center}
.next{display:grid;gap:10px;margin-top:16px;text-align:left}.next div{display:flex;gap:10px;align-items:flex-start;font-size:14.5px}.next .n{display:inline-flex;flex:none;width:24px;height:24px;border-radius:50%;background:var(--teal-l);color:var(--teal);font-weight:700;font-size:13px;align-items:center;justify-content:center;margin-top:1px}
.alert{background:#fef2f2;border:1.5px solid #fecaca;color:var(--red);border-radius:12px;padding:12px 14px;font-size:14.5px;margin-bottom:14px}
@media (min-width:600px){.wrap{padding-bottom:80px}}
</style>
</head>
<body>
<div class="top"><div class="in"><img src="{{ asset('images/layout/logo-orange.svg') }}" alt="MOKA"><div class="t">Homestay &amp; Serviced Suites · Kuala Lumpur</div></div></div>
<div class="wrap">
    @yield('body')
    <div class="foot">MOKA Homestay · <a href="https://instagram.com/mokahomemy" rel="noopener">Instagram</a> · <a href="https://facebook.com/mokahomemy" rel="noopener">Facebook</a></div>
</div>
</body>
</html>
