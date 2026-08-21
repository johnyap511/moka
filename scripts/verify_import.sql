-- Post-import integrity report.
--
-- Everything in the BROKEN section must read 0. A non-zero value there means
-- records point at rows that did not come across, which shows up in the admin
-- as listings with no owner, bookings on missing properties, or EZEE bookings
-- that claim to be assigned but open to nothing.
--
--   mysql -u USER DB -t < scripts/verify_import.sql

SELECT '=== VOLUMES ===' AS check_name, NULL AS n;

SELECT 'users'                     AS check_name, COUNT(*) AS n FROM users
UNION ALL SELECT 'owners (role 3)',        COUNT(*) FROM role_user WHERE role_id = 3
UNION ALL SELECT 'listings',               COUNT(*) FROM listings
UNION ALL SELECT 'listings active',        COUNT(*) FROM listings WHERE status = 1
UNION ALL SELECT 'bookings',               COUNT(*) FROM bookings
UNION ALL SELECT 'bookings current/future',COUNT(*) FROM bookings WHERE check_out >= CURDATE()
UNION ALL SELECT 'ezee_bookings',          COUNT(*) FROM ezee_bookings
UNION ALL SELECT 'ezee assigned (book_id)',COUNT(*) FROM ezee_bookings WHERE book_id IS NOT NULL AND book_id > 0
UNION ALL SELECT 'ezee_groups',            COUNT(*) FROM ezee_groups
UNION ALL SELECT 'listing_categories',     COUNT(*) FROM listing_categories
UNION ALL SELECT 'listing_price_details',  COUNT(*) FROM listing_price_details;

SELECT '=== OWNERSHIP ===' AS check_name, NULL AS n;

SELECT 'listings WITH an owner'    AS check_name, COUNT(*) AS n FROM listings WHERE user_id IS NOT NULL AND user_id > 0
UNION ALL SELECT 'listings WITHOUT an owner', COUNT(*) FROM listings WHERE user_id IS NULL OR user_id = 0
UNION ALL SELECT 'distinct owners holding listings', COUNT(DISTINCT user_id) FROM listings WHERE user_id IS NOT NULL AND user_id > 0;

SELECT '=== BROKEN (all must be 0) ===' AS check_name, NULL AS n;

SELECT 'listing.user_id -> missing user' AS check_name, COUNT(*) AS n
  FROM listings l LEFT JOIN users u ON u.id = l.user_id
 WHERE l.user_id IS NOT NULL AND l.user_id > 0 AND u.id IS NULL

UNION ALL
SELECT 'listing owner not role 3', COUNT(*)
  FROM listings l
  JOIN users u ON u.id = l.user_id
  LEFT JOIN role_user ru ON ru.user_id = u.id AND ru.role_id = 3
 WHERE l.user_id IS NOT NULL AND l.user_id > 0 AND ru.user_id IS NULL

UNION ALL
SELECT 'booking.listing_id -> missing listing', COUNT(*)
  FROM bookings b LEFT JOIN listings l ON l.id = b.listing_id
 WHERE b.listing_id IS NOT NULL AND l.id IS NULL

UNION ALL
SELECT 'booking.user_id -> missing user', COUNT(*)
  FROM bookings b LEFT JOIN users u ON u.id = b.user_id
 WHERE b.user_id IS NOT NULL AND b.user_id > 0 AND u.id IS NULL

UNION ALL
SELECT 'ezee.book_id -> missing booking', COUNT(*)
  FROM ezee_bookings e LEFT JOIN bookings b ON b.id = e.book_id
 WHERE e.book_id IS NOT NULL AND e.book_id > 0 AND b.id IS NULL

UNION ALL
SELECT 'ezee marked assigned but no book_id', COUNT(*)
  FROM ezee_bookings WHERE status = 8 AND (book_id IS NULL OR book_id = 0)

UNION ALL
SELECT 'ezee has book_id but not marked assigned', COUNT(*)
  FROM ezee_bookings WHERE status <> 8 AND book_id IS NOT NULL AND book_id > 0

UNION ALL
SELECT 'listing_categories -> missing listing', COUNT(*)
  FROM listing_categories lc LEFT JOIN listings l ON l.id = lc.listing_id
 WHERE l.id IS NULL

UNION ALL
SELECT 'listings with no rental category', COUNT(*)
  FROM listings l LEFT JOIN listing_categories lc ON lc.listing_id = l.id
 WHERE lc.listing_id IS NULL AND l.status = 1

UNION ALL
SELECT 'duplicate SubBookingId groups', COUNT(*) FROM (
    SELECT SubBookingId FROM ezee_bookings
     WHERE SubBookingId IS NOT NULL AND SubBookingId <> ''
     GROUP BY SubBookingId HAVING COUNT(*) > 1
) d;

SELECT '=== EZEE GROUPS (auth keys) ===' AS check_name, NULL AS n;

SELECT CONCAT(hotel_code, ' ', COALESCE(name, '?')) AS check_name,
       CASE WHEN auth_key LIKE '%9225-11f1-8' THEN 1 ELSE 0 END AS n
  FROM ezee_groups ORDER BY hotel_code;
