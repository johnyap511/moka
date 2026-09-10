# MOKA ground rules (confirmed by Sam Kong, 4 Sep 2026)

These rules apply everywhere: sync, auto-assign, hourly reconcile, calendar, owner portal, both exports, Cowork reports.
Nothing in the platform may follow a different rule. In code they live in app/Support/Channel.php (channels, fee groups)
and app/Support/EzeePricing.php (amounts); the admin portal shows this page under "Ground rules".

## A. Source of truth
1. eZee is final for every eZee-linked booking: dates, room, guest, room charge, extra charges, tax, commission, channel, status.
   MOKA copies eZee; it never overrides it. A correction is made in eZee, and MOKA follows within the hour.
2. Every eZee stay exists in MOKA with its RES number. A row without a RES is allowed only for a MOKA-only booking
   that does not exist in eZee. Open item: link the hand-keyed month/split pieces of eZee stays to their RES.
3. A booking is identified by hotel + RES + folio + unit. Folio numbers repeat across hotels; never match by folio alone.
4. MOKA never cancels a booking on its own. It cancels only when eZee reports that RES cancelled.

## B. Money
5. Rate/night = eZee room charge (excl. tax) ÷ nights, identical on every piece of a split or cross-month stay.
6. SST = 8% of the room charge, shown separately (exclusive), on every channel including Monthly Rental and Long Term Rental.
7. Cleaning fee = eZee's "Cleaning Fee" line. If eZee has no cleaning line but a "Channel" line, that line is the cleaning fee.
   Deposits, late check-out and other incidentals are never cleaning and are dropped; they are not stay revenue.
   If eZee bills room only, cleaning = 0.
   Cleaning sits on the first piece of a stay and belongs to the arrival month.
8. SST(CF) = the tax eZee applied to that cleaning line (8% or 0), never assumed.
9. OTA fee:
   - OTA channels (Booking.com, Expedia, Airbnb, Traveloka, ...): the commission eZee reports for that booking.
   - Net-rate channels (Agoda, Trip.com, Tiket.com): 0, the rate is already net.
   - Direct channels (Walk In, PMS, Google, Internet, Booking Engine, Monthly Rental): shown as "Website",
     8% M&A fee on the room charge excl. tax only, never on the cleaning fee.
   - Long Term Rental: shown as "Long Term Rental", no fee.
10. Total = rate × nights + SST + cleaning + SST(CF) − discount. The OTA fee is shown, not deducted.
11. Revenue is by calendar month: a cross-month stay is split at month end, each piece carries its own nights.

## C. Channel names (one list, everywhere)
12. Booking.com, Agoda, Expedia, Airbnb, Trip.com, Traveloka, Tiket.com, Website, Long Term Rental, Owner.
    eZee's source is mapped to this list (CTrip/Ctrip/Ctrip.com → Trip.com; Traveloka codes stripped;
    Walk In/PMS/Google/Internet/Booking Engine/Monthly Rental → Website).

## D. Process
13. Month end: Revenue Export (EZEE) to check → fix in eZee → wait for the hourly sync → Bookings export (old format) → Cowork reports.
14. The old export's 19 columns never change.
15. Every automated amount change is written to the price log; nothing is changed silently.
16. Manual edits in MOKA are for MOKA-only bookings. On a linked booking they are overwritten by the next sync.
17. Stamped months (confirmed 7 Sep 2026): a booking that checked in before the first day of the previous month is locked.
    In September, July and earlier are locked. No job changes a locked booking; an eZee difference on one goes to Needs
    Review once. People may still edit it by hand.
18. Voids: eZee sends no event for a voided reservation. The absence sweep runs daily at 05:00, before the 06:00
    auto-assign; unassigned reservations are retired, assigned ones go to Needs Review. "Voided in eZee" is the manual path.
19. Delete is hidden (config moka.show_delete_button). Cancel keeps history; delete does not.
20. Room swaps eZee confirms (7 Sep 2026): when reservation A's final room is X, booking B blocks X, and eZee says B ended
    elsewhere, the sync moves B's nights from A's check-in to B's final room and puts A in X, for unlocked months only.
    Every applied swap appears in Needs Review for a person to check in eZee and Mark done.
21. One booking per unit per night, strictly. Two live bookings never share a night on an owner's unit; the save is refused.
    Company rooms ("<Hotel> Extra Room n") are the only exception and belong to MOKA, not to an owner.
22. Day-use / hourly stays (eZee check-in = check-out) are MOKA company revenue: assigned to the hotel's company room, never on
    an owner's calendar or statement; 0 nights, rate blank, room charge + SST in the total, "Day use" in Remarks.
23. Pool profit sharing (confirmed 8 Sep 2026): a unit of type "group" belongs to a pool. The pool's month = room revenue at the
    stamped rate for the nights in the month + cleaning fees of stays arriving in the month, excluding SST; the M&A fee is not
    deducted. An owner's share = the unit's pool weight ÷ the pool's total weight; weight follows unit size (e.g. 900 for a
    3-bedroom, 600 for a studio; equal weights where units are the same size). The owner portal shows pool figures and the share.
24. Guest profiles (8 Sep 2026): one profile per guest, built from eZee's name, email, mobile and country. A stay is matched
    to an existing profile by email, then mobile, then full name only when neither side has any contact detail. Names carry
    no counter; the old "NUR56" style names are cleaned. Nothing is merged on name alone when a phone or email exists.

## 25. Stays parked on Extra Rooms are reviewed, never moved (10 Sep 2026)
The front desk parks a finished or extended stay on an eZee "Extra Room". eZee then reports only that room. The sync never
moves a booking from a real unit to an Extra Room. When eZee's dates or amount drift for such a stay, or when a stay of one
night or more is created on an Extra Room, a Needs Review item is raised: staff read eZee's Room Charges and Split the stay,
unit nights to the owner's unit, parked nights on the Extra Room as company revenue. Day use (start = end) stays on the
company room under rule 22.
