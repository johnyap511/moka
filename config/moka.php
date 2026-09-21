<?php

return [
    // The Delete button on the bookings list. Hidden since 7 Sep 2026: Cancel keeps history; delete does not.
    'show_delete_button' => (bool) env('MOKA_SHOW_DELETE', false),

    /*
     * Ground rule 27. The last month whose report has been sent to owners, as YYYY-MM.
     * That month and everything before it is stamped: jobs leave its bookings alone and
     * staff need a super admin password to change one. Move it forward when the next
     * monthly report goes out. It can only lock more than rule 17 does, never less.
     */
    'reported_through' => env('MOKA_REPORTED_THROUGH', '2026-08'),
];
