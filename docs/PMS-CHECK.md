# PMS check (eZee vs Homemoka reconciliation)

Built 15 Oct 2026 after Bell Suites 07-30: eZee moved a no-show off the unit on 23 Sep,
Homemoka only reflected it on 5 Oct, and the owner saw the figure change. Promise made to
the owner: a month-end reconciliation against the PMS before every monthly report.

## What it does
`App\Support\PmsReconcile::run()` compares every **live eZee reservation** whose stay touches
the window with its Homemoka booking, and lists Homemoka bookings with no eZee record.
Nothing is changed by the check; it only records differences in `pms_differences`.

| Kind | Meaning |
|---|---|
| A | eZee reservation with no Homemoka booking (not assigned) |
| B | Booking cancelled in Homemoka while eZee still shows the stay |
| C | eZee has the stay on an extra room, Homemoka on a unit (no-show not yet moved) |
| D | Different unit |
| E | Dates differ (whole stay chain vs eZee Start/End) |
| F | eZee room has no Homemoka unit mapping |
| G | Amount differs (same drift rule as the daily reconcile, `EzeeAutoAssign::amountDrift`) |
| H | Homemoka booking whose whole folio family has no live eZee row |

Split stays and month segments are handled through `BookingSplitter::stayChain` and the
folio family, so a stay held as several bookings is not reported as missing.

## When it runs
- **Daily 06:30** (`pms:reconcile --days=60`), after the 06:00 reconcile. Open count on the
  admin dashboard tile; list at Operations → **PMS check**.
- **Month-end**: on the PMS check page pick the month and **Run month check**
  (`pms:reconcile --month=YYYY-MM`). Every stay touching the month is compared.

## Differences lifecycle
- New difference → open, `first_seen_at`. Seen again → `last_seen_at` refreshed.
- A person resolves it with a note (ezee.manage). A manual resolution sticks; the same
  fingerprint is not raised again unless it was auto-resolved and then reappears.
- Not found by a later run that covers its dates → auto-resolved "cleared by the data".
- Reopen is available on the Resolved tab for manual resolutions.

## Month-end gate (owner reports)
`PmsReconcile::gate($month)` must pass before **Owner reports → Upload and continue**:
a month check for that month within the last 24 hours and no open difference for it.
The owner reports page shows the state and links to the check. A super admin may override
with a reason; the reason is written into the run's log.

## Owner dashboard
A line under the heading: "Live figures, provisional until your monthly report. Last
checked against the property management system <time of latest run>."

## Code
`app/Support/PmsReconcile.php`, `app/Console/Commands/PmsReconcileCommand.php`,
`app/Http/Controllers/Admin/PmsDifferenceController.php`, `resources/views/admin/pms/index.blade.php`,
models `PmsDifference`, `PmsReconcileRun`; gate in `Admin\OwnerReportController::store`.
Permissions: ezee.view to see, ezee.manage to run and resolve.

## Month sheet (6 Nov 2026, Part A of the reconciliation build)
Operations → PMS check → **Month sheet** (`/admin/pms-check/sheet?month=YYYY-MM`, ezee.view). Every booking
checking in during the month, next to its eZee figure, with a verdict and what to do; eZee reservations with no
Homemoka booking (open kind A) at the end as EZEE ONLY. Read-only; `App\Support\PmsMonthSheet`. Excel download of
all rows (`/pms-check/sheet/export`). Default view shows only rows needing attention.

Rules, in order, first match wins (from Sam's reconciliation tool v6.7, adapted to Homemoka data):
1. Open PMS difference on the booking → NOT OK `DIFF-<kind>`: resolve it on the list first.
2. No live eZee record: Owner source → OK; zero value → OK; else NOT OK `NO-EZEE`.
3. Long Term Rental on either side → OK `LTR` (no amount comparison).
4. eZee stay longer than `ltr_min_nights` (300) with a non-LTR source → NOT OK `WRONG-SOURCE`.
5. Day use (no night): extra room → OK; owner unit → REVIEW `DAY-USE` (not billable to the owner).
6. Single-row stay: within `sst_rounding` (0.02) → OK; within `rounding` (0.50) → OK `ROUNDING`.
7. "-N" split room: room charge agrees → OK `SPLIT` (cleaning sits on the sibling row).
8. Stay held as several rows (month segments, room move): whole stay vs eZee. Agrees → OK `SEGMENT`; explained by
   extras/deposit/cleaning → OK; below `materiality` (RM 100) → OK `CROSS-MONTH`; else REVIEW `CROSS-MONTH`.
9. Single-row difference explained by eZee extras that are not revenue (`EXTRAS`), the RM 200 deposit (`DEPOSIT`),
   or a cleaning fee with/without SST on the other side of a month end (`CLEANING`) → OK.
10. Otherwise NOT OK `UNEXPLAINED`: open the folio in eZee and compare the itemised list.
EZEE ONLY rows: `EZ-MISSING` (owner unit, billable: add the booking), `EZ-EXTRA` (extra room, manual check),
`EZ-DAYUSE`, `EZ-ZERO`. Thresholds in `config/moka.php` `pms_check` (env MOKA_PMS_*).
Not reproducible from the eZee API, so not implemented: "other payment method" rules (the API gives one lump
TotalPayment and no method breakdown; checked 6 Nov 2026).
October 2026 run: 1,081 rows, 1,052 OK, 5 REVIEW, 22 NOT OK, 2 eZee-only, 2.7 s.
