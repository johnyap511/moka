<?php

return [
    // The Delete button on the bookings list. Hidden since 7 Sep 2026: Cancel keeps history; delete does not.
    'show_delete_button' => (bool) env('MOKA_SHOW_DELETE', false),
];
