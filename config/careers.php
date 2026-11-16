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

    // Roles named by Sam on 16 Nov 2026. The descriptions are a first draft for him to correct.
    // No 'posted' date yet, so they are not offered to job search engines until he confirms the wording.
    'roles' => [
        [
            'slug'     => 'front-desk-operations',
            'title'    => 'Front Desk (Operations)',
            'type'     => 'Full time',
            'location' => 'EkoCheras, Kuala Lumpur',
            'summary'  => 'Welcome guests, handle check-ins and check-outs, and keep each stay running smoothly from arrival to departure.',
            'points'   => [
                'Check guests in and out, and answer their questions in person, by phone and by message',
                'Work with housekeeping so every unit is ready on time',
                'Record bookings and payments accurately',
                'Suits someone friendly, organised and calm when it gets busy',
            ],
            'employment' => 'FULL_TIME', 'city' => 'Kuala Lumpur', 'region' => 'Kuala Lumpur',
        ],
        [
            'slug'     => 'maintenance-technician',
            'title'    => 'Maintenance Technician',
            'type'     => 'Full time',
            'location' => 'EkoCheras and the other buildings we manage',
            'summary'  => 'Keep our units in working order with repairs, regular checks and quick fixes before a guest notices.',
            'points'   => [
                'Carry out repairs and regular checks on air-conditioning, plumbing, electrical fittings and furniture',
                'Respond to maintenance requests and record the work done',
                'Travel between the buildings we manage',
                'Suits someone hands-on who takes pride in tidy work',
            ],
            'employment' => 'FULL_TIME', 'city' => 'Kuala Lumpur', 'region' => 'Kuala Lumpur',
        ],
        [
            'slug'     => 'account-and-finance-executive',
            'title'    => 'Account and Finance Executive',
            'type'     => 'Full time',
            'location' => 'EkoCheras, Kuala Lumpur',
            'summary'  => 'Help prepare the monthly owner reports, handle payments and keep the accounts accurate.',
            'points'   => [
                'Prepare and check monthly owner reports and payouts',
                'Process bills and payments on time',
                'Match bookings against bank records',
                'Suits someone careful with numbers who finishes what they start',
            ],
            'employment' => 'FULL_TIME', 'city' => 'Kuala Lumpur', 'region' => 'Kuala Lumpur',
        ],
        [
            'slug'     => 'business-development-executive',
            'title'    => 'Business Development Executive',
            'type'     => 'Full time',
            'location' => 'EkoCheras, Kuala Lumpur',
            'summary'  => 'Find and meet property owners, agents and developers, and bring new units to MOKA.',
            'points'   => [
                'Approach and meet owners, agents and developers',
                'Prepare proposals and follow up until the agreement is signed',
                'Work with marketing on new leads',
                'Suits someone outgoing who enjoys meeting people and closing deals',
            ],
            'employment' => 'FULL_TIME', 'city' => 'Kuala Lumpur', 'region' => 'Kuala Lumpur',
        ],
        [
            'slug'     => 'internship-program',
            'title'    => 'Internship Program',
            'type'     => 'Internship',
            'location' => 'EkoCheras, Kuala Lumpur',
            'summary'  => 'Learn how a short-stay property business runs by working alongside one of our teams.',
            'points'   => [
                'Placements in operations, guest sales, marketing or finance',
                'Real tasks, with a team member guiding you',
                'In your application, tell us your course, your internship dates and the team you prefer',
            ],
            'employment' => 'INTERN', 'city' => 'Kuala Lumpur', 'region' => 'Kuala Lumpur',
        ],
    ],
];
