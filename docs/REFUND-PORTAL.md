# Deposit refund portal — how it works (staff guide)

Live since 12 Oct 2026. Replaces the Google Form.

## Front desk (every day)

1. Open the admin **Dashboard**. The box "Today's deposit refund link" shows the current link.
2. Click **Copy WhatsApp message** and paste it to the guest (or the guest group). It is the usual check-out message with today's link in place of the old forms.gle link.
3. A new link is issued automatically every morning at **9:00**. Each link works for 48 hours, so a message sent yesterday still works today. Nothing to request.
4. If a guest says the link has expired, send today's message again.

## Guest

Fills in: name as per reservation, contact number, email, property, unit, check-out date, bank name, account holder, account number, two ratings, optional comments, and optionally the team member who served them with a rating. The guest sees only "Thank you, your request is in". They never see booking details.

## One guest, several units

The guest taps "+ Add another unit" on the form (up to three). One request is created per unit, each matched to its own booking and tagged "1 of 2", "2 of 2". Tick both and use Mark selected as paid with one bank reference: one transfer to the guest, one row per eZee deposit. If a guest crammed two units into one box ("01-28 and 01-30"), type the other unit in the row's **Split** box: a second request is created with the same bank details and grouped with the first.

## Daily workflow and cut-offs

1. Guest submits on check-out day. Deposit is RM 200 on every request (config `moka.refund_deposit`).
2. **Operation Team, by 12:00 noon the next working day** (the row shows "Ops cut-off …"): record any deduction with its reason, and help identify unmatched requests with Pick, Link (booking #, FN folio or RES) or Split. After the cut-off the deduction box closes for Operations; Finance Manager / Admin can still edit.
3. **Finance, daily**: check the deposits on the bank statement / SQL accounting, tick the rows and click **✓ Deposit received** in the bulk bar (or Confirm received inside a row), then tick again and **Mark paid** with the bank reference. Bulk payment skips rows whose receipt is not confirmed; a single Mark paid on an unconfirmed row asks first. Operations tick rows and click **✓ No deduction** to record a clean check in bulk.

The list is ordered by due date, most urgent first, under Overdue / Due today / Due tomorrow / Due later headings. Tiles at the top show Overdue, Due today, Ready to pay (Ops checked and deposit received) and the share paid on time in the last 30 days. "Select: ready to pay / overdue / due today / ops not checked" ticks the matching rows in one click.

Each request is one line: guest, stay, booking match, refund amount, and three progress chips, Ops (deduction checked), Rcvd (deposit received), Paid. Click the arrow or the line to open the Booking, Deposit and Bank panels with the forms.
4. **Month end**: Paid tab with the month's paid-date range, read the total, export the CSV, tie to the bank statement as a second check.

## Deductions (Operation Team)

When part of a deposit is kept (damage, missing item, extra cleaning), the Operation Team records it on the request before Finance pays: open the row's **Deposit / deduct** box in the Deposit column, enter the deposit held, the amount deducted and the reason, Save. The row then shows "Refund RM X" (deposit less deduction) with who recorded it and when. A deduction without a reason, or larger than the deposit, is refused. Finance's Paid box is prefilled with the refund due and records the amount actually paid; the Paid tab shows a total for the chosen date range, and the CSV carries Deposit, Deduction, Deduction reason, Refund due and Amount paid.

## Finance (Operations → Deposit refunds)

- **Pending** — everything not yet paid. Rows marked **Matched** were tied to a booking automatically (property + unit + check-out date). Open the booking link, check the deposit in eZee, pay, then click **Paid** (bank reference optional).
- Matching accepts the Homemoka unit name ("11-08", "Alinea 11-08") and the eZee room code guests see in their messages ("AL-11-08", "BS 03-06"). A code that belongs to another property is flagged, not matched.
- **Date range** boxes on the filter bar: submitted dates on Pending / Verify / All, paid dates on Paid. Export CSV follows the same range, so a paid-date range matches a bank statement period.
- **Verify** — no clean match (wrong unit, wrong date, or the same booking already has a request = **Duplicate**). The row shows **Likely bookings** (same unit within 3 days, similar guest name at that property within 7 days, same phone within 14 days): click **Pick** to link one. Or type into the **Link** box: booking number, eZee folio (FN4525) or reservation (RES4465). Or **Reject** with a reason. Nothing is ever deleted.
- **Due** — 3 working days after the guest submitted. Overdue rows turn red.
- **Export CSV** — the current list with bank details, for the bank's bulk payment file. Finance only; every export is logged.
- **Guest feedback** — cleanliness and service by property, and the team KPI (who was named, average rating, compliments). Only requests tied to a real stay count, so made-up submissions cannot move anyone's score.

## Who sees what

| Role | Dashboard link | Requests | Bank details, Paid / Reject / Link, CSV | Feedback |
|---|---|---|---|---|
| Finance Team, Finance Manager, Admin, Super Admin | yes | yes | yes | yes |
| Operation Manager | yes | yes, account masked, can record deposit / deduction | no | yes |
| Operation Team | yes | yes, account masked, can record deposit / deduction | no | no |

## Protection

- Link = 28 random characters, 48 hours, only accepted in that shape.
- Per IP: 6 submissions a minute, 150 a day. Per phone number: 3 a day. Honeypot field for bots. Optional Cloudflare Turnstile (set `MOKA_TURNSTILE_SITE` and `MOKA_TURNSTILE_SECRET` in `.env`).
- Bank holder and account number are encrypted with the app key (`APP_KEY` in `.env`). Keep the `.env` backups: without the key the stored account numbers cannot be read.
- Shared logins (the "Intern" account) never appear in the "who served you" list (`config/moka.php`, `refund_staff_exclude`).

## Where the code is

`App\Support\Refunds` (link, matching, caps), `RefundController` (guest), `Admin\RefundController` (Finance), command `refunds:issue-link` (09:00), tables `refund_links` and `refund_requests`, views `resources/views/refund/` and `admin/refunds/`.
