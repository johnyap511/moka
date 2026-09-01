<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Automatic assignment
    |--------------------------------------------------------------------------
    |
    | When enabled, the hourly sync assigns EZEE bookings to listings using the
    | room mapping, and follows room moves. Off by default: turning it on starts
    | writing bookings that owners can see, so it should be a deliberate act
    | after a dry run looks right.
    |
    */

    'auto_assign' => env('EZEE_AUTO_ASSIGN', false),

    /*
    | Only bookings still running or in the future are touched. A stay that has
    | already ended is history — moving it would rewrite an owner's past
    | calendar for no benefit.
    */

    'assign_from' => env('EZEE_ASSIGN_FROM', 'today'),

];
