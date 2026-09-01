<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Canonical host
    |--------------------------------------------------------------------------
    |
    | Only this host may be indexed. Anything else serving the same application —
    | staging, an IP address, a preview box — returns Disallow and noindex, so a
    | copy of the site cannot compete with the real one in search.
    |
    | Deliberately not keyed on APP_ENV: staging runs with APP_ENV=production so
    | it behaves like the real thing, which makes the environment name useless
    | for telling them apart. The hostname is the thing that actually differs.
    |
    */

    'canonical_host' => env('SEO_CANONICAL_HOST', 'homemoka.com'),

];
