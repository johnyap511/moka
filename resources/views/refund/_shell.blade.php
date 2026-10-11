<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>@yield('title', 'Deposit Refund') — MOKA</title>
<style>
:root{--teal:#0f766e;--ink:#1f2937;--muted:#6b7280;--line:#e5e7eb;--bg:#f6f4f0;--card:#fff;--orange:#e8732a}
*{box-sizing:border-box}html{-webkit-text-size-adjust:100%}
body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}
.wrap{max-width:560px;margin:0 auto;padding:20px 16px 48px}
.logo{display:flex;justify-content:center;padding:6px 0 14px}.logo img{height:44px}
.card{background:var(--card);border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.06);padding:20px 18px;margin-bottom:14px}
h1{font-size:21px;margin:0 0 8px}h2{font-size:15px;margin:0 0 12px;color:var(--teal);text-transform:uppercase;letter-spacing:.04em}
p{margin:0 0 10px}.muted{color:var(--muted);font-size:13.5px}
.notice{background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:10px 12px;font-size:13.5px;color:#7c2d12;margin-top:12px}
.notice ol{margin:6px 0 0;padding-left:18px}
label{display:block;font-weight:600;font-size:14px;margin:0 0 4px}label small{display:block;font-weight:400;color:var(--muted);font-size:12.5px}
.field{margin-bottom:16px}
input[type=text],input[type=email],input[type=tel],input[type=date],select,textarea{width:100%;padding:11px 12px;font:inherit;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--ink)}
input:focus,select:focus,textarea:focus{outline:2px solid var(--teal);outline-offset:0;border-color:var(--teal)}
textarea{min-height:84px;resize:vertical}
.err{color:#b91c1c;font-size:13px;margin-top:4px}
.scale{display:flex;gap:6px;align-items:center}.scale span{font-size:12px;color:var(--muted);width:56px}.scale span:last-child{text-align:right}
.scale .opts{display:flex;gap:6px;flex:1;justify-content:space-between}
.scale input{position:absolute;opacity:0;width:0;height:0}
.scale .opt{flex:1;text-align:center;padding:10px 0;border:1px solid var(--line);border-radius:10px;cursor:pointer;font-weight:600;background:#fff}
.scale input:checked+.opt{background:var(--teal);color:#fff;border-color:var(--teal)}
.scale input:focus-visible+.opt{outline:2px solid var(--orange)}
.btn{display:block;width:100%;padding:14px;border:0;border-radius:12px;background:var(--teal);color:#fff;font:inherit;font-weight:700;font-size:16px;cursor:pointer}
.btn:hover{background:#0a5f58}
.hp{position:absolute;left:-9999px;opacity:0;height:0;overflow:hidden}
.foot{text-align:center;font-size:12.5px;color:var(--muted);margin-top:18px}.foot a{color:var(--teal)}
.ok{width:64px;height:64px;border-radius:50%;background:#dcfce7;color:#166534;display:flex;align-items:center;justify-content:center;margin:4px auto 14px;font-size:30px}
.center{text-align:center}
</style>
</head>
<body>
<div class="wrap">
    <div class="logo"><img src="{{ asset('images/layout/logo-orange.svg') }}" alt="MOKA"></div>
    @yield('body')
    <div class="foot">MOKA Homestay · <a href="https://instagram.com/mokahomemy">Instagram</a> · <a href="https://facebook.com/mokahomemy">Facebook</a></div>
</div>
</body>
</html>
