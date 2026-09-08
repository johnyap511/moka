<?php

/*
|--------------------------------------------------------------------------
| Solution pages
|--------------------------------------------------------------------------
|
| One landing page per audience and per service, each written for the
| searches that audience actually makes. Pages live at /solutions/{slug},
| are rendered by v2.pages.solution, listed on /solutions, linked from the
| footer, and included in the sitemap with 'updated' as <lastmod>.
|
| Facts on these pages come from the service page, the about page and the
| ground rules: commission-only fee, 2–4 weeks to first booking (6–10 with
| renovation), listed on Airbnb, Booking.com, Agoda and more, dynamic pricing,
| hotel-standard housekeeping, owner dashboard, Klang Valley / Penang /
| Johor Bahru / Kota Kinabalu. Nothing here promises a return.
|
*/

return [

    'pages' => [

        [
            'slug'        => 'airbnb-management-malaysia',
            'label'       => 'Airbnb management',
            'title'       => 'Airbnb Management Company in Malaysia | MOKA',
            'description' => 'Full-service Airbnb management in Kuala Lumpur, Selangor, Penang, Johor Bahru and Kota Kinabalu. Listing, pricing, guests and housekeeping handled on a commission-only fee.',
            'eyebrow'     => 'Airbnb management Malaysia',
            'h1'          => 'Airbnb management, <em>done properly</em>',
            'sub'         => 'MOKA runs your Airbnb end to end: photography, listing, pricing, guest messages, check-in, cleaning and reviews. You see every booking and every ringgit in your owner dashboard.',
            'updated'     => '2026-09-08',
            'sections'    => [
                ['h2' => 'What a Malaysian Airbnb host actually has to do', 'p' => 'A listing that earns well is a full-time job: answering enquiries within minutes, adjusting prices for school holidays and events, coordinating cleaners between a 12pm check-out and a 3pm check-in, replacing a broken kettle the same day, and chasing five-star reviews. Most owners underestimate this until the first bad month.', 'bullets' => []],
                ['h2' => 'What MOKA takes off your hands', 'p' => 'One team, one fee, one dashboard.', 'bullets' => ['Professional photography, copywriting and listing set-up on Airbnb, Booking.com, Agoda and more', 'Dynamic pricing that moves nightly rates with demand', '24/7 guest communication, smart-lock check-in and issue resolution within the hour', 'Hotel-standard turnover cleaning, fresh linen and restocked amenities after every stay', 'Review management and a monthly performance report']],
                ['h2' => 'How the fee works', 'p' => 'MOKA is commission-only. There is no upfront charge and no fixed monthly retainer: we earn a percentage of the rental revenue we generate for you, so our interest and yours are the same. The exact rate depends on the property and the plan, and you get it in writing before anything starts.', 'bullets' => []],
                ['h2' => 'Where we operate', 'p' => 'Klang Valley, Penang, Johor Bahru and Kota Kinabalu today, with new areas opening. Ask us about yours.', 'bullets' => []],
            ],
            'faqs' => [
                ['q' => 'How quickly can my unit start earning?', 'a' => 'Typically 2 to 4 weeks from signing for a unit that is ready to list, or 6 to 10 weeks when renovation is needed.'],
                ['q' => 'Can I still stay in my own unit?', 'a' => 'Yes. Block the dates you want in your MOKA dashboard and we work around them.'],
                ['q' => 'Do I need to be in Malaysia?', 'a' => 'No. Many MOKA owners live overseas. Everything from guest handling to maintenance is managed locally, and your dashboard shows it all in real time.'],
            ],
        ],

        [
            'slug'        => 'short-term-rental-management',
            'label'       => 'Short-term rental',
            'title'       => 'Short-Term Rental Management in Malaysia | MOKA',
            'description' => 'Short-term and holiday rental management for condos and serviced apartments in Malaysia. Multi-platform listing, dynamic pricing, guest hosting and housekeeping on one commission-only fee.',
            'eyebrow'     => 'Short-term rental',
            'h1'          => 'Short-term rental management for <em>condos and serviced apartments</em>',
            'sub'         => 'Nightly and weekly stays earn more than a long lease when they are run well. MOKA runs them well, across every booking platform at once.',
            'updated'     => '2026-09-08',
            'sections'    => [
                ['h2' => 'Nightly, weekly and everything between', 'p' => 'Short-term rental is not only Airbnb. MOKA lists your unit on five or more platforms at the same time, from Airbnb and Booking.com to Agoda, Expedia, Trip.com and Traveloka, and takes direct bookings too. More channels, more nights filled, and one calendar so a unit is never double-booked.', 'bullets' => []],
                ['h2' => 'Pricing that follows demand', 'p' => 'Rates change every night with occupancy, events, holidays and lead time. A concert weekend in KL and a quiet Tuesday in the monsoon should never be priced the same, and with MOKA they are not.', 'bullets' => ['Occupancy targeting above 70%', 'Minimum-stay and gap-night rules that protect weekends', 'Monthly report with occupancy, average rate and revenue by channel']],
                ['h2' => 'Guests handled, unit protected', 'p' => 'Guest vetting before confirmation, smart-lock check-in, 24/7 support, and hotel-standard cleaning after every departure. Damage deposits and house rules are enforced on every platform.', 'bullets' => []],
            ],
            'faqs' => [
                ['q' => 'Is short-term rental allowed in my building?', 'a' => 'It depends on the building management and, in some states, local guidelines. MOKA already operates in many Klang Valley, Penang, Johor Bahru and Kota Kinabalu developments and will tell you plainly whether yours is suitable before you commit.'],
                ['q' => 'What does MOKA charge?', 'a' => 'A percentage of rental revenue, nothing upfront. The rate depends on the property and plan and is agreed in writing first.'],
            ],
        ],

        [
            'slug'        => 'monthly-rental',
            'label'       => 'Monthly & weekly rental',
            'title'       => 'Monthly and Weekly Rental Management in Malaysia | MOKA',
            'description' => 'Furnished monthly and weekly rentals for relocating professionals, project teams and long-visit families, managed by MOKA. Steady income without a fixed-term tenancy.',
            'eyebrow'     => 'Monthly and weekly rental',
            'h1'          => 'Monthly and weekly rental, <em>without the long lease</em>',
            'sub'         => 'Furnished stays of a week to a few months fill the gap between hotel rates and a twelve-month tenancy. MOKA markets, screens and manages them alongside your nightly bookings.',
            'updated'     => '2026-09-08',
            'sections'    => [
                ['h2' => 'Who books by the month', 'p' => 'Professionals relocating to Kuala Lumpur, project and construction teams, medical visitors, families between homes, and students in the weeks around term. They want a furnished unit, weekly cleaning and a single invoice, and they pay more per night than a long-term tenant.', 'bullets' => []],
                ['h2' => 'How MOKA runs it', 'p' => 'Medium-term stays sit on the same calendar as your short-term bookings, so the unit is never idle and never double-booked.', 'bullets' => ['Monthly and weekly rates set against local long-let and hotel prices', 'Guest screening, agreement and deposit handled before arrival', 'Scheduled housekeeping and linen changes during the stay', 'Utilities and internet included and monitored', 'Everything reported in your owner dashboard, month by month']],
                ['h2' => 'When a monthly guest makes more sense', 'p' => 'Low season, a building with strict short-stay rules, or an owner who prefers fewer changeovers. MOKA will tell you which mix of nightly, weekly and monthly stays suits your unit and your building, and adjust it through the year.', 'bullets' => []],
            ],
            'faqs' => [
                ['q' => 'Is a monthly rental a tenancy?', 'a' => 'No. It is a furnished stay with services included, closer to a serviced apartment than a lease, and it ends on the agreed date.'],
                ['q' => 'Can a unit switch between nightly and monthly?', 'a' => 'Yes. Both sit on one calendar and MOKA moves between them as demand changes.'],
            ],
        ],

        [
            'slug'        => 'corporate-housing',
            'label'       => 'Corporate housing',
            'title'       => 'Corporate Housing in Kuala Lumpur and Malaysia | MOKA',
            'description' => 'Furnished corporate housing for relocating staff, project teams and business travellers in Kuala Lumpur, Selangor, Penang and Johor Bahru, with one invoice and one point of contact.',
            'eyebrow'     => 'Corporate housing',
            'h1'          => 'Corporate housing your finance team <em>will thank you for</em>',
            'sub'         => 'Furnished apartments for staff relocations, project teams and extended business travel. One agreement, one monthly invoice, one number to call.',
            'updated'     => '2026-09-08',
            'sections'    => [
                ['h2' => 'Built for companies, not just travellers', 'p' => 'A hotel is expensive after the second week and a lease is too rigid for a six-month project. MOKA places your people in furnished condominiums and serviced apartments near where they work, for as long as they need, with housekeeping and internet included.', 'bullets' => ['Serviced units across Kuala Lumpur, Petaling Jaya, Cheras, Penang, Johor Bahru and Kota Kinabalu', 'Weekly or monthly terms, extendable', 'Consolidated invoicing and SST-compliant tax invoices', 'Housekeeping, linen and maintenance included', 'A dedicated contact for your HR or admin team']],
                ['h2' => 'For property owners', 'p' => 'Corporate guests are the steadiest short-stay demand there is: longer stays, fewer changeovers, and companies that pay on time. If you own a well-located unit, MOKA can place it in the corporate pool alongside nightly bookings.', 'bullets' => []],
            ],
            'faqs' => [
                ['q' => 'Can you house a whole team?', 'a' => 'Yes. MOKA manages multiple units in the same developments, so a project team can be placed in one building.'],
                ['q' => 'Do you provide tax invoices?', 'a' => 'Yes. Every stay is invoiced with SST shown separately.'],
            ],
        ],

        [
            'slug'        => 'for-homeowners',
            'label'       => 'For homeowners',
            'title'       => 'Property Management for Homeowners in Malaysia | MOKA',
            'description' => 'Own a condo or apartment you do not live in? MOKA turns it into short-stay income: renovation if needed, listing, guests, cleaning and reporting, with no upfront cost.',
            'eyebrow'     => 'For homeowners',
            'h1'          => 'Your spare unit can <em>pay for itself</em>',
            'sub'         => 'Whether it is the condo you moved out of, an inherited apartment or a second home, MOKA turns it into managed short-stay income and keeps you informed every month.',
            'updated'     => '2026-09-08',
            'sections'    => [
                ['h2' => 'What happens after you say yes', 'p' => 'An inspection and an honest recommendation first. Some units are ready to list next week. Others earn far more after a renovation, and we will show you the numbers either way.', 'bullets' => ['Inspection, income estimate and plan in writing', 'Renovation and furnishing by our in-house team if needed', 'Photography, listing and pricing across the major platforms', 'Guests, check-in, cleaning and maintenance handled', 'Monthly statement and a live owner dashboard']],
                ['h2' => 'Your home is still your home', 'p' => 'Block dates for your own stays. Set house rules. Decide on the deposit. You keep ownership and control; MOKA does the work.', 'bullets' => []],
                ['h2' => 'No upfront fee', 'p' => 'MOKA earns a share of the revenue it produces. If the unit does not earn, neither do we.', 'bullets' => []],
            ],
            'faqs' => [
                ['q' => 'My unit is empty and unfurnished. Can you still help?', 'a' => 'Yes. Renovation and furnishing are part of what MOKA does, with design, sourcing and installation handled in-house.'],
                ['q' => 'How do I know what my unit earned?', 'a' => 'Your owner dashboard shows every booking, occupancy and revenue for any month, and you receive a statement each month.'],
            ],
        ],

        [
            'slug'        => 'for-property-investors',
            'label'       => 'For property investors',
            'title'       => 'Short-Stay Management for Property Investors in Malaysia | MOKA',
            'description' => 'Investors use MOKA to run condo portfolios as short-stay assets: one operator, multi-platform revenue, dynamic pricing, transparent reporting and pooled income on group listings.',
            'eyebrow'     => 'For property investors',
            'h1'          => 'Run your units as <em>a portfolio</em>, not a chore',
            'sub'         => 'From a single studio to a floor of units, MOKA operates them as one business: shared calendars, dynamic pricing, professional housekeeping and reporting you can hand to your accountant.',
            'updated'     => '2026-09-08',
            'sections'    => [
                ['h2' => 'Why investors choose managed short-stay', 'p' => 'A well-run short-stay unit in the right building outperforms a long lease, and it stays furnished and maintained to a hotel standard because guests demand it. The risk is operational: pricing, changeovers and guest issues. That is what MOKA removes.', 'bullets' => []],
                ['h2' => 'What you get as an investor', 'p' => '', 'bullets' => ['A revenue estimate before you buy, based on real bookings in comparable units', 'Renovation to a short-stay specification, with durability in mind', 'Listing on Airbnb, Booking.com, Agoda, Expedia and more, priced dynamically', 'Pooled income on group listings, shared by unit size, so one empty week does not fall on one owner', 'Monthly statements with rate, nights, cleaning, taxes and fees itemised', 'An owner dashboard for every unit, any month, on your phone']],
                ['h2' => 'Grow with the same team', 'p' => 'MOKA already runs multiple units in developments across Kuala Lumpur, Cheras, Petaling Jaya, Penang, Johor Bahru and Kota Kinabalu. Adding a unit to a building we already operate is fast, because the housekeeping and guest teams are already there.', 'bullets' => []],
            ],
            'faqs' => [
                ['q' => 'Can you advise which unit to buy?', 'a' => 'We can tell you what comparable units earn under MOKA management and which buildings work for short-stay. We do not give investment advice; the decision is yours.'],
                ['q' => 'How is income shared in a pooled group?', 'a' => 'By unit size. Each unit in the pool carries a weight, and pool revenue for the month is split by those weights. Your dashboard shows the pool figures.'],
            ],
        ],

        [
            'slug'        => 'for-property-developers',
            'label'       => 'For developers',
            'title'       => 'Short-Stay Operator for Property Developers in Malaysia | MOKA',
            'description' => 'MOKA partners with developers to operate unsold, bulk or investor-owned units as managed short-stay and corporate housing, with one operator for the whole block and pooled returns for buyers.',
            'eyebrow'     => 'For property developers',
            'h1'          => 'One operator for <em>the whole block</em>',
            'sub'         => 'Turn unsold stock and investor units into a managed short-stay product with a single operator, a single standard and reporting the sales team can show buyers.',
            'updated'     => '2026-09-08',
            'sections'    => [
                ['h2' => 'What developers use MOKA for', 'p' => '', 'bullets' => ['Operating investor-owned units under one brand and standard from handover', 'A managed-income proposition for the sales gallery, backed by real operating data from comparable buildings', 'Furnishing packages built to a short-stay specification, installed at scale', 'Corporate housing placements that fill units from day one', 'Pooled income for buyers, shared by unit size, with a dashboard for every owner']],
                ['h2' => 'Why one operator matters', 'p' => 'A building with fifty different hosts has fifty cleaning standards, fifty check-in methods and constant friction with the management corporation. One operator means one house rule, one guest desk, one cleaning team and one contact for the JMB.', 'bullets' => []],
                ['h2' => 'Working with your sales and JMB', 'p' => 'MOKA has operated within managed developments in the Klang Valley and beyond, and knows how to structure short-stay so it sits comfortably with building management. We present to your sales team and to purchasers, and we sign directly with each owner.', 'bullets' => []],
            ],
            'faqs' => [
                ['q' => 'Do you guarantee returns?', 'a' => 'No. MOKA shows real operating figures from comparable units and reports every month; it does not promise a yield.'],
                ['q' => 'Can you start before vacant possession?', 'a' => 'Yes. Furnishing specification, pricing plans and sales collateral are prepared ahead of handover so units list as soon as they are ready.'],
            ],
        ],

        [
            'slug'        => 'for-property-agents',
            'label'       => 'For agents & agencies',
            'title'       => 'Referral Partnership for Property Agents and Agencies | MOKA',
            'description' => 'Property agents and agencies refer owners and investors to MOKA for short-stay and monthly rental management. Your client earns, you keep the relationship, and MOKA pays a referral fee.',
            'eyebrow'     => 'For property agents and agencies',
            'h1'          => 'Refer a client, <em>keep the client</em>',
            'sub'         => 'Not every owner wants a twelve-month tenant. When yours wants managed income instead, refer them to MOKA: you keep the relationship, and you earn on the introduction.',
            'updated'     => '2026-09-08',
            'sections'    => [
                ['h2' => 'When to refer to MOKA', 'p' => '', 'bullets' => ['An owner with a vacant furnished unit who does not want a long lease', 'An investor buying a unit in a building known for short-stay', 'A landlord tired of tenant turnover and late rent', 'A corporate client who needs housing for staff, not a lease', 'A developer client with unsold stock']],
                ['h2' => 'How the partnership works', 'p' => 'Introduce the owner, and MOKA handles the inspection, estimate and onboarding. You are kept informed, your client stays yours for future sales, and MOKA pays a referral fee once the unit is under management. Terms are simple and in writing.', 'bullets' => []],
                ['h2' => 'Why agents trust the referral', 'p' => 'Your name is on the introduction, so the service has to be good. MOKA is a Superhost and Preferred Host with a 4.9-star guest rating, a live owner dashboard and monthly statements your client can read in a minute.', 'bullets' => []],
            ],
            'faqs' => [
                ['q' => 'Do I need to register as a partner?', 'a' => 'A short conversation is enough. Tell us about your client and we take it from there; the referral terms are confirmed in writing before onboarding.'],
                ['q' => 'Can I refer clients outside the Klang Valley?', 'a' => 'Yes, in Penang, Johor Bahru and Kota Kinabalu today, and we will tell you if an area is not yet covered.'],
            ],
        ],

        [
            'slug'        => 'renovation',
            'label'       => 'Renovation for owners',
            'title'       => 'Renovation and Furnishing for Short-Stay and Rental Units | MOKA',
            'description' => 'MOKA\'s in-house team renovates and furnishes condos and apartments for short-stay, monthly rental and corporate housing: design, furniture, smart locks, staging and photography, built to last.',
            'eyebrow'     => 'Renovation for owners',
            'h1'          => 'Renovated to earn, <em>built to last</em>',
            'sub'         => 'A rental unit is furnished for hundreds of guests, not one family. MOKA designs and fits it out for durability, fast turnovers and photographs that book.',
            'updated'     => '2026-09-08',
            'sections'    => [
                ['h2' => 'What is included', 'p' => '', 'bullets' => ['Interior design consultation and a layout planned around guest flow and cleaning', 'Furniture, fittings and appliances sourced and installed by our team', 'Smart locks, wifi and the technology guests expect', 'Photography-ready staging on completion', 'Post-renovation touch-ups at no extra charge']],
                ['h2' => 'Designed for the way the unit will be used', 'p' => 'Washable finishes, hard-wearing fabrics, spare linen storage, a kitchen that resets in minutes and lighting that photographs well. The choices that make a home comfortable are not always the ones that survive a hundred check-outs, and we know the difference.', 'bullets' => []],
                ['h2' => 'Renovation as part of management', 'p' => 'Most owners renovate with MOKA because we then manage the unit, so the design is accountable to the income it produces. Renovation-only projects are quoted on request.', 'bullets' => []],
            ],
            'faqs' => [
                ['q' => 'How long does a renovation take?', 'a' => 'From signing to first booking is usually 6 to 10 weeks when renovation is involved, depending on scope.'],
                ['q' => 'Do you renovate units you will not manage?', 'a' => 'On request. Tell us about the unit and we will quote.'],
            ],
        ],

    ],
];
