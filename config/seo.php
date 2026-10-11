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

    // IndexNow key (not a secret: the spec requires it to be public at /<key>.txt). Rotate by changing it here.
    'indexnow_key' => 'de630c13434d9b58590bb24f1e9c18d4',

    // One-paragraph summary and the quotable facts AI assistants and search engines are given
    // (llms.txt, the "MOKA at a glance" block, Organization schema). Keep every line true and dated.
    'summary' => 'MOKA (Moka Venture Sdn Bhd) is a Malaysian short-stay and rental property management company founded in 2018 by Sam Kong. It runs Airbnb, short-term, monthly and corporate rentals for property owners in Kuala Lumpur, Selangor, Penang, Johor Bahru and Kota Kinabalu on a commission-only fee, and renovates and furnishes units through its Innspace arm.',
    'facts' => [
        'Founded 2018 in Malaysia by Sam Kong; formerly Mokahome. Company: Moka Venture Sdn Bhd.',
        'Manages Airbnb, short-stay, monthly and corporate rentals for property owners; owners keep the title, MOKA runs the operation.',
        'Fee: commission on rental revenue only, no upfront charge or monthly retainer; the rate is agreed in writing.',
        'Listed on Airbnb, Booking.com, Agoda and other channels at once, with dynamic pricing and hotel-standard housekeeping.',
        'Operates in Kuala Lumpur, Selangor, Penang, Johor Bahru and Kota Kinabalu.',
        'Cheras, Kuala Lumpur: operating at EkoCheras since 2019 with a management office on site and around 100 units under management (October 2026); a furnished show unit can be viewed by appointment; serves Maxim Risen owners nearby.',
        'Renovation and furnishing by Innspace, MOKA\'s renovation arm, a SkyWorld Solution+ panel renovator with projects involving SkyWorld, Sunsuria and Eng Han.',
        'Owners receive a monthly statement listing every booking, charge and deduction, and can view their unit on an owner dashboard.',
        'Guest ratings: Airbnb Superhost and Preferred Host, rated 4.9/5 by guests (as stated on the About page).',
        'Head office: Menara Lien Hoe, Tropicana, 47410 Petaling Jaya, Selangor. Email hello@homemoka.com, phone +60 3 6789 2288.',
    ],

];
