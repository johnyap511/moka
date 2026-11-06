# Study note: report generator spec + reconciliation spec (6 Nov 2026)

Source files (Sam's Downloads): MOKA_REPORT_GENERATOR_SPEC.md (engine V21), Reconciliation_Spec.md (tool v6.7).
Purpose: understand before building. Nothing built from this note yet.

## 1. Report generator (V21) — what it is
The spec documents the Python engine `moka_reports.py` that already runs for Homemoka as the private
Cloud Run service (repo ~/moka-report-service, since 6 Oct 2026, page /admin/owner-reports, table report_runs).
So "Path A" in the spec is already done. The engine is design-locked; payout logic frozen.

Inputs today: two Excel uploads per month.
- Master_Utilities.xlsx (finance): per-unit config + that month's bills, maintenance items, adjustments, Min Guarantee tab.
- Reconciled_FINAL.xlsx: "Moka" sheet (one row per booking) + BS/Alinea/Mesahill group tabs.
- Optional previous month's master (new/removed unit list). Override tables (mg_units, owner_stay, office_rent,
  alinea_rent_ovr, str_retag, lt_override) are already editable in the page's Advanced box.

What Homemoka does NOT hold yet (would be new data models for a native build):
- Unit finance config: profit share (L:H), bill logic (water/internet/TNB/MF+SF/rent), report type Individual/Grouping,
  Long Term flag + LT rent, Rental (rent floor / sublease), Min Guarantee, remarks flag "100:0 MF + SF OWNER".
  listings table has only pool_weight, cleaning_fee, owner_income, advance_rental; groups exist (ezee_groups).
- Monthly bills per unit (Water, Internet, Electricity, MFSF, Rental, LongTermRent), up to 6 maintenance items with
  ratios, N adjustment slots (item, amount, ratio).
- Standing overrides hard-coded in the engine: HYBRID_UNITS, THE_OLIVE, BS_1412_LT_RENT (RM1600).

Calculation core (must match to the cent): 8 unit classes (group, owner-stay, olive, office-rent, lt_rent, lt,
individual_rent, individual); bills split by PS; MG top-up absorbed by Moka; adjustments and maintenance split by
their own ratios; 3 group pools (BS 80% by points 600/900 of 12,600; Alinea 75% even; Mesahill 80% even over
active units only); 4-dp internal precision, 2-dp display; compilation order and flags.

Recommended path: keep the engine (frozen, tested), move the INPUTS into Homemoka in two steps:
  1. Homemoka builds the "Moka" sheet + group tabs itself from bookings and group membership (Phase 2 agreed 6 Oct).
  2. Unit finance settings page + monthly bills/adjustments form that produce Master_Utilities for the engine.
A native PHP rewrite of the maths is a later, optional step, validated against archived report_runs month by month.

## 2. Reconciliation (v6.7) — what it is
Forward pass (each Moka booking → find eZee row, classify the money difference with 15 ordered rules),
reverse pass (each eZee booking with Total>0 must exist in Moka; MISSING = billable, Extra Room / hourly = manual),
group OTA+cleaning subtotals. Works from eZee REPORT files (detail revenue report, daily extra charge report),
with Other Payment Method and a unit-name mapping table. Manual check first, report later.

Already in Homemoka (PMS check, 15 Oct 2026, App\Support\PmsReconcile, /admin/pms-check):
- Reverse pass = kind A (eZee reservation with no Homemoka booking) and H (Homemoka booking without eZee);
  unit/dates/amount = D/E/G; unmapped room = F (mapping is a DB relation, ezee_room_mappings); extra room = C.
- Lifecycle (open → resolved with note, auto-clear, reopen), daily 06:30 + month run, owner-report gate.
- Works from the eZee API (ezee_bookings), not report files. Amounts in Homemoka are DERIVED from eZee
  (ground rule: eZee is final for linked bookings), so forward money diffs only arise from unlinked or hand-edited rows.

Gaps vs the spec (candidates, all additive, no data changes):
- G says "amount differs" but does not say WHY: add the reason classification (SST rounding 0.02/0.50,
  cross-month portion with RM100 materiality → REVIEW vs OK, cleaning fee or CF×1.08 in adjacent month,
  extra charges explain the diff — ezee_bookings.extra_charges exists — deposit RM200, OPM).
  Open question: does the API give "Other Payment Method"? TotalPayment exists; payment method breakdown unknown.
- R4: stay > 300 nights but source not Long Term Rental → flag wrong source.
- Hourly / day-use on real units (Nights = 0): list as non-billable manual check (today only extra-room day-use is handled).
- Thresholds (materiality, deposit, rounding, LTR nights) as config, not constants.
- A per-booking month sheet ("Checkk": every booking with OK / REVIEW / Not OK) for the human tick, exportable;
  today the page lists only differences.
- Group OTA + cleaning subtotals as an output of the month check (feeds the report generator group tabs).

## 3. Build order suggested
1. PMS check extensions (reason codes, config thresholds, hourly, wrong-source, month sheet export). Small, safe.
2. Homemoka generates the reconciled workbook (Moka sheet + group tabs) from the checked month → drop one upload.
3. Unit finance settings + monthly bills form → generate Master_Utilities → drop the second upload.
4. (Optional, later) native payout maths, verified to the cent against archived runs.

Decisions for Sam: keep Excel for bills or key in Homemoka; who maintains overrides monthly; whether the
Oct-2026 archived run is the golden test month.

## 4. Checked 6 Nov 2026: does the eZee API give "Other Payment Method"?
No. Booking API (getdataAPI, Request_Type Booking) returns TotalPayment as one lump, PayAtHotel flag,
TACommision, totals, and ExtraCharge lines (ChargeName, Amount, ChargeDate). RetrieveListofBills returns
foliono, billno, TotalCharges, PaidAmount, DueAmount only. Other kiosk request names (RetrieveBillDetails,
RetrieveFolioDetails, RetrieveBookingDetails, RetrievePaymentDetails, RetrieveListofPayments, FolioDetails,
GetFolioDetails) are rejected as invalid.
Consequence: rules R12/R14 (OPM) are not reproducible from the API; R13 (RM200 deposit) is, via the deposit
ExtraCharge line (already in ezee_bookings.extra_charges) and TotalPayment minus charges; R9–R11 fully covered.
