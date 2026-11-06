# Future build: owner statements, yearly statements, profit analysis

Sam, 6 Nov 2026. Goals, in his words: capture the net profit for all units and owners for data
analysis; from the monthly results generate a yearly statement per owner automatically (owners
need it for tax submission); push every report to the owner portal so owners view and download
them there (today they are sent by hand on WhatsApp); email them too; archive files after a year.

## What is ready now (6 Nov 2026)
- **Every finished report run keeps its inputs and its zip** (`report_runs`, files in
  `storage/app/reports/<id>/`; never deleted). The zip holds Excel and PDF for every unit.
- **Per-unit results are captured on every run** into `report_unit_results` (migration
  2026_11_06_000001): run, month, unit, listing, owner at the time, category, payable to landlord,
  Moka net profit, and the unit's PDF and Excel path inside the zip. `OwnerReports::captureResults`
  runs when a pack finishes; `php artisan reports:capture --all` backfills (July, August, September
  2026 packs captured on 6 Nov 2026). Group members carry their pooled per-unit figure and the group
  statement as their file. Backfill result (6 Nov 2026): July 139 units, August 139, September 137; totals
  equal each run's compilation to the cent; 107 / 107 / 105 owners. Four units have no Homemoka listing and
  so no owner yet: J-06-01, J-30-11, RS-15-01 (New Storeroom) (office rents) and The Olive; create listings
  (with the owner account) for them before owner statements go live.
- **Owner identity**: `listings.user_id` is the owner account; the owner portal (`/owner`, role
  owner) already shows each owner only their own units (rule: MAKE SURE THAT OWNER ONLY VIEW THEIR
  OWN UNIT). An owner with several units gets several rows per month.
- **Month-end routine inside Homemoka**: PMS month check → flags cleared → Report Generator with the
  Master Utilities Excel only; Homemoka builds the bookings workbook. The engine stays on Cloud Run.

## To build, in order
1. **Owner statements page in the owner portal.** For each of the owner's units, one line per month
   from `report_unit_results` (payable, with the PDF to view or download, streamed out of the run's
   zip). Months before the capture (pre July 2026) are not available. Publishing is a per-run action
   on the Report Generator page ("Publish to owners") so staff still review the flags first; a
   `published_at` on `report_runs` (or per row) gates visibility.
2. **Notification**: on publish, email each owner "your <Month> statement is ready" with the portal
   link (owner email on the user account; collect missing ones first). WhatsApp stays manual.
3. **Yearly statement per owner**: sum of `report_unit_results` by owner and unit for a calendar year,
   rendered as one PDF (unit, month, payable; totals), generated in January for the previous year and
   on demand; downloadable in the portal; the same for Moka's own books by unit.
4. **Archive after a year**: zips older than 12 months move from the VM disk to the GCS bucket
   (`gs://moka-reports-output-494606` or a cold bucket); the portal fetches from the bucket when the
   local copy is gone. `report_unit_results` rows stay forever (they are the ledger).
5. **Profit analysis**: `report_unit_results` already answers payable and Moka net profit by unit,
   owner, property, category and month. A dashboard page (per property and per owner, trends by
   month, negative months flagged) is a view over this table. Revenue, OTA and cleaning per unit per
   month can be joined from `bookings` (or captured from the individual reports later).

## Data rules
- Rows are written from the pack that was (or will be) sent; a corrected pack re-run for a month
  replaces that run's rows only. Which run of a month is "the one sent" is the published one.
- Never delete `report_runs` or their zips (rule 27: a reported month is frozen; the pack is the
  record of what owners received).
- Owner change on a unit: rows keep the owner at capture time, so a yearly statement goes to the
  owner who was paid that month.
