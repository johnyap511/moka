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
