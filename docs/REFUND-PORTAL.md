# Deposit refund portal — how it works (staff guide)

Live since 12 Oct 2026. Replaces the Google Form.

## Front desk (every day)

1. Open the admin **Dashboard**. The box "Today's deposit refund link" shows the current link.
2. Click **Copy WhatsApp message** and paste it to the guest (or the guest group). It is the usual check-out message with today's link in place of the old forms.gle link.
3. A new link is issued automatically every morning at **9:00**. Each link works for 48 hours, so a message sent yesterday still works today. Nothing to request.
4. If a guest says the link has expired, send today's message again.

## Guest

Fills in: name as per reservation, contact number, email, property, unit, check-out date, bank name, account holder, account number, two ratings, optional comments, and optionally the team member who served them with a rating. The guest sees only "Thank you, your request is in". They never see booking details.

## Finance (Operations → Deposit refunds)

- **Pending** — everything not yet paid. Rows marked **Matched** were tied to a booking automatically (property + unit + check-out date). Open the booking link, check the deposit in eZee, pay, then click **Paid** (bank reference optional).
- Matching accepts the Homemoka unit name ("11-08", "Alinea 11-08") and the eZee room code guests see in their messages ("AL-11-08", "BS 03-06"). A code that belongs to another property is flagged, not matched.
- **Date range** boxes on the filter bar: submitted dates on Pending / Verify / All, paid dates on Paid. Export CSV follows the same range, so a paid-date range matches a bank statement period.
- **Verify** — no clean match (wrong unit, wrong date, name differs, or the same booking already has a request = **Duplicate**). Find the stay, type the booking number in the **Link** box, or **Reject** with a reason. Nothing is ever deleted.
- **Due** — 3 working days after the guest submitted. Overdue rows turn red.
- **Export CSV** — the current list with bank details, for the bank's bulk payment file. Finance only; every export is logged.
- **Guest feedback** — cleanliness and service by property, and the team KPI (who was named, average rating, compliments). Only requests tied to a real stay count, so made-up submissions cannot move anyone's score.

## Who sees what

| Role | Dashboard link | Requests | Bank details, Paid / Reject / Link, CSV | Feedback |
|---|---|---|---|---|
| Finance Team, Finance Manager, Admin, Super Admin | yes | yes | yes | yes |
| Operation Manager | yes | yes, account masked | no | yes |
| Operation Team | yes | no | no | no |

## Protection

- Link = 28 random characters, 48 hours, only accepted in that shape.
- Per IP: 6 submissions a minute, 150 a day. Per phone number: 3 a day. Honeypot field for bots. Optional Cloudflare Turnstile (set `MOKA_TURNSTILE_SITE` and `MOKA_TURNSTILE_SECRET` in `.env`).
- Bank holder and account number are encrypted with the app key (`APP_KEY` in `.env`). Keep the `.env` backups: without the key the stored account numbers cannot be read.
- Shared logins (the "Intern" account) never appear in the "who served you" list (`config/moka.php`, `refund_staff_exclude`).

## Where the code is

`App\Support\Refunds` (link, matching, caps), `RefundController` (guest), `Admin\RefundController` (Finance), command `refunds:issue-link` (09:00), tables `refund_links` and `refund_requests`, views `resources/views/refund/` and `admin/refunds/`.
