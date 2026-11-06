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

## 5. October 2026 golden files (from Sam, 6 Nov 2026)
Files: Reconciled_October2026_FINAL.xlsx, Master Utilities_102026_v2.xlsx (generated 4 Nov 09:27), Reports_102026_V21.zip.
- Reconciled workbook tabs: Note, Moka (1073 rows, the old 19-column Bookings export by check-in month), EZ (eZee detail
  revenue report, 69 columns), Checkk (forward check), List (253-row Moka↔eZee name map), BS, BS Group (19: 15 studio + 4 3BR),
  Alinea Group (12), Mesahill Group (14), EZ Not in Moka (reverse check), Rate Alerts.
- Master data tab "Utilities Oct 2026": 160 units, 40 columns, 4 adjustment slots. Report types: Individual 104 (19 Long Term),
  Grouping 53 (4 Long Term), 3 "NO REPORT". Profit shares 75:25 (83), 80:20 (44), 100:0 (29), 70:30 (4). 17 Logic phrasings.
  Other tabs: Min Guarantee (8 units), Issues, Anomalies, Reconciliation, Version Info.
- Zip: 231 files, Excel + PDF; Individual 89, Long Term 19, Group 3, Compilation 1. Compilation types: Individual 61,
  Individual (Rent) 27, Long Term 15, Long Term (Rent) 4, BS Studio 15, BS 3BR 4, Alinea 12, Mesahill 14.

Homemoka comparison:
- Moka sheet = Homemoka bookings with check-in in October and status 5 (live): 1073 vs 1079. The 6 extra rows in Homemoka
  are 7 bookings keyed on 4 Nov from the "EZ Not in Moka" list (BS 15-11 FN23263; H-05-06 FN33189/33231/33270/33294/33313;
  Extra Room 1 FN33310) minus one extra-room move. So Homemoka CAN generate the Moka sheet itself.
- Group tabs match Homemoka group membership exactly (BS 19, Alinea 12). Mesahill: file 14 active; Homemoka group has 19
  (PB-39-11, PB-40-03, PB-41-10, PB-41-17, PB-42-06 are the not-started ones).
- Master unit names: 153 of 160 match a Homemoka listing. Unmatched: RS-15-01 (New Storeroom), J-30-11, J-06-01,
  PB-40-04, PB-40-10, PB-40-11 (not created in Homemoka), The Olive (not a listing).
- listings.profit is NOT reliable as profit share: 45 mismatches vs the master (mostly NULL where master says 100:0;
  E-13-11 and E-3A-09 say 30 where master says 75:25). Master is the truth; a unit finance settings table is needed.

FINDING (for Sam): the October reports in the zip were generated from the Moka sheet BEFORE the 4 Nov fixes.
R_EkoCheras H-05-06 lists 10 bookings and lacks the five stays 16–23, 23–25, 25–26, 27–28, 28 Oct–1 Nov (incl. the
7-night walk-in RM1,188); R_Bell Suites 15-11 lists 3 bookings and lacks the 22–23 Oct Airbnb (RM211.57).
If these zips were sent to owners, those two owners were under-reported for October. Sam to decide (rule 27).
Lesson for the build: the reverse-check fixes must land in Homemoka BEFORE the Moka sheet is produced; the gate enforces this.

Update 6 Nov (Sam): the two October reports (H-05-06, BS 15-11) were amended after the issue was found; no action.
Utility codes on listings (App\Support\ListingOptions::UTILITY_OPTIONS): A Moka pays, split by profit share; B owner pays,
Moka refunds owner; C owner pays only; D Moka pays only; E add owner only; F add Moka only. NOTE the month-approval page
(approvalByMonth.blade.php) labels C as "Host Pays All" and D as "Owner Pays All", the opposite of ListingOptions. To confirm with Sam.

## 6. "Homemoka checking Oct'26" files (Sam's manual check inputs, studied 6 Nov 2026)
Zip: Bookings - Homemoka.xlsx (the old 19-column export, 1,073 October check-ins, title row "MOKA" then header),
Detail Revenue Report CSV per property (AL 198, BS 206, CHE 98, EKO 397, FOR 227 = 1,126 rows; column set varies per
property because every extra-charge type is a column), Daily Extra Charge CSV per property (916 lines; Reference =
"Front Desk Folio-FN…,Room : …"; Remark = Cleaning fee / Surcharge / Per Stay Fees / damage notes).
Property prefixes → eZee hotel codes: EKO 19676, BS 20317, FOR 20318 (Forum, Damai 88), CHE 20319 (Arte Cheras,
Queensville, KL Gateway, Mesahill), AL 20320.

What the Detail Revenue Report's Total means (verified on rows): revenue POSTED in the report window, incl. tax,
incl. extras and the Damage Deposit posted in the window. A tenancy of 364 nights shows October's share (RES27327:
949.44 vs Homemoka segment 949.45). Scope = arrival or departure in October; rows arriving 29/30 Sep and leaving
1 Oct appear with Total 0. 11 rows have a blank RES (post-audit folios, rule 26). "Other Payment Method" = payments
by non-room methods (deposits −200, advance rent); not in the API.

Coverage checks:
- Report vs API feed (ezee_bookings), hotel + RES: 1,110 of 1,115 RES rows present; the 5 absent are zero-total
  day-use/walk-ins. Within-October rows, whole folio (TotalAmountAfterTax + TotalExtraCharge + tax − discount):
  848 agree to the cent, 8 differ (cleaning posted on another date or on the "-1" row), 22 unmatched only by my
  quick unit-name normaliser. So the API carries the same money as the report; the month sheet can work from it.
- API rows touching October not in the report: 6 dead 2024 rows (ignored by the engine), one extra-room row,
  and RES31688 (EkoCheras walk-in 15 Oct–15 Nov, RM 3,794.40, no folio) — worth a look.
- Report vs Homemoka export, RES + unit: within October 739 agree, 76 differ; the differences are the deposit
  (RM 200/224, Homemoka rightly excludes it), extras posted on the folio, room moves held on two units, and the
  genuinely missing bookings the reverse check listed. Cross-month rows differ by construction (posted share vs segment).

Implication for the build: Sam's manual check = report Total vs Homemoka Total per booking, deposits/extras set aside.
The month sheet already does the same from the API, booking by booking, and flags the rest. Part B should also give
staff the per-property OTA + cleaning subtotals (group tabs) and the "posted this month" view for cross-month stays.

## 7. Part B built (6 Nov 2026): Homemoka writes the reconciled workbook
`App\Support\OwnerReportSheet::write($ym, $path)` (existed since 11 Oct for the Moka sheet) now also writes the
BS Group / Alinea Group / Mesahill Group tabs and a 20th column "Posted this month". Groups configured in
`config/moka.php` `report_groups` (tab, Homemoka group name prefix, markers). Member rule: not archived at month
end and started renting (first live booking by month end). Owner reports page: leave the Bookings sheet empty and
the workbook is built at "Upload and continue" (commit 734d575, b1 folio fallback after).
Proof against Reconciled_October2026_FINAL.xlsx: group tabs identical to the cent (BS 9,101.30 / 6,533.31;
Alinea 4,796.82 / 3,977.30; Mesahill 1,692.77 / 1,569.04; same members and markers). Moka sheet: 1,072 common rows
identical except 4 bookings edited after 4 Nov (70820, 70836, 68946, 71338), 7 rows added 4 Nov, 1 voided (71466).
Posted this month = Total on every October row (all stays end by 1 Nov or are month segments).

ENGINE WARNING: the Cloud Run engine (~/moka-report-service/engine/moka_reports.py, 6 Oct 2026) has no Mesahill
group (gather() reads BS and Alinea only; compilation order lacks "Group Mesahill"). Sam's October zip has a
Mesahill statement, so his local engine is newer. The service must be updated with the current moka_reports.py
before any cloud run of October or later; until then the cloud output would omit Mesahill.

## 8. Dry run (6 Nov 2026, local, Excel only)
Inputs: Homemoka-generated October workbook (35e7a79), Sam's Master Utilities_102026_v2.xlsx, Sam's
moka_reports_OCTOBER.py (Downloads, 6 Nov 13:51: dynamic adjustment slots, LT_OVERRIDE retired, no Mesahill),
toolkit logo, no previous master. Engine output: 129 unit reports, Group BS + Alinea, compilation.
Compared with Reports_102026_V21.zip (Excel): 110 files in common, 103 identical to the cent, 7 differ, all expected:
Compilation (Mesahill units listed as individuals because this engine copy has no Mesahill), H-05-06 and BS 15-11
(bookings keyed 4 Nov), Damai 88 12-6, H-26-13A, RS-39-10, SS-09-02 (bookings 68946, 70820, 71338, 70836 edited
after the file). Group BS and Group Alinea statements identical.
Mesahill (engine copy lacks it): recomputed from Homemoka's Mesahill tab + master with the spec §8 formula:
Sales 10,069.90 (room 8,500.86 + cleaning 1,569.04), 2% maint 201.40, OPEX 3,261.81 (OTA 1,692.77 + cleaning
1,569.04, no utilities in the master), operating profit 6,606.69, net 80% 5,285.35, per unit 377.53 (14 units),
Moka 94.38 per unit: equals Sam's Group Mesahill statement and compilation rows.
Conclusion: Homemoka's workbook is a drop-in replacement for the hand-made Reconciled_FINAL.xlsx.
Outstanding: the engine copy with Mesahill (the one that made the 10:52 zip) is not on this Mac nor on Cloud Run;
needed in ~/moka-report-service/engine before the November cloud run.
