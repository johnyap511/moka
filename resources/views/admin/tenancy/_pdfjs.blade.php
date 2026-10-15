{{-- The statement PDF, drawn in the browser with jsPDF from figures the server computed.
     Shared by the calculator (on Finalise) and the statement page (re-issue of a missing PDF). --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" crossorigin="anonymous"></script>
<script>
window.MokaStatementPDF = (function () {
  "use strict";
  var MONTHS = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  var M = 17, PW = 210, RIGHT = PW - M, BOTTOM = 277;
  var INK = [26, 24, 20], SOFT = [122, 116, 106], FAINT = [158, 151, 140];
  var GREEN = [20, 122, 94], GREEN_D = [12, 86, 66], GREEN_BG = [226, 241, 234];
  var RULE = [224, 219, 209], ROW = [247, 245, 240], RED = [160, 46, 34], RED_BG = [251, 232, 228];

  function pdfText(t) {
    return String(t == null ? "" : t)
      .replace(/→/g, "-").replace(/–|—/g, "-").replace(/×/g, "x").replace(/·/g, "-")
      .replace(/−/g, "-").replace(/[‘’]/g, "'").replace(/[“”]/g, '"');
  }
  function money2(n) { return Math.abs(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","); }
  function fileSafe(t) { return pdfText(t).replace(/[\\\/:*?"<>|]+/g, " ").replace(/\s+/g, " ").trim(); }

  /* company: {name, reg, addr1, addr2, email, phone}; data: the server's computed figures;
     no: statement number; issued: "15 Oct 2026 at 14:02". Returns {doc, name, blob}. */
  return function build(COMPANY, data, no, issued) {
    var ns = window.jspdf;
    if (!ns || !ns.jsPDF) return null;
    var doc = new ns.jsPDF({ unit: "mm", format: "a4" });
    var y = 0;

    function set(font, size, color) { doc.setFont("helvetica", font); doc.setFontSize(size); doc.setTextColor(color[0], color[1], color[2]); }
    function rule(x1, yy, x2, color, w) { doc.setDrawColor(color[0], color[1], color[2]); doc.setLineWidth(w || 0.2); doc.line(x1, yy, x2, yy); }

    function masthead(first) {
      doc.setFillColor(GREEN_D[0], GREEN_D[1], GREEN_D[2]);
      doc.rect(0, 0, PW, first ? 28 : 18, "F");
      doc.setTextColor(255, 255, 255);
      doc.setFont("helvetica", "bold"); doc.setFontSize(first ? 12.5 : 10);
      doc.text(COMPANY.name, M, first ? 12 : 10.5);
      doc.setFont("helvetica", "normal"); doc.setFontSize(7.2);
      if (first) {
        doc.text(COMPANY.reg, M, 16.4);
        doc.text(COMPANY.addr1, M, 21);
        doc.text(COMPANY.addr2, M, 24.3);
        doc.text("E  " + COMPANY.email + "          H  " + COMPANY.phone, RIGHT, 12, { align: "right" });
      } else {
        doc.setFontSize(7.6);
        doc.text("Settlement statement " + no + "  -  continued", RIGHT, 10.5, { align: "right" });
      }
      return first ? 38 : 27;
    }
    function space(need) { if (y + need > BOTTOM) { doc.addPage(); y = masthead(false); } }

    y = masthead(true);
    set("bold", 17, INK); doc.text("SETTLEMENT STATEMENT", M, y);
    set("normal", 8.2, SOFT); doc.text("Utility proration and deposit settlement", M, y + 5);
    set("normal", 7.3, FAINT); doc.text("STATEMENT NO.", RIGHT, y - 4.5, { align: "right" });
    set("bold", 11, GREEN); doc.text(no, RIGHT, y + 0.5, { align: "right" });
    set("normal", 8, SOFT); doc.text("Issued " + issued, RIGHT, y + 5, { align: "right" });
    y += 11; rule(M, y, RIGHT, GREEN, 0.7); y += 9;

    function field(label, value, x, yy, w) {
      set("normal", 7.2, FAINT); doc.text(label.toUpperCase(), x, yy);
      set("bold", 10, INK);
      var lines = doc.splitTextToSize(pdfText(value) || "-", w);
      doc.text(lines, x, yy + 4.8);
      return 4.8 + lines.length * 4.4;
    }
    var gap = 9, colW = (RIGHT - M - gap * 2) / 3;
    var c1 = M, c2 = M + colW + gap, c3 = M + (colW + gap) * 2;
    var h1 = 0, h2 = 0, h3 = 0;
    h1 += field("Statement to", data.tenant, c1, y + h1, colW);
    if (data.property) { h1 += 1.5; h1 += field("Property", data.property, c1, y + h1, colW); }
    h2 += field("Stay", (data.when || "-"), c2, y + h2, colW);
    if (data.stayDays) {
      set("normal", 8.2, SOFT);
      doc.text(data.stayDays + (data.inclusive ? " days" : " nights") + (data.unitCount > 1 ? ", " + data.unitCount + " units" : ""), c2, y + h2 + 3.4);
      h2 += 5;
    }
    h3 += field("Booking reference", data.ref || "-", c3, y + h3, colW);
    if (data.preparedBy) { h3 += 1.5; h3 += field("Prepared by", data.preparedBy, c3, y + h3, colW); }
    y += Math.max(h1, Math.max(h2, h3)) + 2;
    rule(M, y, RIGHT, RULE, 0.2); y += 6;

    var CX2 = M + 42, CX3 = M + 84, CX4 = RIGHT;
    function tableHead() {
      set("bold", 7.2, FAINT);
      doc.text("DESCRIPTION", M, y); doc.text("BILLING PERIOD", CX2, y); doc.text("BASIS OF CHARGE", CX3, y);
      doc.text("AMOUNT (RM)", CX4, y, { align: "right" });
      y += 2.2; rule(M, y, RIGHT, INK, 0.4); y += 5;
    }
    function sectionBar(title, right) {
      space(16);
      doc.setFillColor(GREEN_BG[0], GREEN_BG[1], GREEN_BG[2]); doc.rect(M, y - 4.2, RIGHT - M, 6.8, "F");
      doc.setFillColor(GREEN[0], GREEN[1], GREEN[2]); doc.rect(M, y - 4.2, 1.6, 6.8, "F");
      set("bold", 8.4, GREEN_D); doc.text(pdfText(title), M + 4.5, y);
      if (right) { set("normal", 7.6, [92, 120, 108]); doc.text(pdfText(right), RIGHT - 2.5, y, { align: "right" }); }
      y += 7.8;
    }
    var zebra = 0;
    function lineItem(it) {
      space(10);
      var h = 6.6;
      if (zebra++ % 2 === 1) { doc.setFillColor(ROW[0], ROW[1], ROW[2]); doc.rect(M, y - 4.2, RIGHT - M, h, "F"); }
      set("normal", 9.2, INK); doc.text(pdfText(it.label), M, y);
      set("normal", 7.8, FAINT); doc.text(pdfText((it.period || "").replace(/^Bill /, "")), CX2, y);
      set("normal", 7.9, SOFT); doc.text(pdfText(it.basis), CX3, y);
      set("bold", 9.2, INK); doc.text(money2(it.amt), CX4, y, { align: "right" });
      y += h; rule(M, y - 2.4, RIGHT, RULE, 0.15);
    }
    function subTotal(label, amount) {
      space(9); zebra = 0;
      set("bold", 8.6, SOFT); doc.text(pdfText(label), M, y + 0.8); doc.text(money2(amount), CX4, y + 0.8, { align: "right" });
      y += 7;
    }
    tableHead();
    (data.units || []).forEach(function (u) {
      zebra = 0;
      sectionBar("UNIT " + u.letter + "   " + u.name, u.meta);
      u.lines.forEach(lineItem);
      if ((data.units || []).length > 1) subTotal("Subtotal - " + u.name, u.total);
    });
    if ((data.addons || []).length) { zebra = 0; sectionBar("FIXED CHARGES", "charged in full, not split by days"); data.addons.forEach(lineItem); }
    if (data.arrears) { zebra = 0; sectionBar("OTHER AMOUNTS", ""); lineItem({ label: "Rent arrears / other", basis: "As advised", period: "", amt: data.arrears }); }

    var rows = 2 + (data.arrears ? 1 : 0) + 1 + (data.deposit > 0 ? 1 : 0);
    space(rows * 6 + 14 + (data.deposit > 0 ? 24 : 0));
    y += 3;
    var tx = M + 100;
    function tRow(label, amount, strong) {
      set(strong ? "bold" : "normal", strong ? 10 : 9.4, strong ? INK : SOFT);
      doc.text(pdfText(label), tx, y); doc.text(money2(amount), CX4, y, { align: "right" }); y += 5.6;
    }
    tRow("Bills split by days", data.billTotal, false);
    tRow("Fixed charges", data.addTotal, false);
    if (data.arrears) tRow("Rent arrears / other", data.arrears, false);
    rule(tx, y - 4, RIGHT, INK, 0.4); y += 1.5;
    tRow("Total payable by tenant", data.total, true);
    if (data.deposit > 0) tRow("Less security deposit held", data.deposit, false);

    if (data.deposit > 0) {
      var bal = Math.round((data.deposit - data.total) * 100) / 100;
      var refund = bal >= 0;
      space(26); y += 2;
      var bg = refund ? GREEN_BG : RED_BG, fg = refund ? GREEN_D : RED;
      doc.setFillColor(bg[0], bg[1], bg[2]); doc.setDrawColor(fg[0], fg[1], fg[2]); doc.setLineWidth(0.6);
      doc.roundedRect(M, y, RIGHT - M, 17, 2.5, 2.5, "FD");
      set("bold", 10.5, fg); doc.text(refund ? "REFUND DUE TO TENANT" : "BALANCE STILL OWED BY TENANT", M + 6, y + 10.5);
      set("bold", 16, fg); doc.text("RM " + money2(bal), RIGHT - 6, y + 11, { align: "right" });
      y += 24;
    }
    if (data.remarks) {
      space(20);
      set("bold", 7.2, FAINT); doc.text("REMARKS", M, y); y += 4.8;
      set("normal", 8.6, INK);
      var rl = doc.splitTextToSize(pdfText(data.remarks), RIGHT - M);
      doc.text(rl, M, y); y += rl.length * 4.3 + 5;
    }
    set("normal", 7.8, SOFT);
    var noteProbe = doc.splitTextToSize(
      "Each utility bill is divided by the number of days in its billing period, then charged for the days the tenant " +
      "occupied the unit within that period. Where the tenant moved between units, every bill is measured against the " +
      "dates of its own unit only. Fixed charges are not split by days. Indah Water is charged per calendar month of " +
      "the stay. Each line is rounded to the sen, so the lines above add up to the total. All amounts are in Malaysian " +
      "Ringgit (RM). Copies of the underlying bills are available on request.", RIGHT - M);
    space(12.3 + noteProbe.length * 3.5);
    rule(M, y, RIGHT, RULE, 0.2); y += 5.5;
    set("bold", 7.2, FAINT); doc.text("HOW THESE CHARGES ARE WORKED OUT", M, y); y += 4.6;
    set("normal", 7.8, SOFT); doc.text(noteProbe, M, y); y += noteProbe.length * 3.5 + 4;

    var pages = doc.internal.getNumberOfPages();
    for (var i = 1; i <= pages; i++) {
      doc.setPage(i);
      rule(M, 282, RIGHT, RULE, 0.2);
      set("normal", 7.2, FAINT);
      doc.text("This document is system generated, no signature needed.", M, 287);
      doc.text("Generated on " + issued + " via homemoka.com", M, 290.6);
      doc.text(no, RIGHT, 287, { align: "right" });
      doc.text("Page " + i + " of " + pages, RIGHT, 290.6, { align: "right" });
    }
    var who = fileSafe(data.tenant).slice(0, 28);
    return { doc: doc, name: no + (who ? " - " + who : "") + ".pdf", blob: doc.output("blob") };
  };
})();
</script>
