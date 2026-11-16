<?php

/*
|--------------------------------------------------------------------------
| Careers page (/careers)
|--------------------------------------------------------------------------
| Who receives each application, and the roles shown on the page.
| With no roles listed, the page shows the general application only.
|
| A role looks like this:
|   [
|       'slug'     => 'guest-relations-executive',   // lowercase, hyphens, never reused
|       'title'    => 'Guest Relations Executive',
|       'type'     => 'Full time',
|       'location' => 'EkoCheras, Kuala Lumpur',
|       'summary'  => 'One or two sentences on the job.',
|       'points'   => ['What you will do', 'What we look for'],
|       // Optional, for job search engines (Google for Jobs). Without 'posted' the role is shown
|       // on the page but not offered to them.
|       'posted'     => '2026-11-16',            // date the role opened
|       'closes'     => '2027-01-31',            // optional closing date
|       'employment' => 'FULL_TIME',             // FULL_TIME, PART_TIME, CONTRACTOR, INTERN
|       'city'       => 'Kuala Lumpur',
|       'region'     => 'Kuala Lumpur',
|   ],
*/

return [
    // Set by Sam, 16 Nov 2026 (louiss with two s is the correct address).
    'to'  => ['louiss@homemoka.com'],
    'bcc' => ['sam@homemoka.com'],

    // Roles named by Sam on 16 Nov 2026. He asked to keep the descriptions simple: one line each, no bullet points.
    // No 'posted' date yet, so they are not offered to job search engines until he confirms the wording.
    'roles' => [
        [
            'slug'     => 'front-desk-operations',
            'title'    => 'Front Desk (Operations)',
            'type'     => 'Full time',
            'location' => 'EkoCheras, Kuala Lumpur',
            'summary'  => 'Welcome guests, handle check-ins and check-outs, and look after them during their stay.',
            'employment' => 'FULL_TIME', 'city' => 'Kuala Lumpur', 'region' => 'Kuala Lumpur',
        ],
        [
            'slug'     => 'maintenance-technician',
            'title'    => 'Maintenance Technician',
            'type'     => 'Full time',
            'location' => 'EkoCheras and other sites',
            'summary'  => 'Repair and maintain our units so every one is ready for the next guest.',
            'employment' => 'FULL_TIME', 'city' => 'Kuala Lumpur', 'region' => 'Kuala Lumpur',
        ],
        [
            'slug'     => 'account-and-finance-executive',
            'title'    => 'Account and Finance Executive',
            'type'     => 'Full time',
            'location' => 'EkoCheras, Kuala Lumpur',
            'summary'  => 'Prepare owner reports, handle payments and keep the accounts accurate.',
            'employment' => 'FULL_TIME', 'city' => 'Kuala Lumpur', 'region' => 'Kuala Lumpur',
        ],
        [
            'slug'     => 'business-development-executive',
            'title'    => 'Business Development Executive',
            'type'     => 'Full time',
            'location' => 'EkoCheras, Kuala Lumpur',
            'summary'  => 'Meet property owners, agents and developers, and bring new units to MOKA.',
            'employment' => 'FULL_TIME', 'city' => 'Kuala Lumpur', 'region' => 'Kuala Lumpur',
        ],
        [
            'slug'     => 'internship-program',
            'title'    => 'Internship Program',
            'type'     => 'Internship',
            'location' => 'EkoCheras, Kuala Lumpur',
            'summary'  => 'Learn how a short-stay property business runs by working with one of our teams. Tell us your course and internship dates when you apply.',
            'employment' => 'INTERN', 'city' => 'Kuala Lumpur', 'region' => 'Kuala Lumpur',
        ],
    ],
];
