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
