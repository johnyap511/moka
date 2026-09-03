# MOKA — Full Application Audit

**Repository:** `johnyap511/moka`, branch `main`, HEAD `453db5e` ("Show the units in a group")
**Local checkout:** `~/Herd/moka`
**Staging:** `35.215.128.58` — GCP VM `moka-staging`, project `moka-staging-494606`, zone `asia-east2-c`
**Production:** `31.22.4.60` — *not reachable from this environment; nothing in this report is measured against production*
**Audit date:** 2 September 2026
**Changes made:** none. All database access was read-only (`SELECT` / `SHOW`), plus one `ezee:auto-assign --dry-run`, which writes nothing.

---

## 1. Executive summary

MOKA is a Laravel 9 short-stay property management platform for Moka Venture Sdn Bhd. In practice it is **an admin and owner back-office fed by the eZee (ipms247) PMS**, not the consumer booking site its route table suggests. The public booking funnel and the guest portal are largely non-functional; the parts the business actually runs on — eZee sync, room mapping, booking assignment, monthly owner reporting — are live and healthy.

The codebase shows two distinct generations. Anything touched since roughly June 2026 (`app/Support/Ezee*`, `EzeeRoomMappingController`, the newer migrations, the deploy script) is well-structured, commented with the *reason* for each decision, and defensively written. Everything older — `ListingController` at 7,803 lines, `BookController`, `BookingController`, `WebController` — is copy-paste procedural code with heavy duplication and no tests.

### What matters most

| # | Finding | Severity |
|---|---|---|
| 1 | Any owner can change **any user's password**, including an admin's, via `PUT /owner/update/change_password/{id}` | **Critical** |
| 2 | Admin RBAC is cosmetic — the `adminperm` middleware is applied to **zero routes**; all 23 admins resolve to `super_admin` | **Critical** |
| 3 | "Remove Duplicates" on the eZee screen would cancel **7,916 legitimate bookings** (groups on `SubBookingId`, which is not unique across properties) | **Critical** |
| 4 | Owner report screens leak other owners' revenue/payment data via `?listing_id=` (IDOR) | **High** |
| 5 | `/admin/ezee/bookings-by-property` loads all **60,673** eZee rows into one page | **High** |
| 6 | The hourly sync updates on unindexed `SubBookingId`, matching rows across properties — the cause of 1,520 lock-wait timeouts and a live data-corruption path | **High** |
| 7 | `/contact`, `/policy`, `/terms` return **500** in production configuration; `/policy` and `/terms` are linked from every public page footer | **High** |
| 8 | Auto-assignment cannot be switched on: only **270 of 814** current/future eZee stays carry a unit name | **Blocker** |
| 9 | Config and routes are never cached in production; the deploy script clears but never rebuilds | Medium |
| 10 | Zero automated tests across ~24,100 lines of PHP | Medium |

---

## 2. Scope and method

### Verified directly

- Static analysis of the full checkout: 42 controllers, 68 model classes, 12 console commands, 108 Blade views, 64 migrations.
- Live read-only queries against the staging MySQL database (table sizes, indexes, row counts, data-quality probes).
- Live HTTP probes of every public route on staging.
- Staging `storage/logs/laravel.log` (15 MB) error-frequency analysis.
- `php artisan ezee:auto-assign --dry-run` on staging to measure assignment readiness.
- Git history correlation (182 commits, 28 April – 2 September 2026).

### Not verified

- **Production (31.22.4.60).** Everything here describes the `main` branch and the staging deployment. Where staging and production configuration may diverge — notably `EZEE_AUTO_ASSIGN`, mail transport, and whether the legacy `update_excel` table is still in use — that is called out.
- **Authenticated admin and owner screens in the browser.** No credentials were available, so admin/owner screens are described from controller and Blade source rather than from live walkthroughs. Broken screens listed in §8 are inferred from missing view files; three of them (`/contact`, `/policy`, `/terms`) were confirmed live, and two more (`admin.listing.book.ezeeBookReport`, `auth.contact`) appear as `View not found` in the staging error log, which validates the detection method.

---

## 3. System overview

### Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 9, PHP 8.1+ (staging runs PHP 8.2.30 FPM) |
| Auth / RBAC | `santigarcor/laratrust` 7 for roles; a bespoke `admin_can()` layer for admin sub-roles |
| Database | MySQL (staging: `moka` on 127.0.0.1). Local dev is configured for SQLite |
| Queue | `sync` — **everything runs inline in the request** |
| Cache / session | file |
| Spreadsheets | `maatwebsite/excel` + raw PhpSpreadsheet |
| PDF | `dompdf` |
| Images | `intervention/image` |
| Files | `unisharp/laravel-filemanager` |
| Scheduling | system cron → `artisan schedule:run` every minute |

### Deployment

`sudo bash scripts/deploy.sh --branch main` on the VM: refuses to run with a dirty working tree, pulls, conditionally runs `composer install` (only when `composer.lock` moved), reports pending migrations unless `--migrate`, clears caches, reloads `php8.2-fpm`.

The script is well-written but **clears caches without rebuilding them**. `bootstrap/cache/` on staging contains only `packages.php` and `services.php` — no `config.php`, no route cache. Every request therefore re-parses `.env` and all four route files. See §11.

The staging working tree also carries accidental artefacts from people running commands on the server directly: files literally named `19676,`, `7010306964bf04d4ef-9225-11f1-8,`, `"ponse = curl_exec($ch);"`, a stray `moka/` directory, and `resources/views/admin/listing/book/ezeeBook.blade.php.bak`.

---

## 4. Module breakdown

### 4.1 eZee PMS integration — *the operational core*

| File | Lines | Purpose |
|---|---|---|
| `app/Console/Commands/HistoricalApi.php` | 605 | `hour:update` — the scheduled sync. Pulls reservations for each property and upserts `ezee_bookings`; fetches folio numbers; triggers auto-assign |
| `app/Support/EzeeAutoAssign.php` | 475 | Reconcile pass: assigns eZee reservations to listings, follows room moves, logs conflicts, closes stale conflicts |
| `app/Support/EzeeUnitMap.php` | 194 | Resolves an eZee unit name to a listing, scoped by property |
| `app/Support/EzeePricing.php` | 285 | Single source of truth for the M&A (marketing & administration) fee and the price breakdown |
| `app/Support/EzeeBookingFeed.php` | 245 | Booking feed client |
| `app/Support/EzeeRoomFeed.php` | 90 | Room/unit inventory client |
| `app/Http/Controllers/Admin/EzeeRoomMappingController.php` | 358 | The mapping screen, manual assign/reassign, conflict resolution, audit log |
| `app/Http/Controllers/Admin/BookController.php` | 2,362 | eZee booking lists, manual assignment, duplicate removal, bulk upload |

**Five eZee properties** (`ezee_groups`), each with its own `hotel_code` and `auth_key`:

| id | hotel_code | Name |
|---|---|---|
| 1 | 19676 | EkoCheras |
| 2 | 20317 | Bell Suites |
| 3 | 20318 | Forum, Damai 88 |
| 4 | 20319 | Arte Cheras, Queensville, KL Gateway |
| 6 | 20320 | Alinea Suites |

Note that group 4 covers **three physically separate buildings** under one hotel code. Since property-scoped unit resolution keys on hotel code, unit names must be unique across all three of those buildings or they will resolve ambiguously.

### 4.2 Listings

`Admin\ListingController` (7,803 lines) — CRUD, images, video, pricing, zones, amenities, archiving, Excel export, and the entire monthly reporting engine. `Listing` carries a global `notArchived` scope with `withArchived()` / `archived()` escapes; this is applied consistently and is one of the better pieces of design in the codebase.

### 4.3 Bookings

Two controllers with overlapping responsibility:
- `Admin\BookingController` (1,458 lines) — the `/admin/book` resource: list (paginated *and* a separate DataTables server-side path), CRUD, Excel import/export.
- `Admin\BookController` (2,362 lines) — creating a booking against a listing, plus everything eZee-facing.

### 4.4 Owner portal

`Owner\HomeController` (dashboard KPIs), `Owner\ListingController` (list, monthly report, Excel export, a stub `performance()`), `Owner\CalendarController` (unified calendar, booking detail, Excel export), `Owner\ReportController` (revenue and payment reports read from `listing_reports`).

### 4.5 Finance / owner reporting

`ListingController::reportExport()` (1,526 lines) and `reportExports()` (2,165 lines) build the monthly owner statement as an XLSX. As a **side effect** they create or update the `listing_reports` row — which is what makes the month visible to the owner in the owner portal. There is no other writer.

`Admin\PaymentController` reads `listing_reports` for upcoming/past payment screens. The `payments` table is empty on staging.

### 4.6 Public site and guest portal

Two generations of front-end coexist: `resources/views/auth/newTheme/*` (older) and `resources/views/v2/*` (current). Live routes mix both. The guest portal (`/home/*`, role `user`) is substantially broken — see §8.3.

### 4.7 Payments — non-functional

`User\PaymentController` integrates Billplz. It reads `BILLPLZ_KEY`, `BILLPLZ_COLLECTION_ID`, `BILLPLZ_CALLBACK_URL`, `BILLPLZ_REDIRECT_URL`, `BILLPLZ_SECRET` via `env()`. **None of these are set** in `.env` on staging or locally. The `payments` table is empty; `transactions` holds 143 legacy rows. The endpoint is live and CSRF-exempt (`/payment/callback`), so it should be treated as attack surface even though it fails closed.

### 4.8 Supporting modules

Zones, amenities, groups (`Admin\GroupController`), user/owner/admin management, approval review, announcements, subscribe list, file manager, blog (5 static Blade posts), sitemap and environment-aware robots.txt.

---

## 5. Data model

### Live table sizes (staging, 2 Sep 2026)

| Table | Rows | Data | Index |
|---|---:|---:|---:|
| `data_logs` | 197,746 | 80.6 MB | **0.0 MB** |
| `bookings` | 64,487 | 9.5 MB | 0.0 MB |
| `ezee_bookings` | 60,673 (exact) | 15.5 MB | 0.0 MB |
| `users` | 57,487 | 5.5 MB | 0.0 MB |
| `role_user` | 55,766 | 2.5 MB | 1.5 MB |
| `listing_reports` | 1,896 | 0.4 MB | 0.0 MB |
| `listings` | 303 (168 active) | 0.1 MB | 0.0 MB |
| `ezee_room_mappings` | 196 (138 mapped) | — | — |
| `ezee_rooms` | 167 | — | — |
| `ezee_assignment_logs` | 257 | — | — |

### Core relationships

```
User ──< Listing ──< Booking >── User (guest)
         │             │
         │             └──1:1── EzeeBooking (via ezee_bookings.book_id)
         │
         ├──< ListingReport   (year+month; the owner-visible statement)
         ├──< ListingImages / ListingDetail / ListingPrice(Detail)
         ├──>< Amenities      (listing_amenities)
         ├──>< Zone           (listing_zones)
         ├──< ListingGroup >── Group
         └──< EzeeRoomMapping >── EzeeGroup

EzeeGroup (property) ──< EzeeRoom (unit inventory)
                      └──< EzeeGroupListing

EzeeBooking ──< EzeeAssignmentLog >── Listing (+ old_listing_id, assigned_by)
update_excel ──> Listing        (utility/adjustment lines)
```

`ezee_bookings` links to `bookings` by `book_id`; the reverse link is `Booking::ezeeBooking()`. The reservation's true identity is the **pair** `(SubBookingId, TransactionId)` — eZee numbers reservations per property, so `RES6103` exists independently at several properties. **20,410 `SubBookingId` values are shared across more than one property.** This single fact underlies findings 3 and 6.

### Model-layer problems

**Three parallel model namespaces** with no clear rule: `app/*.php` (35 classes, the real set), `app/Models/*.php` (6 classes), `app/OtherModel/*.php` (8 classes). `app/Models/Listing.php` and `app/Models/User.php` duplicate `app/Listing.php` and `app/User.php`. `App\Models\Listing` is referenced from exactly two Blade files (`admin/user/ownerList.blade.php:48`, `admin/user/ownerEdit.blade.php:8`) — and critically it **lacks the `notArchived` global scope**, so those two screens count and list archived properties while every other screen does not.

**Duplicate tables.** `update_excel` (12 rows, legacy) and `update_excels` (0 rows) both exist with overlapping but non-identical columns. `App\update_excel` points at `update_excels` — the empty one. On staging the utility/adjustment feature therefore has no data. Whether production is in the same state must be checked before anything is concluded from it.

**Empty tables suggesting abandoned features:** `payments`, `inventories`, `stop_sells`, `rate_linears`, `rate_non_linears`, `permissions`, `permission_role`, `permission_user`, `admin_user_permissions`, `personal_access_tokens`, `update_excels`, `ezee_sync_logs`, `announcement`.

### Missing indexes

Every hot table except `role_user` reports **0.0 MB of index data**. Confirmed via `SHOW INDEX`:

| Table | Indexes present | Missing, and why it hurts |
|---|---|---|
| `bookings` | PK, `(listing_id, check_in, check_out)` | `user_id`, `status`, `folio_no`, `created_at` — the dashboard sums `WHERE status >= 5` over 64k rows on every load |
| `ezee_bookings` | PK, `RoomName`, `End`, `book_id` | **`SubBookingId`**, `TransactionId`, `Start`, `status`, `Source` |
| `users` | PK only | **`email`** — every login and every `unique:users` validation full-scans 57k rows |
| `data_logs` | PK only | `related_id`, `title`, `created_at` — 197k rows, 80 MB |
| `listing_reports` | PK only | `(listing_id, year, month)` — the lookup every report write and every owner report page performs |

The unique key on `(SubBookingId, TransactionId)` that migration `2026_09_01_000007` tries to add is **skipped on staging**, because the migration correctly refuses to run while duplicates exist. There are only **3** such duplicate pairs — a trivial cleanup that would unlock the index.

---

## 6. End-to-end workflow: eZee booking → owner visibility

### Step 1 — Hourly sync (`hour:update`)

Cron runs `artisan schedule:run` every minute; `Console\Kernel` schedules `hour:update` **hourly with `withoutOverlapping(120)`**. That overlap guard was added on 2026-08-14 and is holding (see §9.3).

`HistoricalApi::handle()`:
1. Picks up to 50 `ezee_bookings` from the last 30 days that still have no `folio_no` and fetches each folio via a synchronous `RetrieveListofBills` call, using the credentials of the property identified by the first five characters of `TransactionId`.
2. For each of the five properties, POSTs an XML `Booking` request to `https://live.ipms247.com/pmsinterface/getdataAPI.php` for the window **−3 days to +6 months**.
3. Parses the XML into an array and walks two structurally different response shapes (single reservation vs. list) through two near-identical ~250-line blocks.
4. For each reservation, looks up `(SubBookingId, TransactionId)`. If absent, inserts and fetches the folio; if present, **updates on `SubBookingId` alone**.
5. Calls `EzeeAutoAssign::reconcile()` if `config('ezee.auto_assign')` is on.

The unit is read from `eZeePMSRoomid` (e.g. `C2-07-10`) into `EzeeBooking.RoomName`, falling back to `RoomName` if eZee ever starts sending it.

### Step 2 — Reconcile (`EzeeAutoAssign`)

Deliberately a reconcile pass over settled state rather than a per-booking reaction, because eZee reports a room move as a changed `eZeePMSRoomid` on an existing reservation, and within one sync the moves can arrive in any order. Order of operations:

1. Load the unit map. Resolution is **property-first**: `hotelCode|unit`, where the hotel code comes from the `TransactionId` prefix. Name-only fallback applies only for names that belong to exactly one property, cross-checked against eZee's own `ezee_rooms` inventory so a name-only match can never cross properties.
2. Select candidates: `RoomName` non-empty **and** `End >= today`, ordered by `Start`.
3. **Moves first**, then new assignments — a unit is often only free because the guest in it is moving out in the same run.
4. For a new assignment: if a live booking already records this exact stay on this unit, **adopt** it (link, don't duplicate). If a live booking overlaps the dates, **log a conflict and change nothing**. Otherwise create a guest `User` (`ezee_tmp = 1`), create the `Booking` with the `EzeePricing::breakdown()` figures, set `ezee_bookings.book_id` and `status = 8`, all inside a transaction with `lockForUpdate()` re-read to survive a race with manual assignment.
5. Close conflicts that no longer apply, so the review queue does not grow without bound.

Both trails are written: `ezee_assignment_logs` (the audit-log screen) and `data_logs` (system-wide).

### Step 3 — Human review

`/admin/ezee/booking` (All / Assigned / Unassigned tabs), `/admin/ezee/room-mapping`, `/admin/ezee/assignment-log`. Outstanding conflicts are surfaced on the booking list itself, not only on the log, so the person doing the work sees them where they work.

### Step 4 — Owner visibility

Two independent paths:

- **Calendar and dashboard** read `bookings` directly and are live the moment a booking exists.
- **Monthly statements** (`/owner/report/revenue`, `/owner/report/payment`) read `listing_reports`, which is written **only as a side effect of an admin running the monthly export** for that listing and month. An owner cannot see a month's statement until someone in the office generates it.

### Step 5 — M&A commission

`EzeePricing::marketingFee()` is the rate table. It is date-banded, with a hard cutover at **`CUTOVER_V6 = 2026-09-01`**: everything before that date is the historical record of what was actually charged and is never recomputed; corrections apply forward only.

Rates: Booking.com 18% commission + 2.8% PSF, from the cutover on a base that includes cleaning SST and with Booking.com's own 8% applied on top; Airbnb 15.9% on the untaxed base; Traveloka 17%; Expedia 15%/20%; CTrip 15%/0%; walk-in and website 8%; Agoda, Long Term Rental, Tiket.com, Monthly Rental and Ruiying zero. Source names carry booking-reference suffixes (`Booking.com-13707539`), so matching is by **longest-prefix** against a known-channel list.

**Reports read the stored `ota_fee`, not a recomputation.** This was a deliberate fix — recomputing applied today's rates to historical bookings, so the same export run either side of a rate change produced two different documents.

The consequence to be clear about: the 64,638 bookings already in the table carry fees stamped by the **older, known-imperfect** logic — including the Booking.com shortfall of roughly RM6 per booking that the v6 spec identifies. Reports now faithfully reproduce those historical figures. There is no reconciliation of the historical under/over-charge, and none is attempted. If the business wants one, it is a separate exercise.

---

## 7. Scheduled jobs and external integrations

### Scheduled

| Command | Schedule | Notes |
|---|---|---|
| `hour:update` (`HistoricalApi`) | hourly, `withoutOverlapping(120)` | The only scheduled job |

### On-demand console commands

`ezee:auto-assign` (`--dry-run`, `--from`, `--close-stale`), `ezee:sync-rooms`, `ezee:list-rooms`, `ezee:derive-room-mapping`, `ezee:suggest-room-ids`, `ezee:backfill-room-ids`, `ezee:backfill-folio`, `ezee:fill-folio-numbers`, `ezee:bookings`, `book:reminder`, `update:version`.

`book:reminder` sends booking reminder and feedback emails but is **not scheduled**, and both of its mailables reference view files that do not exist. It is dead.

### External services

| Service | Endpoint | Status |
|---|---|---|
| eZee PMS — bookings | `live.ipms247.com/pmsinterface/getdataAPI.php` | Live, hourly, XML |
| eZee PMS — folios | `live.ipms247.com/index.php/page/service.kioskconnectivity` | Live, JSON |
| Billplz | `www.billplz.com/api/v3/bills` | **Unconfigured** — no keys in any `.env` |
| SMTP | `mailpit:1025` on staging | Non-delivering on staging by design |

**A hardcoded session cookie is pinned into the eZee booking request** in `HistoricalApi.php` (`AWSALB`, `AWSALBCORS`, `SSID` values baked into `CURLOPT_HTTPHEADER`). It presumably no longer matters to eZee's load balancer, but it is a captured session identifier committed to a public-ish repository and it should be removed.

Neither eZee call checks `curl_errno()` or the HTTP status. A failed or truncated response silently parses to nothing and the sync reports success.

---

## 8. Screen inventory

240 routes: 168 admin, 19 owner, 53 public/guest.

### 8.1 Admin (`/admin/*`, `auth` + `role:admin`)

Sidebar sections and their screens, with the permission each is *displayed* under (see §10.2 — none of these are enforced server-side):

**Overview**
| Screen | Route | View | Notes |
|---|---|---|---|
| Dashboard | `/admin/dashboard` | `admin/home/index` | `dashboard.view`. **Runs 6 aggregate queries inside the Blade template**, including `SUM(price)` over 64k unindexed rows |
| Calendar (all) | `/admin/calendar` | `admin/listing/calendar` | `calendar.view` |
| Week grid | `/admin/view/calendar` | `admin/home/viewCalendar` | 7-day occupancy grid |

**Properties**
| Screen | Route | View |
|---|---|---|
| Listings | `/admin/listing` | `admin/listing/index` |
| Create / Edit / Show | `/admin/listing/create`, `/{id}/edit`, `/{id}` | `listingCreate`, `listingEdit`, `listingDetail` |
| Details | `/admin/listing/{id}/details` | `admin/listing/listingDetail` |
| Pricing | `/admin/listing/{id}/price` | `admin/listing/listingPrice` |
| Images | `/admin/listing/{id}/images` | `admin/listing/listingImages` |
| **Video** | `/admin/listing/{id}/video` | **missing — `admin.listing.listingVideo`** |
| **Zones (per listing)** | `/admin/listing/{listingId}/zone` | **missing — `admin.setting.zone.listingZone`** |
| Amenities (per listing) | `/admin/listing/{listingId}/amenities` | `admin/setting/amenities/listingAmenities` |
| Per-listing calendar | `/admin/listing/{id}/calendar` | `admin/listing/calendar` |
| Groups | `/admin/group` (resource) | `admin/listing/group/*` |
| Chart report | `/admin/listing/chart/report` | `admin/listing/report` |

**Bookings & eZee**
| Screen | Route | View |
|---|---|---|
| Bookings | `/admin/book` (resource) | `admin/listing/book/index` |
| Booking detail / edit | `/admin/book/{id}`, `/{id}/edit` | `book/detail`, `book/edit` |
| Create booking on listing | `/admin/listing/{id}/book` | `admin/listing/bookCreate` |
| eZee bookings — All / Assigned / Unassigned | `/admin/ezee/booking`, `/assigned_booking`, `/unassigned_booking` | `book/ezeeBook` |
| eZee booking edit | `/admin/ezee/booking/{id}/edit` | `book/ezeeBookEdit` |
| eZee report | `/admin/ezee/booking_report` | `book/ezeeBookReport` |
| **eZee by property** | `/admin/ezee/bookings-by-property` | `book/ezeeBooksByProperty` — **loads all 60,673 rows** |
| eZee upload | `/admin/ezee/upload_bookings` | `book/upload_ezee_bookings` |
| Room mapping | `/admin/ezee/room-mapping` | `admin/ezee/room_mapping` |
| Assignment log | `/admin/ezee/assignment-log` | `admin/ezee/assignment_log` |
| eZee groups | `/admin/ezee/group` | `admin/listing/ezeeGroup/*` |
| Sync history | `/admin/booking/histroy/api` | `admin/listing/history` — note the typo in the URL |

**People**
| Screen | Route | View |
|---|---|---|
| Owners | `/admin/owners` (resource) | `admin/user/ownerList`, `ownerCreate`, `ownerEdit` |
| Owner's listings | `/admin/owners/{id}/listing` | reuses listing views |
| Users | `/admin/users` (resource) | `admin/user/index`, `editUser`, `userDetail` |
| User verification | `/admin/user/verify` | `admin/user/userVerify` |
| Admins | `/admin/admin` (resource) | `admin/user/adminList`, `adminCreate`, `adminEdit` |

**Settings**
| Screen | Route | View |
|---|---|---|
| Zones | `/admin/setting/zone` (resource) | `admin/setting/zone/*` |
| Amenities | `/admin/setting/amenities` (resource) | `admin/setting/amenities/*` |
| Estimates | `/admin/setting/estimate` | `admin/setting/estimate` |
| Logs | `/admin/setting/logs` | `admin/setting/logs` |
| Subscribers | `/admin/subscribe` | `admin/home/subscribeList` |
| **File manager** | `/admin/filemanager` | **missing — `admin.home.filemanager`** |
| Admin roles | `/admin/setting/admin-roles` | `admin/settings/admin_roles` |

**Hidden / partly broken**
- Approval-by-month (`/admin/approval/month_wise`) and Approval review (`/admin/approval/review`) are commented out of the sidebar "on request" but remain routed and reachable.
- **`/admin/approval/review/{id}/edit` renders `admin.approval.reviewEdit`, which does not exist.**
- Payments (`/admin/payment/upcoming`, `/past`) are commented out of the sidebar but routed; the `payments` table is empty.

### 8.2 Owner (`/owner/*`, `auth` + `role:owner`)

Sidebar: Dashboard, Listings, Calendar, Report, Change Password. Two further screens are routed but **not linked anywhere**.

| Screen | Route | View | State |
|---|---|---|---|
| Dashboard | `/owner/dashboard` | `owner/home/index` | Month KPIs: bookings, revenue, occupancy, ADR, average length of stay, accumulated YTD sales, source and category breakdowns, 6-month occupancy and ADR trend |
| Listings | `/owner/listing` | `owner/listing/index` | Correctly scoped to `Auth::id()` |
| Calendar | `/owner/calendar` | `owner/listing/calendar` | **IDOR — see §10.4** |
| Booking detail | `/owner/book/{id}` | `owner/listing/book/detail` | Correctly guarded (403 on foreign listing) |
| Monthly report | `/owner/listing/chart/report` | `owner/listing/report` | **The listing dropdown does nothing — see §9.5** |
| Performance | `/owner/performance` | `owner/listing/performance` | Controller is a one-line stub returning the view with no data |
| Revenue report | `/owner/report/revenue` | `owner/report/revenue` | **Unlinked. IDOR — see §10.3** |
| Payment report | `/owner/report/payment` | `owner/report/payment` | **Unlinked. IDOR — see §10.3** |
| Change password | `/owner/change_password` | `owner/home/profile` | **Critical auth flaw — see §10.1** |
| Excel export | `/owner/listing/excel/export`, `/owner/calendar/export` | — | Raw `header()` + `exit()`, bypasses the Laravel response |

### 8.3 Public and guest

**Working** (confirmed live, HTTP 200): `/`, `/homepage`, `/about`, `/service`, `/designs`, `/get/estimate`, `/location/search`, `/listing/{key}`, `/blog`, `/blog/{slug}`, `/login`, `/register`, `/sitemap.xml`, `/robots.txt`.

**Broken** (confirmed live, HTTP 500):

| Route | Cause |
|---|---|
| `/contact` | `View [auth.contact] not found` |
| `/policy` | `View [auth.policy] not found` — **linked from the footer of every v2 page** |
| `/terms` | `View [auth.terms] not found` — **linked from the footer of every v2 page** |
| `/announcement` | Public route renders an **admin** view; `admin/layout.blade.php` dereferences `Auth::user()->name` on null |

All four appear in the staging error log with those exact messages.

**Guest portal (`/home/*`, role `user`) — substantially broken.** Only the dashboard renders. Every other screen points at a view that does not exist:

| Route | Missing view |
|---|---|
| `/home/dashboard` | *(works)* |
| `/home/profile` | `user.home.profileEdit` |
| `/home/profile/verify` | `user.home.verification` |
| `/home/booking/history` | `user.home.bookingHistory` |
| `/home/listing/{id}` | `auth.newTheme.propertyDetail` |
| `/home/booking/new` | `auth.newTheme.confirm` |

`Auth\RegisterController::register()` also redirects to `/home` on success, and **`/home` is not a route** — the group is `prefix('home')` and the only dashboard route is `/home/dashboard`. Every successful registration lands on a 404.

---

## 9. Correctness and data-integrity findings

### 9.1 "Remove Duplicates" would cancel 7,916 legitimate bookings — **Critical**

`BookController::ezeeRemoveDuplicates()` (route `POST /admin/ezee/bookings/remove-duplicates`) groups `ezee_bookings` by **`SubBookingId` alone**, keeps one row per group, and sets `status = 1` (cancelled) on the rest — capped at 300 per click.

But `SubBookingId` is not unique across properties. Measured on staging:

- **20,331** `SubBookingId` groups span more than one property.
- The step-1 query currently selects **7,916 rows** for cancellation.

Those are overwhelmingly *distinct reservations at different properties*, not duplicates. The migrations added since June carry explicit comments saying `SubBookingId` alone is not unique; this button was never updated to match.

A second pass groups by `FirstName + LastName + Start + End + TotalAmountAfterTax`, which will also collapse genuine group bookings — several units booked by one person for the same dates at the same rate is exactly the shape of a family or corporate booking.

**Fix:** group by `(SubBookingId, TransactionId)`; drop or heavily qualify the name-based pass.

### 9.2 The sync updates across properties — **High**

In `HistoricalApi::handle()`, existence is checked correctly on the pair:

```php
$exist = EzeeBooking::where([['SubBookingId', $sub_booking_id],
                            ['TransactionId', $transaction_id]])->first();
```

but the update is not:

```php
EzeeBooking::where("SubBookingId", $sub_booking_id)->update([
    'RoomTypeName' => ..., 'RoomName' => ...,
    'TotalExtraCharge' => ..., 'TotalAmountAfterTax' => ...,
]);
```

With 20,410 shared `SubBookingId` values, **every hourly run copies one property's room name, room type, extra charge and total onto unrelated bookings at other properties.** This is a live, ongoing corruption path that touches the amount fields the M&A fee is derived from.

**Fix:** add `TransactionId` to the update's `where`, or update by primary key from `$exist`.

### 9.3 Lock-wait timeouts — **High** (mitigated, not cured)

1,520 `SQLSTATE[HY000]: 1205 Lock wait timeout exceeded` errors in the staging log — the single most frequent error by a wide margin. Every one of them is on the statement in §9.2. Because `SubBookingId` is unindexed, InnoDB takes next-key locks across the whole 60k-row table for each of those updates, and concurrent runs deadlock.

Occurrences by day: 2026-06-11 (4), 07-13 (35), 07-18 (177), 07-19 (260), 07-20 (266), 07-21 (118), 07-23 (71), 07-24 (38), **08-14 (551)**. `withoutOverlapping(120)` was added on **2026-08-14** and there have been none since. The mitigation works; the underlying unindexed cross-property update does not.

Also present: 250 × `SQLSTATE[HY000] [2002] Connection refused` — periods where MySQL was down or refusing connections.

### 9.4 Auto-assignment is blocked by missing unit names — **Blocker**

`EZEE_AUTO_ASSIGN` is absent from staging's `.env`, so it defaults to `false`. The local `.env` has it `true`. The gate on turning it on is data, not code:

| Measure | Value |
|---|---|
| `ezee_bookings` total | 60,673 |
| …with a non-empty `RoomName` | **1,354 (2.2%)** |
| Current/future stays (`End >= today`) | 814 |
| …with a non-empty `RoomName` | **270 (33%)** |

Two-thirds of current and future reservations carry no unit at all and can never be auto-assigned. `RoomName` only arrives on rows the sync has touched since the field was added in June 2026, and only when eZee supplies `eZeePMSRoomid`.

A dry run against those 270 candidates:

| Assigned | Adopted | Moved | Conflicts | Unmapped room | Already correct | Failed |
|---:|---:|---:|---:|---:|---:|---:|
| 4 | 1 | 0 | **21** | 33 | 211 | 0 |

The engine itself is sound — 211 already correct, 0 failures. The 21 conflicts are genuine placement disagreements needing a person: in the 14 blocking bookings sampled, 12 are themselves assigned eZee reservations with their own room names, so these are real double-bookings between the mapping and reality, not engine bugs.

One instructive case: listing 283 (`Ekocheras H-13A-3A`) holds booking #67121, a 2026-09-01→09-07 "Website" stay linked to an eZee row with **no `RoomName`** covering 2026-08-12→09-07, while eZee separately reports seven short stays for unit `H-13A-3A` across the same window under four different guest names. Either the long booking is stale, or the mapping for that unit is wrong. Cases like this need resolving before auto-assign is switched on, not after.

**Recommended order:** (1) backfill `RoomName` — `ezee:backfill-room-ids` exists for this; (2) map the 33 unmapped units; (3) work the 21 conflicts down; (4) re-run the dry run; (5) enable.

### 9.5 The owner's report listing selector does nothing — **Medium**

`resources/views/owner/listing/report.blade.php:20` renders a `listing_id` select that auto-submits. `Owner\ListingController::report()` **never reads `$request->listing_id`** — it takes `Listing::where('user_id', Auth::id())->where('status',1)->first()`. An owner with several properties always sees the first one, whatever they pick. The surrounding `$listingData` / `$id` block is a redundant no-op that reads as if it were doing the selection.

### 9.6 `ORDER BY` incompatible with `only_full_group_by` — **Medium**

11 occurrences in the log, from `Owner\ListingController::report()`:

```sql
SELECT DATE_FORMAT(check_in,'%m') month, DATE_FORMAT(check_in,'%Y') year
FROM bookings WHERE listing_id = ? ... GROUP BY month, year ORDER BY check_in ASC
```

`ORDER BY check_in` on a grouped query is rejected under MySQL's default `only_full_group_by`. The owner's monthly report 500s for the affected listing. Fix: `ORDER BY year, month`.

### 9.7 Compiled-view write failures — **Medium**

46 × `file_put_contents(/var/www/moka/storage/framework/views/…): Failed to open stream`. Directory ownership is correct (`www-data:deployers`, `drwxrwsr-x`), so this is a race between concurrent requests compiling the same Blade after `view:clear`. Caching views at deploy time (§11.1) removes the window entirely.

### 9.8 Smaller correctness issues

- `EzeeAutoAssign::sameStay()` matches on an **exact** `check_in`/`check_out` pair. Any date drift between eZee and the local booking turns an adoption into a conflict.
- `EzeeAutoAssign::assign()` creates a `User` per assignment with no de-duplication by email — the direct cause of 50,422 `ezee_tmp` users and 1,984 duplicate email groups.
- `Role::find(2)` and `Role::find(3)` are hardcoded role IDs in `EzeeAutoAssign` and `WebController`.
- 355 `ezee_bookings` rows have `End <= Start`. These are silently excluded from assignment by the `End >= today` filter and produce `nights = 0` in `EzeePricing`, so their fee is zero.
- `DataLog::create(['title' => 'sendnotify', 'data' => '', 'related_id' => 20317, ...])` in `HistoricalApi` writes a junk row with a hardcoded ID and empty payload for **every new booking**. `data_logs` is now the largest table at 197,746 rows / 80.6 MB, of which 189,376 are `folio_no` and 10,799 are `sendnotify`. Only 48 rows relate to actual assignment activity.
- `Admin\HomeController::view_calendar()` reads `$booked` after a loop that may never assign it.
- Billplz signatures are compared with `!==` rather than `hash_equals()`.
- `$result->id` is dereferenced after `json_decode` with no check that the decode produced an object.

---

## 10. Security findings

### 10.1 Any owner can change any user's password — **Critical**

```php
// routes/routes/owner.php  (inside auth + role:owner)
Route::put('/update/change_password/{id}', 'Owner\HomeController@update');
```

```php
// Owner\HomeController::update()
$user = User::find($id);          // ← $id straight from the URL. No ownership check.
if (!empty($request->password)) { $data['password'] = Hash::make($request->password); }
if ($request->password == $request->c_password) { $user->update($data); }
```

Nothing ties `$id` to `Auth::id()`. Any authenticated owner can `PUT /owner/update/change_password/1` and set the password of **any account in the system, including any of the 23 admins**, then log in as them. This is full vertical privilege escalation from the lowest authenticated role.

**Fix:** ignore the route parameter entirely and operate on `Auth::user()`; require the current password.

### 10.2 Admin RBAC is not enforced — **Critical**

Two independent failures compound:

1. **`admin_can()` fails open.** `app/Helpers/admin_permissions.php` treats a null or empty `admin_role` as `super_admin`. On staging, **`admin_role` is NULL for all 58,440 users**, and `admin_user_permissions` has **0 rows**. Every one of the 23 admin-role users is therefore a super admin.
2. **The middleware is applied nowhere.** `CheckAdminPermission` is registered as `adminperm` in `Http\Kernel` and appears in **zero routes** (`grep -c adminperm routes/routes/admin.php` → 0). `admin_can()` is called server-side in only two controllers, both about role management itself. Everywhere else it appears **only in Blade templates**, hiding sidebar links.

The four roles in `config/admin_permissions.php` (`super_admin`, `manager`, `finance`, `operations`) and the whole `/admin/setting/admin-roles` screen are therefore decorative. A "finance" admin who types `/admin/listing/1/edit` gets the page.

**Fix:** apply `adminperm:` to every admin route group; change the null-role default from `super_admin` to no permissions once roles are actually assigned.

### 10.3 Owner report IDOR — **High**

`Owner\ReportController::revenue()` and `payment()`:

```php
if (empty($listingId)) {
    $listingIds = Listing::withArchived()->where('user_id', $authId)->pluck('id')->toArray();
} else {
    $listingIds[] = $listingId;      // ← no ownership check
}
$revenues = ListingReport::whereIn('listing_id', $listingIds)->get();
```

`GET /owner/report/revenue?listing_id=<any id>` returns another owner's revenue and payment statements — income, platform income, utilities, adjustments. Compare `Owner\HomeController::index()`, which does check ownership, and `Owner\CalendarController::show()`, which returns 403. The two report actions are the outliers. They are not linked in the sidebar, which limits accidental discovery but is not a control.

### 10.4 Owner calendar IDOR — **High**

`Owner\CalendarController::allBooks()`:

```php
$listing = $selectedId ? Listing::withArchived()->where('user_id',$authId)->find($selectedId) : null;
$books   = $selectedId ? Booking::where('listing_id',$selectedId)->where('status','>',1)->get() : collect();
```

The **header** is scoped (`$listing` is null for a foreign listing) but the **events are not**. `GET /owner/calendar?listing_id=<foreign id>` renders another owner's bookings with guest names, nightly rates, cleaning fees, OTA fees and totals.

### 10.5 Duplicate emails with no unique index — **Medium**

`users` has **no index on `email`**. `RegisterController` validates `unique:users`, but that is application-level only and is bypassed entirely by `EzeeAutoAssign`, which creates guest users directly.

Staging state: 1,984 duplicate-email groups; **10** groups contain more than one password-bearing account; **3** pair a real account with an eZee temp record. `Auth::attempt()` takes the first matching row, so in those cases a legitimate user may be authenticated against — or locked out by — the wrong record. 57,709 users have no password at all.

### 10.6 Other

- **Hardcoded eZee session cookie** committed in `HistoricalApi.php` (`AWSALB`, `AWSALBCORS`, `SSID`). Remove.
- **`/payment/callback` is CSRF-exempt** and reachable unauthenticated. It fails closed today only because `BILLPLZ_SECRET` is unset; signature comparison is not constant-time.
- **`/announcement` is public and renders an admin view.** It 500s, so nothing leaks — but the intent of exposing an admin template on an unauthenticated route should be reviewed rather than left as an accidental fix.
- **`APP_URL=http://35.215.128.58`** — staging is served over plain HTTP. `SecurityHeaders` sets `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection` and `Referrer-Policy`, but there is no HSTS and no Content-Security-Policy.
- **`Log::info('Booking index called', ['all_params' => $request->all()])`** in `BookingController::index()` writes every query parameter — including guest name and email search terms — to a log file at `LOG_LEVEL=debug` with no rotation policy evident (15 MB and growing).
- **No SQL injection was found.** The two raw `DB::select()` blocks in `BookController` are fully static; there are no `whereRaw`/`orderByRaw` calls with interpolated input. `getSafeOrderColumn()` in `BookingController` correctly whitelists DataTables sort columns.

---

## 11. Performance findings

### 11.1 Config and routes are never cached — **High, trivial to fix**

`bootstrap/cache/` on staging contains only `packages.php` and `services.php`. `scripts/deploy.sh` runs `view:clear`, `route:clear`, `config:clear` and stops. Every request re-parses `.env` and all four route files, and Blade templates compile lazily (which also causes §9.7).

**Fix:** append `config:cache`, `route:cache`, `view:cache` to the deploy script.

**Caveat that must be handled first:** `env()` is called outside `config/` in two places, and both return `null` once config is cached —

- `app/Http/Middleware/Localization.php` — `env('APP_LOCALE', 'en')`. `APP_LOCALE` is not defined in any `.env`, so the default carries it today, but under `config:cache` the fallback still works. Low risk.
- `app/Http/Controllers/User/PaymentController.php` — five `BILLPLZ_*` reads. Already null. Move to a `config/billplz.php` before caching, or the Billplz integration can never be enabled.

### 11.2 Missing indexes

See the table in §5. In priority order:

1. `users(email)` — every login and registration full-scans 57k rows.
2. `ezee_bookings(SubBookingId, TransactionId)` — unique, after cleaning the **3** blocking duplicate pairs. Removes the §9.3 lock storm and makes the sync's per-reservation lookups indexed.
3. `bookings(status)` and `bookings(user_id)` — the dashboard aggregates and the booking list joins.
4. `listing_reports(listing_id, year, month)` — every report read and write.
5. `data_logs(related_id)`, `data_logs(created_at)`.

### 11.3 Unbounded result sets

| Location | Problem |
|---|---|
| `BookController::ezeeBookingsByProperty()` | `ezee_group_id` is **NULL on all 60,673 rows**, so the "fallback" branch always fires and loads the entire table into one page. Route `/admin/ezee/bookings-by-property`. |
| `EzeeAutoAssign::reconcile()` | `$candidates` loads every matching `EzeeBooking` into memory with no `chunk()`. Fine at 270 rows today; grows with `RoomName` coverage. |
| `Owner\CalendarController::allBooks()` | Every booking for a listing, unbounded by date. |
| `ListingController::listingApproval()` | `update_excel::orderBy(...)->get()` with no limit. (Dead code — unrouted.) |

### 11.4 N+1 query patterns

| Location | Pattern |
|---|---|
| `Owner\CalendarController::allBooks():48` | `User::find($book->user_id)` **and** `EzeeBooking::where('book_id',…)` per booking — 2 queries × N |
| `Owner\CalendarController::export():157,180` | `User::where('id',…)` per listing, `User::find()` per booking |
| `Admin\CalendarController::export():255,278` | Same pattern |
| `Owner\HomeController::index():118` | One `Booking` query per month across a 6-month trend loop |
| `Owner\ListingController::report():95` | One `Booking` query per month |
| `ListingController::importApproval():4511-4514` | **Four separate `pluck()` calls on the same `Listing` row**, inside a loop |
| `admin/user/ownerList.blade.php:48` | `App\Models\Listing::where('user_id',…)->count()` inside the owner loop |
| `admin/user/userDetail.blade.php:8`, `ownerEdit.blade.php:8` | Queries in the template |
| `admin/home/index.blade.php:7-14` | **Six aggregate queries in the dashboard template**, including `SUM(price)` over 64k unindexed rows |

`Admin\CalendarController::index()` shows the right pattern — it preloads `EzeeBooking::whereIn('book_id', …)->keyBy('book_id')` with an explicit comment saying the loop used to query per booking. The same fix has not been applied to the other four sites.

### 11.5 Synchronous work in the request and in the sync

`QUEUE_CONNECTION=sync`, so `Mail::queue()` sends inline and every export builds in-request. `ListingController::reportExports()` builds a multi-listing XLSX synchronously inside an HTTP request.

In `HistoricalApi`, folio lookups are **synchronous HTTP calls inside the reservation loop** — up to 50 up front plus one per newly created booking, each with a 10-second timeout. A slow eZee endpoint stretches the sync linearly.

The XLSX exports in both calendar controllers call `ob_end_clean()` then raw `header()` and `exit()`, bypassing Laravel's response pipeline (and therefore `SecurityHeaders`). `ob_end_clean()` warns if no buffer is active.

---

## 12. Dead code and duplication

### Confirmed dead

| Item | Lines | Evidence |
|---|---:|---|
| `BookController::historicalAPI()` | 474 | Referenced only in a commented-out constructor line |
| `BookingController::historicalAPI()` | 414 | Same. **Near-identical to the above** — 161 differing lines out of ~450 |
| `API/EZEEAPIController` | 478 | Not referenced by any route |
| `ListingController::listingApproval()` | ~6 | Unrouted, and renders a view that does not exist |
| `WebController` — 14 of 30 public methods | — | Only 16 are routed. Includes `welcome()`, `login()`, `register()`, `registerOwnerStore()`, `listProperty()`, `consultation()` |
| `BookingCompleteListener` mail path | — | All three mailables render views that do not exist |
| `book:reminder` command | 127 | Not scheduled; both mailables render missing views |
| `Export/CalendarExport2` | 57 | 88 differing lines from `CalendarExport` — a near-copy |
| `app/Models/{User,ListingAmenity,ListingImage,ListingReview,EstimateRequest}` | — | Unreferenced. `Models\Listing` is used by 2 Blade files and should be replaced by `App\Listing` |

**Together with the third copy in `HistoricalApi`, the eZee sync logic exists in three places.** Only the command is live.

### Structural duplication

- `ListingController::reportExport()` (1,526 lines) and `reportExports()` (2,165 lines) share **806 identical lines** — 53% of the smaller method. Two more partial copies of the same spreadsheet-building block exist at lines ~4,738 and ~5,975.
- The dead `$ota_cal`, `$new_ota`, `$new_ota1`, `$chackdate`, `$todays`, `$createdMonth`, `$create_check_in` locals are computed and never used in **all four** copies, alongside ~20 lines of commented-out Traveloka/Expedia branches in each.
- `HistoricalApi::handle()` contains two ~250-line blocks that differ only in which array level they read from — the classic "single reservation vs. list" XML shape, handled by duplication rather than normalisation.

### Zero test coverage

There is no `tests/` directory. ~24,100 lines of PHP, including the commission engine that determines what owners are paid, with no automated verification.

---

## 13. Prioritised recommendations

### Immediate — security, before anything else

1. **`Owner\HomeController::update()`** — operate on `Auth::user()`, ignore `{id}`, require the current password. (§10.1)
2. **Disable or fix "Remove Duplicates"** — it can cancel 7,916 real bookings on one click. Group by `(SubBookingId, TransactionId)`. (§9.1)
3. **Apply `adminperm:` to admin route groups**, and stop treating a null `admin_role` as super admin once real roles are assigned. (§10.2)
4. **Scope `Owner\ReportController` and `CalendarController::allBooks()` to `Auth::id()`.** (§10.3, §10.4)
5. **Remove the hardcoded eZee session cookie.** (§7)

### High — correctness

6. **Add `TransactionId` to the sync's update `where`.** One line; stops ongoing cross-property corruption and the lock storm. (§9.2)
7. **Clean the 3 duplicate `(SubBookingId, TransactionId)` pairs and let the unique index apply.** (§5)
8. **Fix `/contact`, `/policy`, `/terms`** — `/policy` and `/terms` are linked from every public footer. (§8.3)
9. **Fix the `ORDER BY` / `only_full_group_by` query** in the owner report. (§9.6)
10. **Make the owner report honour `listing_id`.** (§9.5)

### High — unblock auto-assignment

11. Backfill `RoomName` (`ezee:backfill-room-ids`), map the 33 unmapped units, work the 21 conflicts down, re-run `--dry-run`, then enable `EZEE_AUTO_ASSIGN`. (§9.4)

### Medium — performance

12. Add `config:cache`, `route:cache`, `view:cache` to `deploy.sh` — after moving `BILLPLZ_*` into a config file. (§11.1)
13. Add the five indexes in §11.2.
14. Paginate `/admin/ezee/bookings-by-property`. (§11.3)
15. Move the dashboard's six aggregates out of the Blade template and cache them. (§11.4)
16. Eager-load the four remaining N+1 calendar loops, following the pattern already used in `Admin\CalendarController::index()`. (§11.4)
17. Prune `data_logs` and stop writing the empty `sendnotify` rows; index what remains. 80 MB of 96 MB total is one junk table. (§9.8)
18. Switch `QUEUE_CONNECTION` off `sync` for mail and exports. (§11.5)

### Medium — code health

19. Delete the three dead `historicalAPI()` copies and `EZEEAPIController` (~1,366 lines in one commit, no behaviour change).
20. Extract the shared 806 lines of `reportExport`/`reportExports` into one builder.
21. Collapse the three model namespaces into `App\Models\`, and replace `App\Models\Listing` in the two owner Blades with `App\Listing` so archiving behaves consistently.
22. Add tests for `EzeePricing` first — it is pure, self-contained, and decides what owners get paid.
23. Add a CI check that every `view()` target exists. Four of the six broken screens found here are this one class of defect, and commit `4c47841` ("Add the missing EZEE admin views") shows it has bitten before.

---

## 14. Appendix — verified metrics

**Code:** 42 controllers · 68 model classes · 12 console commands · 108 Blade views · 64 migrations · 240 routes (168 admin / 19 owner / 53 public) · 17,374 lines across controllers · ~24,100 PHP LOC · **0 tests**

**Largest controllers:** `ListingController` 7,803 · `BookController` 2,362 · `BookingController` 1,458 · `WebController` 556 · `EZEEAPIController` 478 (dead)

**Staging data:** `data_logs` 197,746 · `bookings` 64,935 · `ezee_bookings` 60,673 · `users` 57,487 (50,422 `ezee_tmp`) · `role_user` 55,766 (23 admin / 207 owner / 57,684 user) · `listing_reports` 1,896 · `listings` 303 (168 active) · `ezee_room_mappings` 196 (138 mapped) · `ezee_rooms` 167 · `ezee_assignment_logs` 257

**eZee data quality:** `RoomName` populated on 1,354 / 60,673 (2.2%) · current-and-future stays 814, of which 270 have a unit · `book_id` null on 10,840 · `End <= Start` on 355 · duplicate `SubBookingId` groups 20,410 · groups spanning >1 property 20,331 · duplicate `(SubBookingId, TransactionId)` pairs **3**

**Booking status distribution:** 1 (cancelled) 129 · 3 (pending) 4 · 4 (paid) 1 · 5 (confirmed) 64,638 · 8 (completed) 163
**eZee status distribution:** 1 (cancelled) 209 · 5 (unassigned) 10,632 · 8 (assigned) 49,832

**Top eZee sources:** Booking.com 26,199 · PMS 8,865 · Agoda 6,517 · Airbnb 5,026 · Walk In 4,739 · CTrip 3,242 · Expedia 1,065 · Tiket.com 557 · Traveloka 273

**Staging error log (15 MB):** 1,520 lock-wait timeouts · 250 connection refused · 56 `View [auth.terms]` · 55 `View [auth.policy]` · 46 compiled-view write failures · 11 `only_full_group_by` · 10 `Class "App\update_excel" not found` · 9 `View [auth.contact]` · 9 `simplexml_load_string`

**Git:** 182 commits, 28 Apr – 2 Sep 2026. `withoutOverlapping` added 2026-08-14 (`b58a578`); missing eZee views added 2026-08-27 (`4c47841`).
