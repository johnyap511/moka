@extends('admin.layout')
@section('title', 'New tenancy statement')
@section('page-title', 'New tenancy statement')

@push('styles')
<style>
.tb{--accent:#0f766e;--accent-ink:#115e59;--accent-soft:#e6f4f1;--warn:#96560a;--warn-soft:#fbeedc;--neg:#a63125;--neg-soft:#fae6e2;--line:#e5e7eb;--line-strong:#cbd5e1;--muted:#64748b;--faint:#94a3b8;--ink:#111827;--surface:#fff;--surface-2:#f8fafc}
.tb *{box-sizing:border-box}
.tb-top{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;margin-bottom:12px}.tb-top h1{margin:0 0 2px;font-size:20px}.tb-top p{margin:0;font-size:12.5px;color:var(--muted);max-width:720px}
.tb-cols{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(320px,1fr);gap:16px;align-items:start}@media (max-width:980px){.tb-cols{grid-template-columns:minmax(0,1fr)}}
.tb-steps{display:flex;flex-direction:column;gap:14px}
.tb-step{background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:16px}
.tb-sh{display:grid;grid-template-columns:28px minmax(0,1fr);gap:2px 10px;align-items:center;margin-bottom:12px}.tb-sh .n{grid-row:span 2;align-self:start;width:28px;height:28px;border-radius:50%;background:var(--accent-soft);color:var(--accent-ink);font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center}.tb-sh h2{margin:0;font-size:15.5px;font-weight:700}.tb-sh p{grid-column:2;margin:0;font-size:12.5px;color:var(--muted)}
.tb .field{display:flex;flex-direction:column;gap:4px;min-width:0}.tb .lab{font-size:12px;font-weight:600;color:var(--muted)}
.tb input[type=text],.tb input[type=date],.tb input[type=number],.tb textarea{width:100%;font:400 14px/1.4 inherit;color:var(--ink);background:var(--surface-2);border:1.5px solid var(--line-strong);border-radius:8px;padding:8px 10px;min-height:40px}.tb input:focus-visible,.tb textarea:focus-visible{outline:2px solid var(--accent);outline-offset:1px;border-color:var(--accent)}
.tb input[type=number]{-moz-appearance:textfield;font-variant-numeric:tabular-nums}.tb input[type=number]::-webkit-outer-spin-button,.tb input[type=number]::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.tb .money-in{position:relative;display:block}.tb .money-in input{padding-left:36px}.tb .money-in::before{content:"RM";position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--faint);pointer-events:none}
.tb .pair{display:grid;grid-template-columns:1fr 1fr;gap:10px}@media (max-width:460px){.tb .pair{grid-template-columns:1fr}}
.tb .toggle{display:flex;align-items:flex-start;gap:10px;margin-top:12px;padding:10px 12px;border-radius:8px;background:var(--surface-2);font-size:12.5px;color:var(--muted);cursor:pointer}.tb .toggle input{margin:2px 0 0;accent-color:var(--accent);width:16px;height:16px;flex:none}.tb .toggle b{color:var(--ink)}
.tb .readout{margin-top:12px;padding:9px 12px;border-radius:8px;background:var(--accent-soft);color:var(--accent-ink);font-size:13px;font-weight:600}.tb .readout.idle{background:var(--surface-2);color:var(--muted);font-weight:400}.tb .readout.bad{background:var(--neg-soft);color:var(--neg)}
.tb .units{display:flex;flex-direction:column;gap:14px}
.tb .unit{border:1.5px solid var(--line-strong);border-radius:12px;overflow:hidden;background:var(--surface)}
.tb .unit-top{display:flex;align-items:center;gap:8px;padding:8px 8px 8px 10px;background:var(--accent-soft);border-bottom:1.5px solid var(--line-strong)}.tb .unit-badge{flex:none;width:24px;height:24px;border-radius:7px;background:var(--accent);color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center}
.tb .unit-top input.u-name{background:transparent;border:1.5px solid transparent;border-radius:7px;font-weight:700;font-size:14.5px;padding:4px 8px;min-height:34px}.tb .unit-top input.u-name:hover{border-color:var(--line-strong)}.tb .unit-top input.u-name:focus-visible{background:var(--surface)}
.tb .unit-body{padding:12px}.tb .bills-label{font-size:12px;font-weight:700;color:var(--muted);margin:14px 0 8px}
.tb .rows{display:flex;flex-direction:column;gap:10px}
.tb .bill{border:1px solid var(--line);border-radius:10px;overflow:hidden;background:var(--surface-2)}.tb .bill-top{display:flex;align-items:center;gap:6px;padding:6px 6px 6px 10px;border-bottom:1px solid var(--line)}.tb .bill-top input.b-label{background:transparent;border:1.5px solid transparent;border-radius:6px;font-weight:600;font-size:14px;padding:4px 6px;min-height:32px}.tb .bill-top input.b-label:hover{border-color:var(--line-strong)}
.tb .bill-fields{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;padding:10px}@media (max-width:700px){.tb .bill-fields{grid-template-columns:1fr 1fr}.tb .bill-fields .amt-cell{grid-column:1/-1}}
.tb .bill-out{padding:7px 10px;border-top:1px dashed var(--line);font-size:12.5px;color:var(--muted);display:grid;grid-template-columns:minmax(0,1fr) auto;gap:4px 12px;align-items:baseline}.tb .bill-out .n{font-variant-numeric:tabular-nums;font-size:13.5px;font-weight:700;color:var(--accent-ink);white-space:nowrap}.tb .bill-out.warn{background:var(--warn-soft);color:var(--warn)}.tb .bill-out.warn .n{color:var(--warn)}.tb .bill-out.off .n{color:var(--faint);font-weight:400}
.tb .addon{display:grid;grid-template-columns:minmax(0,1fr) 84px 120px 104px 32px;gap:8px;align-items:end}.tb .addon .kill{margin-bottom:4px}@media (max-width:720px){.tb .addon{grid-template-columns:1fr 1fr 32px;row-gap:8px;padding:10px;border:1px solid var(--line);border-radius:10px;background:var(--surface-2)}.tb .addon .name-cell{grid-column:1/3;grid-row:1}.tb .addon .kill{grid-column:3;grid-row:1}.tb .addon .qty-cell{grid-column:1;grid-row:2}.tb .addon .rate-cell{grid-column:2;grid-row:2}.tb .addon .amt-cell{grid-column:1/-1;grid-row:3}}
.tb .a-out{font-variant-numeric:tabular-nums;font-size:14px;font-weight:700;color:var(--accent-ink);min-height:40px;display:flex;align-items:center;justify-content:flex-end;padding:0 4px;white-space:nowrap}.tb .a-out.off{color:var(--faint);font-weight:400}
.tb .tbtn{appearance:none;font:600 13px/1 inherit;padding:10px 14px;border-radius:8px;border:1.5px solid var(--line-strong);background:var(--surface);color:var(--ink);cursor:pointer;min-height:38px}.tb .tbtn:hover{border-color:var(--ink)}.tb .tbtn.primary{background:var(--accent);border-color:var(--accent);color:#fff}.tb .tbtn.primary:hover{background:var(--accent-ink);border-color:var(--accent-ink)}.tb .tbtn.primary:disabled{opacity:.6;cursor:default}
.tb .tbtn.add{border-style:dashed;color:var(--muted);width:100%;margin-top:10px;font-weight:600}
.tb .kill{appearance:none;background:transparent;border:1.5px solid transparent;border-radius:8px;color:var(--faint);width:32px;height:32px;font-size:18px;line-height:1;cursor:pointer;flex:none}.tb .kill:hover{color:var(--neg);border-color:var(--neg)}
.tb-answer{position:sticky;top:12px}@media (max-width:980px){.tb-answer{position:static}}
.tb-card{background:var(--surface);border:1px solid var(--line);border-radius:12px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,.03),0 18px 36px -26px rgba(0,0,0,.35)}
.tb .headline{padding:16px 16px;background:var(--accent-soft);border-bottom:1px solid var(--line)}.tb .headline.owing{background:var(--neg-soft)}.tb .headline .k{font-size:12.5px;font-weight:600;color:var(--muted)}.tb .headline .v{font-size:32px;font-weight:800;font-variant-numeric:tabular-nums;letter-spacing:-.02em;line-height:1.15;margin-top:2px;color:var(--accent-ink)}.tb .headline.owing .v{color:var(--neg)}.tb .headline .sub{font-size:12.5px;color:var(--muted);margin-top:4px}
.tb .who{padding:10px 16px;border-bottom:1px solid var(--line);font-size:13px}.tb .who .m{display:block;font-size:11.5px;color:var(--muted);margin-top:1px}
.tb .lines{padding:4px 16px}.tb .grp{font-size:10.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--faint);padding:12px 0 4px}.tb .grp-unit{display:flex;justify-content:space-between;align-items:baseline;gap:6px 10px;flex-wrap:wrap;color:var(--accent-ink)}.tb .grp-unit .meta{text-transform:none;letter-spacing:0;font-weight:500;font-size:11px;color:var(--faint)}
.tb .line{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:2px 12px;padding:6px 0;border-bottom:1px solid var(--line);font-size:13px}.tb .line:last-child{border-bottom:0}.tb .line .amt{font-variant-numeric:tabular-nums;text-align:right;white-space:nowrap}.tb .line .calc{grid-column:1/-1;font-size:11px;color:var(--faint)}.tb .line.sub{border-bottom:0;padding-top:8px;border-top:1px solid var(--line-strong)}.tb .line.sub .name,.tb .line.sub .amt{font-weight:600;font-size:12px;color:var(--muted)}
.tb .empty{color:var(--faint);font-size:12.5px;padding:4px 0 10px}
.tb .totals{padding:6px 16px 14px}.tb .trow{display:flex;justify-content:space-between;align-items:baseline;gap:12px;padding:5px 0;font-size:13px;color:var(--muted)}.tb .trow .v{font-variant-numeric:tabular-nums;color:var(--ink)}.tb .trow.total{border-top:2px solid var(--ink);margin-top:4px;padding-top:9px;font-weight:700;color:var(--ink);font-size:14px}.tb .trow.total .v{font-size:18px;font-weight:800}
.tb .flags{padding:0 16px}.tb .flag{display:flex;gap:8px;align-items:flex-start;margin-bottom:8px;padding:9px 12px;border-radius:8px;font-size:12.5px;background:var(--warn-soft);color:var(--warn)}.tb .flag .dot{font-weight:800}
.tb .act{padding:0 16px 16px;display:grid;gap:8px}.tb .act .tbtn{width:100%}
.tb .issued{margin:0 16px 16px;padding:12px 14px;border-radius:10px;background:#dcfce7;color:#166534;font-size:13px;display:none}.tb .issued b{display:block;font-size:15px;margin-bottom:4px}.tb .issued a{color:#166534;font-weight:700}
.tb details.help{margin-top:16px;background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:12px 16px;font-size:13px;color:var(--muted)}.tb details.help summary{cursor:pointer;font-weight:600;color:var(--ink)}.tb details.help ol{padding-left:18px}.tb details.help li{margin:7px 0}.tb details.help code{background:var(--surface-2);border:1px solid var(--line);border-radius:4px;padding:1px 5px;font-size:12px}
.tb .toast{position:fixed;left:50%;transform:translateX(-50%);bottom:28px;background:var(--ink);color:#fff;font-size:13px;padding:10px 18px;border-radius:999px;z-index:60;box-shadow:0 10px 30px rgba(0,0,0,.25)}
.tb.locked .tb-steps{opacity:.55;pointer-events:none}
</style>
@endpush

@section('content')
<div class="tb" id="tb">
<div class="tb-top">
    <div><h1>New tenancy statement</h1><p>Key in the tenant, the units with their dates, and each unit's bills. The statement on the right updates as you type. <b>Finalise</b> numbers it, stores it and downloads the PDF.</p></div>
    <a href="{{ route('admin.tenancy.index') }}" class="btn btn-sm">← All statements</a>
</div>

<form id="tb-form" autocomplete="off">
<div class="tb-cols">
    <div class="tb-steps">
        <section class="tb-step">
            <div class="tb-sh"><div class="n">1</div><h2>Who stayed, and when</h2><p>Take the dates from the booking or the tenancy agreement.</p></div>
            <div class="pair">
                <label class="field"><span class="lab">Tenant name</span><input type="text" id="tenant" maxlength="120" placeholder="e.g. Ahmad bin Ismail"></label>
                <label class="field"><span class="lab">Booking reference (optional)</span><input type="text" id="bookref" maxlength="80" placeholder="e.g. FN31549 / FN31548"></label>
            </div>
            <div class="pair" style="margin-top:10px">
                <label class="field"><span class="lab">Prepared by</span><input type="text" id="preparedby" maxlength="80" placeholder="Your name, appears on the statement"></label>
                <label class="field"><span class="lab">Property (optional)</span><input type="text" id="property" maxlength="160" placeholder="e.g. Hyatt Regency KL Midtown residences"></label>
            </div>
            <label class="toggle" for="incl-out"><input type="checkbox" id="incl-out" checked><span><b>Charge the check-out day too.</b> Leave this ticked. Only untick it if the agreement says the tenant pays per night.</span></label>
            <div class="readout idle" id="stay-out">Add the unit and its dates in step 2 to see the total stay.</div>
        </section>

        <section class="tb-step">
            <div class="tb-sh"><div class="n">2</div><h2>Units stayed in, and their bills</h2><p>One block per unit. If the tenant changed rooms mid-stay, add a second block: each unit's bills are split against the dates for <b>that unit only</b>.</p></div>
            <div class="units" id="units"></div>
            <button class="tbtn add" id="add-unit" type="button">+ Add another unit (room change)</button>
        </section>

        <section class="tb-step">
            <div class="tb-sh"><div class="n">3</div><h2>Fixed charges, not split by days</h2><p>Key in how many and the tool multiplies. <b>Leave the quantity blank and that line isn't charged.</b></p></div>
            <div class="rows" id="addons"></div>
            <button class="tbtn add" id="add-addon" type="button">+ Add another charge</button>
            <div class="readout idle" id="months-out">Indah Water is charged per month, not by days.</div>
        </section>

        <section class="tb-step">
            <div class="tb-sh"><div class="n">4</div><h2>Deposit and other amounts</h2><p>Optional. Key the deposit in and the answer becomes how much to refund, or how much is still owed.</p></div>
            <div class="pair">
                <label class="field"><span class="lab">Security deposit held</span><span class="money-in"><input type="number" id="deposit" step="0.01" min="0" placeholder="0.00"></span></label>
                <label class="field"><span class="lab">Rent arrears or other</span><span class="money-in"><input type="number" id="arrears" step="0.01" min="0" placeholder="0.00"></span></label>
            </div>
            <label class="field" style="margin-top:10px"><span class="lab">Remarks on the statement (optional)</span><textarea id="remarks" rows="2" maxlength="1000" placeholder="e.g. Refund to be banked in within 14 working days to the tenant's nominated account."></textarea></label>
        </section>
    </div>

    <div class="tb-answer">
        <div class="tb-card" id="result" aria-live="polite">
            <div class="headline" id="headline">
                <div class="k" id="h-key">Total payable by tenant</div>
                <div class="v" id="h-val">RM 0.00</div>
                <div class="sub" id="h-sub">Key in the unit dates and bills on the left.</div>
            </div>
            <div class="who"><span id="s-who">Tenant not set</span><span class="m" id="s-when">—</span></div>
            <div class="flags" id="flags"></div>
            <div class="lines">
                <div id="s-bills"></div>
                <div class="grp">Fixed charges</div>
                <div id="s-addons"></div>
            </div>
            <div class="totals">
                <div class="trow"><span>Subtotal, bills</span><span class="v" id="t-bills">RM 0.00</span></div>
                <div class="trow"><span>Subtotal, fixed charges</span><span class="v" id="t-addons">RM 0.00</span></div>
                <div class="trow" id="row-arrears" hidden><span>Rent arrears / other</span><span class="v" id="t-arrears">RM 0.00</span></div>
                <div class="trow total"><span>Total payable by tenant</span><span class="v" id="t-total">RM 0.00</span></div>
                <div class="trow" id="row-dep" hidden><span>Less deposit held</span><span class="v" id="t-dep">RM 0.00</span></div>
            </div>
            <div class="issued" id="issued"></div>
            <div class="act" id="act">
                <button class="tbtn primary" id="btn-final" type="button">Finalise, store and download PDF</button>
                <button class="tbtn" id="btn-copy" type="button">Copy as text</button>
                <button class="tbtn" id="btn-clear" type="button">Start over</button>
            </div>
        </div>
    </div>
</div>
</form>

<details class="help">
    <summary>How the calculation works</summary>
    <ol>
        <li><b>Count the billing period in whole days, both ends included.</b> <code>(period to − period from) + 1</code>. A TNB cycle of 5 Aug to 4 Sep is 31 days, not 30.</li>
        <li><b>Find the days the tenant was in that unit during that period.</b> It starts on the later of the unit move-in and period-from, and ends on the earlier of the unit move-out and period-to.</li>
        <li><b>Daily rate, then multiply.</b> <code>bill ÷ days in period</code>, then <code>× days the tenant was in</code>. Rounded to the sen at the end.</li>
        <li><b>Every bill runs on its own cycle.</b> Key each one in with the period printed on that bill. Leave "period to" empty and the last day of that month is filled in.</li>
        <li><b>Fixed charges are never split by days.</b> Quantity × rate. Aircond service defaults to RM150 per unit, deep cleaning to RM250; both are editable.</li>
        <li><b>Indah Water is charged by the month.</b> The tool counts the calendar months the stay touched. Standard domestic rate RM12.00 a month (low-cost RM4.00, kampung and estate RM5.00).</li>
        <li><b>Split bookings.</b> One block per unit with that unit's own dates and bills. The changeover day counts in both units, as the booking references overlap on it.</li>
        <li><b>Orange warnings</b> mean a bill period does not touch the stay. Almost always the wrong month's bill was keyed in. A statement cannot be finalised while a warning is showing.</li>
    </ol>
</details>
</div>
@endsection

@push('scripts')
@include('admin.tenancy._pdfjs')
<script>
(function () {
  "use strict";
  var COMPANY = @json($company);
  var SEED = @json($seed);
  var PREPARED_BY = @json($preparedBy);
  var STORE_URL = @json(route('admin.tenancy.store'));
  var ATTACH_URL = @json(url('/admin/tenancy-statements')) + '/__ID__/pdf';
  var CSRF = document.querySelector('meta[name=csrf-token]').content;

  var MONTHS = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"], LETTERS = "ABCDEFGHIJKLMNOP", DAY = 86400000;
  var BILL_PRESETS = ["TNB electricity", "Water", "Internet / wifi"];
  var ADDON_PRESETS = [
    { label: "Indah Water (sewerage)", qtyLab: "Months", rateLab: "Per month", rate: "12.00", auto: "months" },
    { label: "Deep cleaning", qtyLab: "Qty", rateLab: "Rate", rate: "250.00" },
    { label: "Aircond service", qtyLab: "No. of AC", rateLab: "Per unit", rate: "150.00" },
    { label: "Maintenance work", qtyLab: "Qty", rateLab: "Rate", rate: "" },
    { label: "Damage deduction", qtyLab: "Qty", rateLab: "Rate", rate: "" }
  ];
  function presetAddons() { return ADDON_PRESETS.map(function (p) { return { label: p.label, qtyLab: p.qtyLab, rateLab: p.rateLab, rate: p.rate, auto: p.auto, qty: "", touched: false }; }); }

  var uid = 0, $ = function (id) { return document.getElementById(id); };
  var unitsBox = $("units"), addonsBox = $("addons"), root = $("tb");

  function r2(x) { return Math.round((x + Number.EPSILON) * 100) / 100; }
  function money(n) { var neg = n < -0.004; return (neg ? "−RM " : "RM ") + Math.abs(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","); }
  function num(el) { var v = parseFloat(el.value); return isFinite(v) ? v : 0; }
  function parseDate(s) { if (!s || !/^\d{4}-\d{2}-\d{2}$/.test(s)) return null; var p = s.split("-"); var t = Date.UTC(+p[0], +p[1] - 1, +p[2]); return isNaN(t) ? null : t; }
  function fmt(t) { if (t === null) return "—"; var d = new Date(t); return d.getUTCDate() + " " + MONTHS[d.getUTCMonth()] + " " + d.getUTCFullYear(); }
  function fmtShort(t) { if (t === null) return "—"; var d = new Date(t); return d.getUTCDate() + " " + MONTHS[d.getUTCMonth()]; }
  function days(a, b) { return Math.round((b - a) / DAY) + 1; }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]; }); }
  function monthSpan(a, b, ok) {
    if (a === null || b === null || !ok) return { count: 0, text: "" };
    var s = new Date(a), e = new Date(b), sy = s.getUTCFullYear(), sm = s.getUTCMonth(), ey = e.getUTCFullYear(), em = e.getUTCMonth();
    var count = (ey - sy) * 12 + (em - sm) + 1; if (count < 1) return { count: 0, text: "" };
    var names = []; for (var k = 0; k < count && k < 4; k++) { var m = (sm + k) % 12, y = sy + Math.floor((sm + k) / 12); names.push(MONTHS[m] + " " + y); }
    return { count: count, text: count > 4 ? names.slice(0, 2).join(", ") + " … " + MONTHS[em] + " " + ey : names.join(", ") };
  }
  function endOfMonth(iso) { var p = iso.split("-"); var d = new Date(Date.UTC(+p[0], +p[1], 0)); var m = d.getUTCMonth() + 1, day = d.getUTCDate(); return d.getUTCFullYear() + "-" + (m < 10 ? "0" + m : m) + "-" + (day < 10 ? "0" + day : day); }

  function billRow(data) {
    var i = ++uid, el = document.createElement("div"); el.className = "bill";
    el.innerHTML = '<div class="bill-top"><input type="text" class="b-label" maxlength="60" aria-label="Bill name" placeholder="Bill name" value="' + esc(data.label || "") + '"><button type="button" class="kill" aria-label="Remove this bill">×</button></div>' +
      '<div class="bill-fields"><label class="field"><span class="lab">Period from</span><input type="date" class="b-start" value="' + esc(data.start || "") + '"></label>' +
      '<label class="field"><span class="lab">Period to</span><input type="date" class="b-end" value="' + esc(data.end || "") + '"></label>' +
      '<label class="field amt-cell"><span class="lab">Bill amount</span><span class="money-in"><input type="number" step="0.01" min="0" class="b-amt" placeholder="0.00" value="' + esc(data.amount || "") + '"></span></label></div>' +
      '<div class="bill-out off"><span class="t">Leave the amount empty if this bill doesn\'t apply.</span><span class="n">—</span></div>';
    return el;
  }
  function addonRow(data) {
    var i = ++uid, el = document.createElement("div"); el.className = "addon";
    if (data.auto) el.dataset.auto = data.auto; if (data.touched) el.dataset.touched = "1";
    el.innerHTML = '<label class="field name-cell"><span class="lab">Charge</span><input type="text" class="a-label" maxlength="60" placeholder="Charge name" value="' + esc(data.label || "") + '"></label>' +
      '<label class="field qty-cell"><span class="lab">' + esc(data.qtyLab || "Qty") + '</span><input type="number" step="1" min="0" class="a-qty" placeholder="0" value="' + esc(data.qty || "") + '"></label>' +
      '<label class="field rate-cell"><span class="lab">' + esc(data.rateLab || "Rate") + '</span><span class="money-in"><input type="number" step="0.01" min="0" class="a-rate" placeholder="0.00" value="' + esc(data.rate || "") + '"></span></label>' +
      '<div class="field amt-cell"><span class="lab">Amount</span><div class="a-out off">—</div></div><button type="button" class="kill" aria-label="Remove this charge">×</button>';
    return el;
  }
  function unitCard(data) {
    var i = ++uid, el = document.createElement("div"); el.className = "unit";
    el.innerHTML = '<div class="unit-top"><span class="unit-badge" aria-hidden="true">A</span><input type="text" class="u-name" maxlength="60" aria-label="Unit name" placeholder="Unit number, e.g. H-30-3A" value="' + esc(data.name || "") + '"><button type="button" class="kill kill-unit" aria-label="Remove this unit">×</button></div>' +
      '<div class="unit-body"><div class="pair"><label class="field"><span class="lab">In this unit from</span><input type="date" class="u-in" value="' + esc(data.from || "") + '"></label>' +
      '<label class="field"><span class="lab">In this unit until</span><input type="date" class="u-out" value="' + esc(data.to || "") + '"></label></div>' +
      '<div class="readout idle u-days">Key in both dates to see the days in this unit.</div><div class="bills-label">Bills for this unit</div><div class="rows u-bills"></div>' +
      '<button type="button" class="tbtn add u-addbill">+ Add a bill for this unit</button></div>';
    var box = el.querySelector(".u-bills"); (data.bills || []).forEach(function (b) { box.appendChild(billRow(b)); });
    return el;
  }
  function renumber() { var els = unitsBox.querySelectorAll(".unit"); for (var i = 0; i < els.length; i++) { els[i].querySelector(".unit-badge").textContent = LETTERS[i] || "+"; els[i].querySelector(".kill-unit").hidden = (els.length === 1); } }
  function load(st) {
    $("tenant").value = st.tenant || ""; $("bookref").value = st.bookref || ""; $("deposit").value = st.deposit || ""; $("arrears").value = st.arrears || "";
    $("preparedby").value = st.preparedBy || PREPARED_BY; $("property").value = st.property || ""; $("remarks").value = st.remarks || "";
    $("incl-out").checked = st.inclusive !== false;
    unitsBox.innerHTML = ""; addonsBox.innerHTML = "";
    (st.units || []).forEach(function (u) { unitsBox.appendChild(unitCard(u)); });
    (st.addons || []).forEach(function (a) { addonsBox.appendChild(addonRow(a)); });
    renumber(); compute();
  }
  function snapshot() {
    var st = { tenant: $("tenant").value, bookref: $("bookref").value, preparedBy: $("preparedby").value, property: $("property").value, remarks: $("remarks").value,
               deposit: $("deposit").value, arrears: $("arrears").value, inclusive: $("incl-out").checked, units: [], addons: [] };
    unitsBox.querySelectorAll(".unit").forEach(function (ue) {
      var u = { name: ue.querySelector(".u-name").value, from: ue.querySelector(".u-in").value, to: ue.querySelector(".u-out").value, bills: [] };
      ue.querySelectorAll(".bill").forEach(function (b) { u.bills.push({ label: b.querySelector(".b-label").value, start: b.querySelector(".b-start").value, end: b.querySelector(".b-end").value, amount: b.querySelector(".b-amt").value }); });
      st.units.push(u);
    });
    addonsBox.querySelectorAll(".addon").forEach(function (ae) {
      st.addons.push({ label: ae.querySelector(".a-label").value, qtyLab: ae.querySelector(".qty-cell .lab").textContent, rateLab: ae.querySelector(".rate-cell .lab").textContent,
                       qty: ae.querySelector(".a-qty").value, rate: ae.querySelector(".a-rate").value, auto: ae.dataset.auto || "", touched: ae.dataset.touched === "1" });
    });
    return st;
  }
  function blankUnit() { return { name: "", from: "", to: "", bills: BILL_PRESETS.map(function (l) { return { label: l, start: "", end: "", amount: "" }; }) }; }
  function blank() { return { tenant: "", bookref: "", preparedBy: PREPARED_BY, property: "", remarks: "", deposit: "", arrears: "", inclusive: true, units: [blankUnit()], addons: presetAddons() }; }

  function setOut(el, mod, text, amount) { el.className = "bill-out" + (mod ? " " + mod : ""); el.querySelector(".t").textContent = text; el.querySelector(".n").textContent = amount; }
  function lineHTML(label, calc, amount, dim) {
    return '<div class="line"><span class="name"' + (dim ? ' style="color:var(--faint)"' : "") + '>' + esc(label) + '</span><span class="amt"' + (dim ? ' style="color:var(--faint)"' : '') + '>' + money(amount) + '</span><span class="calc">' + esc(calc) + '</span></div>';
  }

  var hasFlags = false, hasContent = false;
  function compute() {
    var inclusive = $("incl-out").checked, unitEls = unitsBox.querySelectorAll(".unit"), multi = unitEls.length > 1;
    var flags = [], totalStayDays = 0, spanStart = null, spanEnd = null, billTotal = 0, stmtHTML = "", anyBill = false, textUnits = [];
    for (var u = 0; u < unitEls.length; u++) {
      var ue = unitEls[u], letter = LETTERS[u] || "+", rawName = ue.querySelector(".u-name").value.trim(), name = rawName || ("Unit " + letter);
      var uin = parseDate(ue.querySelector(".u-in").value), uoutRaw = parseDate(ue.querySelector(".u-out").value);
      var occEnd = uoutRaw === null ? null : (inclusive ? uoutRaw : uoutRaw - DAY), stayDays = 0, bad = false;
      if (uin !== null && occEnd !== null) { stayDays = days(uin, occEnd); if (stayDays < 1) { stayDays = 0; bad = true; } }
      var ud = ue.querySelector(".u-days");
      if (bad) { ud.className = "readout bad u-days"; ud.textContent = "The end date is on or before the start date. Check them."; flags.push(name + ": the dates for this unit run backwards."); }
      else if (stayDays) { ud.className = "readout u-days"; ud.textContent = "Tenant was in this unit for " + stayDays + (stayDays === 1 ? " day" : " days") + (inclusive ? "" : " (nights)") + ", " + fmt(uin) + " to " + fmt(uoutRaw) + "."; totalStayDays += stayDays; spanStart = spanStart === null ? uin : Math.min(spanStart, uin); spanEnd = spanEnd === null ? uoutRaw : Math.max(spanEnd, uoutRaw); }
      else { ud.className = "readout idle u-days"; ud.textContent = "Key in both dates to see the days in this unit."; }
      var unitLines = "", unitTotal = 0, unitText = [], unitHasBill = false, billEls = ue.querySelectorAll(".bill");
      for (var k = 0; k < billEls.length; k++) {
        var r = billEls[k], label = r.querySelector(".b-label").value.trim() || "Unnamed bill", amt = num(r.querySelector(".b-amt"));
        var bs = parseDate(r.querySelector(".b-start").value), be = parseDate(r.querySelector(".b-end").value), out = r.querySelector(".bill-out");
        if (!amt) { setOut(out, "off", "Leave the amount empty if this bill doesn't apply.", "—"); continue; }
        unitHasBill = true; anyBill = true;
        if (bs === null || be === null) { setOut(out, "warn", "Key in the billing period printed on this bill.", "—"); unitLines += lineHTML(label, "Billing period not keyed in", 0, true); flags.push(name + " – " + label + ": amount keyed in, but the billing period is missing."); continue; }
        if (be < bs) { setOut(out, "warn", "The period runs backwards: “to” is before “from”.", "—"); unitLines += lineHTML(label, "Period end is before period start", 0, true); flags.push(name + " – " + label + ": the billing period runs backwards."); continue; }
        if (uin === null || occEnd === null || !stayDays) { setOut(out, "off", "Waiting on this unit's dates above.", "—"); unitLines += lineHTML(label, "Waiting on this unit's dates", 0, true); flags.push(name + " – " + label + ": waiting on this unit's dates."); continue; }
        var periodDays = days(bs, be), oStart = Math.max(bs, uin), oEnd = Math.min(be, occEnd), overlap = oEnd >= oStart ? days(oStart, oEnd) : 0, daily = amt / periodDays, share = r2(daily * overlap);
        unitTotal += share;
        if (overlap === 0) { setOut(out, "warn", "This bill period doesn't touch the stay in this unit. Wrong month, or wrong unit?", money(0)); unitLines += lineHTML(label, "No overlap · bill " + fmtShort(bs) + " → " + fmtShort(be), 0, true); flags.push(name + " – " + label + ": the billing period doesn't overlap this unit's dates."); }
        else {
          setOut(out, "", "Tenant was in for " + overlap + " of " + periodDays + " days (" + fmtShort(oStart) + " → " + fmtShort(oEnd) + ")  ·  RM " + daily.toFixed(4) + " a day", money(share));
          unitLines += lineHTML(label, overlap + " of " + periodDays + " days · RM " + daily.toFixed(4) + "/day · " + fmtShort(oStart) + " → " + fmtShort(oEnd), share, false);
          unitText.push({ label: label, calc: overlap + " of " + periodDays + " days @ RM " + daily.toFixed(4) + "/day", amt: share });
        }
      }
      billTotal += unitTotal;
      if (unitHasBill) {
        var meta = stayDays ? fmtShort(uin) + " → " + fmtShort(uoutRaw) + " · " + stayDays + (inclusive ? " days" : " nights") : "dates not set";
        stmtHTML += '<div class="grp grp-unit"><span>' + esc(letter + " · " + name) + '</span><span class="meta">' + esc(meta) + '</span></div>' + unitLines;
        if (multi) stmtHTML += '<div class="line sub"><span class="name">Subtotal, ' + esc(name) + '</span><span class="amt">' + money(unitTotal) + "</span></div>";
        textUnits.push({ name: name, letter: letter, meta: meta, lines: unitText, total: unitTotal });
      }
    }
    billTotal = r2(billTotal);
    $("s-bills").innerHTML = anyBill ? stmtHTML : '<div class="grp">Bills split by days</div><p class="empty">No bill amounts keyed in yet.</p>';
    var tenant = $("tenant").value.trim(), ref = $("bookref").value.trim();
    $("s-who").textContent = tenant || ref ? [tenant, ref].filter(Boolean).join("  ·  ") : "Tenant not set";
    $("s-when").textContent = totalStayDays ? fmt(spanStart) + " → " + fmt(spanEnd) + "  ·  " + totalStayDays + (inclusive ? " days" : " nights") + (multi ? " across " + unitEls.length + " units" : "") : "Unit dates not set";
    var so = $("stay-out");
    if (totalStayDays) { so.className = "readout"; so.textContent = "Total stay: " + fmt(spanStart) + " to " + fmt(spanEnd) + ", " + totalStayDays + (inclusive ? " days" : " nights") + (multi ? " across " + unitEls.length + " units." : "."); }
    else { so.className = "readout idle"; so.textContent = "Add the unit and its dates in step 2 to see the total stay."; }

    var months = monthSpan(spanStart, spanEnd, totalStayDays > 0), addHTML = "", addTotal = 0, addText = [], autoUsed = false;
    addonsBox.querySelectorAll(".addon").forEach(function (ar) {
      var qtyEl = ar.querySelector(".a-qty");
      if (ar.dataset.auto === "months" && ar.dataset.touched !== "1") { qtyEl.value = months.count ? String(months.count) : ""; if (months.count) autoUsed = true; }
      var aLabel = ar.querySelector(".a-label").value.trim() || "Unnamed charge", qty = num(qtyEl), rate = num(ar.querySelector(".a-rate")), aAmt = r2(qty * rate), outEl = ar.querySelector(".a-out");
      if (!aAmt) { outEl.className = "a-out off"; outEl.textContent = "—"; return; }
      outEl.className = "a-out"; outEl.textContent = money(aAmt); addTotal += aAmt;
      var unitWord = (ar.querySelector(".qty-cell .lab").textContent || "").toLowerCase(), calc = qty + " × " + money(rate) + (unitWord === "months" ? " a month" : " each");
      addHTML += lineHTML(aLabel, calc, aAmt, false); addText.push({ label: aLabel, calc: calc, amt: aAmt });
    });
    addTotal = r2(addTotal);
    $("s-addons").innerHTML = addHTML || '<p class="empty">No fixed charges keyed in.</p>';
    var mo = $("months-out");
    if (autoUsed) { mo.className = "readout"; mo.textContent = "The stay touches " + months.count + " calendar month" + (months.count === 1 ? "" : "s") + " (" + months.text + "), so Indah Water is filled in as " + months.count + ". Change it if the bill says otherwise."; }
    else if (months.count) { mo.className = "readout"; mo.textContent = "The stay touches " + months.count + " calendar month" + (months.count === 1 ? "" : "s") + " (" + months.text + ")."; }
    else { mo.className = "readout idle"; mo.textContent = "Indah Water is charged per month, not by days. Fill in the unit dates in step 2 and the months fill in by themselves."; }

    var arrears = r2(num($("arrears"))), total = r2(billTotal + addTotal + arrears), deposit = r2(num($("deposit")));
    $("t-bills").textContent = money(billTotal); $("t-addons").textContent = money(addTotal);
    $("row-arrears").hidden = !arrears; $("t-arrears").textContent = money(arrears); $("t-total").textContent = money(total);
    $("row-dep").hidden = !(deposit > 0); $("t-dep").textContent = money(deposit);
    var head = $("headline");
    if (deposit > 0) {
      var balance = r2(deposit - total);
      if (balance >= 0) { head.className = "headline"; $("h-key").textContent = "Refund to the tenant"; $("h-val").textContent = money(balance); $("h-sub").textContent = money(deposit) + " deposit held, less " + money(total) + " in charges."; }
      else { head.className = "headline owing"; $("h-key").textContent = "Still owed by the tenant"; $("h-val").textContent = money(Math.abs(balance)); $("h-sub").textContent = "Charges of " + money(total) + " are more than the " + money(deposit) + " deposit. Collect the difference."; }
    } else { head.className = "headline"; $("h-key").textContent = "Total payable by tenant"; $("h-val").textContent = money(total); $("h-sub").textContent = total ? "Add the deposit in step 4 to see the refund." : "Key in the unit dates and bills on the left."; }
    $("flags").innerHTML = flags.map(function (f) { return '<div class="flag"><span class="dot">!</span><span>' + esc(f) + "</span></div>"; }).join("");
    hasFlags = flags.length > 0; hasContent = textUnits.length > 0 || addText.length > 0 || arrears > 0;

    var L = ["SETTLEMENT STATEMENT", $("s-who").textContent, $("s-when").textContent, ""];
    textUnits.forEach(function (tu) { L.push("UNIT " + tu.letter + " – " + tu.name + "  (" + tu.meta + ")"); tu.lines.forEach(function (b) { L.push("- " + b.label + ": " + money(b.amt)); L.push("  " + b.calc); }); if (multi) L.push("  Subtotal: " + money(tu.total)); L.push(""); });
    if (textUnits.length) { L.push("All bills split by days: " + money(billTotal)); L.push(""); }
    if (addText.length) { L.push("FIXED CHARGES"); addText.forEach(function (a) { L.push("- " + a.label + ": " + money(a.amt) + "  (" + a.calc + ")"); }); L.push("Subtotal: " + money(addTotal)); L.push(""); }
    if (arrears) { L.push("Rent arrears / other: " + money(arrears)); L.push(""); }
    L.push("TOTAL PAYABLE: " + money(total));
    if (deposit > 0) { var bal2 = r2(deposit - total); L.push("Deposit held: " + money(deposit)); L.push(bal2 >= 0 ? "REFUND TO TENANT: " + money(bal2) : "STILL OWED BY TENANT: " + money(Math.abs(bal2))); }
    L.push(""); L.push("Utilities are split by the days the tenant was in each unit: the bill amount divided by the days in the billing period, times the days occupied in that unit.");
    window.__summary = L.join("\n");
  }

  /* ---- finalise: server computes and numbers, browser draws the PDF, stores it, downloads it ---- */
  function finalise() {
    var btn = $("btn-final");
    if (!$("tenant").value.trim()) { toast("Key in the tenant name first."); $("tenant").focus(); return; }
    if (!hasContent) { toast("Nothing to put on a statement yet. Key in a bill or a charge first."); return; }
    if (hasFlags) { toast("Fix the orange warnings first."); return; }
    if (!window.MokaStatementPDF || !window.jspdf) { toast("The PDF builder did not load. Check the internet connection and reload."); return; }
    if (!confirm("Finalise this statement? It gets a number and is stored as final. A wrong statement can only be voided, not edited.")) return;
    btn.disabled = true; btn.textContent = "Storing…";
    fetch(STORE_URL, { method: "POST", headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": CSRF, "Accept": "application/json" }, body: JSON.stringify({ input: snapshot() }) })
      .then(function (r) { return r.json().then(function (j) { return { s: r.status, j: j }; }); })
      .then(function (res) {
        var j = res.j;
        if (!j || !j.ok) { throw new Error((j && j.message) || "Could not store the statement (" + res.s + ")."); }
        root.classList.add("locked");
        var built = window.MokaStatementPDF(COMPANY, j.data, j.no, j.issued);
        var box = $("issued"); box.style.display = "block";
        box.innerHTML = "<b>" + esc(j.no) + " issued</b>Stored as final. <a href='" + esc(j.url) + "'>Open the statement</a> · <a href='" + esc(location.pathname) + "'>Start a new one</a>";
        $("act").innerHTML = '<a class="tbtn primary" style="text-align:center;text-decoration:none" href="' + esc(j.url) + '">Open statement ' + esc(j.no) + '</a><button class="tbtn" id="btn-copy" type="button">Copy as text</button>';
        $("btn-copy").addEventListener("click", copySummary);
        if (!built) { box.innerHTML += "<div style='margin-top:6px;color:#92400e'>The PDF builder did not load, so no PDF was stored yet. Open the statement and press “Create and store the PDF”.</div>"; return; }
        var fd = new FormData(); fd.append("pdf", built.blob, built.name);
        return fetch(ATTACH_URL.replace("__ID__", j.id), { method: "POST", headers: { "X-CSRF-TOKEN": CSRF, "Accept": "application/json" }, body: fd })
          .then(function (r) { return r.json(); })
          .then(function (a) {
            built.doc.save(built.name);
            if (a && a.ok) { toast("Statement " + j.no + " stored and PDF downloaded."); box.innerHTML += "<div style='margin-top:4px'>PDF stored on file and downloaded.</div>"; }
            else { box.innerHTML += "<div style='margin-top:6px;color:#92400e'>The PDF downloaded but could not be stored. Open the statement and press “Create and store the PDF”.</div>"; }
          });
      })
      .catch(function (e) { toast(e.message || "Something went wrong."); btn.disabled = false; btn.textContent = "Finalise, store and download PDF"; });
  }

  var toastTimer;
  function toast(msg) { var old = root.querySelector(".toast"); if (old) old.remove(); var t = document.createElement("div"); t.className = "toast"; t.setAttribute("role", "status"); t.textContent = msg; root.appendChild(t); clearTimeout(toastTimer); toastTimer = setTimeout(function () { t.remove(); }, 3200); }
  function copySummary() {
    var text = window.__summary || "";
    var done = function () { toast("Copied. Paste it to the tenant."); };
    var fallback = function () { var ta = document.createElement("textarea"); ta.value = text; ta.style.position = "fixed"; ta.style.opacity = "0"; document.body.appendChild(ta); ta.select(); var ok = false; try { ok = document.execCommand("copy"); } catch (e) { ok = false; } ta.remove(); if (ok) done(); else toast("Copy blocked here. Select the statement and copy by hand."); };
    if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(text).then(done, fallback); else fallback();
  }

  var form = $("tb-form");
  form.addEventListener("submit", function (e) { e.preventDefault(); });
  form.addEventListener("input", function (e) { var t = e.target; if (t && t.classList && t.classList.contains("a-qty")) { var row = t.closest(".addon"); if (row) row.dataset.touched = "1"; } compute(); });
  form.addEventListener("change", function (e) { var t = e.target; if (t && t.classList && t.classList.contains("b-start") && t.value) { var row = t.closest(".bill"), endEl = row && row.querySelector(".b-end"); if (endEl && !endEl.value) endEl.value = endOfMonth(t.value); } compute(); });
  form.addEventListener("click", function (e) {
    if (!e.target.closest) return;
    var add = e.target.closest(".u-addbill"); if (add) { var box = add.closest(".unit").querySelector(".u-bills"); var row = billRow({}); box.appendChild(row); row.querySelector(".b-label").focus(); return; }
    var killUnit = e.target.closest(".kill-unit"); if (killUnit) { var card = killUnit.closest(".unit"); if (card && unitsBox.querySelectorAll(".unit").length > 1) { card.remove(); renumber(); compute(); } return; }
    var k = e.target.closest(".kill"); if (k) { var r2el = k.closest(".bill") || k.closest(".addon"); if (r2el) { r2el.remove(); compute(); } }
  });
  $("add-unit").addEventListener("click", function () { var prev = unitsBox.querySelector(".unit:last-child"), seed = blankUnit(); if (prev) { var prevOut = prev.querySelector(".u-out").value; if (prevOut) seed.from = prevOut; } var card = unitCard(seed); unitsBox.appendChild(card); renumber(); compute(); card.querySelector(".u-name").focus(); card.scrollIntoView({ behavior: "smooth", block: "nearest" }); });
  $("add-addon").addEventListener("click", function () { var row = addonRow({ qty: "1" }); addonsBox.appendChild(row); row.querySelector(".a-label").focus(); });
  $("btn-clear").addEventListener("click", function () { if (!confirm("Clear everything and start over?")) return; load(blank()); $("tenant").focus(); window.scrollTo({ top: 0, behavior: "smooth" }); });
  $("btn-final").addEventListener("click", finalise);
  $("btn-copy").addEventListener("click", copySummary);
  window.addEventListener("beforeunload", function (e) { if (!root.classList.contains("locked") && hasContent) { e.preventDefault(); e.returnValue = ""; } });

  load(SEED ? SEED : blank());
  if (SEED) toast("Copied from the earlier statement. Check every figure before finalising.");
})();
</script>
@endpush
