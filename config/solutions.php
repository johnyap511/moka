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
            'slug'        => 'airbnb-management-kuala-lumpur',
            'label'       => 'Airbnb management KL',
            'title'       => 'Airbnb & Homestay Management in Kuala Lumpur | MOKA',
            'description' => 'MOKA manages Airbnb, homestay and serviced-apartment units across KL: KLCC, Bukit Bintang, Bangsar South, Cheras and Setapak. Commission-only. Free income estimate.',
            'eyebrow'     => 'Airbnb management Kuala Lumpur',
            'h1'          => 'Airbnb and homestay management in <em>Kuala Lumpur</em>',
            'sub'         => 'KL is Malaysia’s busiest short-stay market and its most competitive. MOKA runs KL units the way a hotel runs rooms: priced daily, cleaned to a standard, and answered around the clock, with every ringgit visible in your owner portal.',
            'updated'     => '2026-09-11',
            'sections'    => [
                ['h2' => 'Where in KL we manage', 'p' => 'MOKA units sit in the areas guests actually search for, and our housekeeping and maintenance teams cover them daily.', 'bullets' => ['KLCC, Bukit Bintang and the city centre: business travellers, medical tourists and weekend visitors', 'Bangsar South and KL Gateway: corporate stays near the offices and LRT', 'Cheras: EkoCheras, Arte Cheras and Queensville, family and long-weekend stays with mall access', 'Sungai Besi and Bandar Malaysia: Trion KL and nearby developments', 'Setapak, Sentul and Wangsa Maju: SkyWorld developments including Sky Awani, The Valley and SkyVogue']],
                ['h2' => 'What a KL unit needs to earn well', 'p' => 'Kuala Lumpur has thousands of listings. The ones that fill are photographed professionally, priced against the calendar every day, reviewed above 4.8, and cleaned like a hotel room between every guest. That is a full-time operation, and it is what MOKA does for you.', 'bullets' => ['Listings on Airbnb, Booking.com, Agoda and Trip.com, plus direct bookings on staymoka.com', 'Dynamic pricing for KL events, school holidays, F1 weekend and year-end travel', 'Self check-in with smart locks, guest vetting and a 24-hour guest line', 'Hotel-grade housekeeping, linen and consumables after every stay']],
                ['h2' => 'The rules in KL', 'p' => 'Kuala Lumpur has no single short-stay licence today, but your building has the final say: a management body can restrict short stays through its by-laws. MOKA checks your building before we list, registers guests with security, and keeps the JMB on side so your listing lasts.', 'bullets' => []],
                ['h2' => 'How the fee works', 'p' => 'Commission-only. No upfront charge, no monthly retainer. MOKA earns a share of the revenue we generate and you see the split on every booking in the owner portal.', 'bullets' => []],
            ],
            'faqs' => [
                ['q' => 'Which KL condos work best for Airbnb?', 'a' => 'Serviced apartments and condominiums with facilities, near an LRT or MRT station or a mall, in buildings whose management allows short stays. Send us your address and we will tell you honestly whether it will work.'],
                ['q' => 'How much can a KL unit earn on Airbnb?', 'a' => 'It depends on the building, the unit size and the finish. Request a free estimate and we reply with what comparable MOKA units in your area actually achieved, not a best-case figure.'],
                ['q' => 'Can MOKA manage a unit I bought as an investment but never furnished?', 'a' => 'Yes. Our sister company Innspace furnishes and renovates units for short-stay use, and can complete the work before vacant possession on SkyWorld developments.'],
            ],
        ],

        [
            'slug'        => 'airbnb-management-selangor',
            'label'       => 'Airbnb management Selangor',
            'title'       => 'Airbnb & Short-Stay Management in Selangor | MOKA',
            'description' => 'MOKA manages short-stay and monthly units across Selangor: Petaling Jaya, Subang, Setia Alam, Sunsuria City and Sepang near KLIA. Commission-only, free estimate.',
            'eyebrow'     => 'Airbnb management Selangor',
            'h1'          => 'Short-stay management across <em>Selangor</em>',
            'sub'         => 'From Petaling Jaya to KLIA, Selangor units earn from business travel, university families, airport stopovers and corporate relocations. MOKA runs them all from our Tropicana office.',
            'updated'     => '2026-09-11',
            'sections'    => [
                ['h2' => 'Where in Selangor we manage', 'p' => 'Our office is in Tropicana, Petaling Jaya, so Selangor units get the fastest response in our network.', 'bullets' => ['Petaling Jaya, Damansara and Tropicana: corporate and medical stays', 'Subang Jaya, Sunway and USJ: university families, Sunway Lagoon and Sunway Medical visitors', 'Setia Alam and Shah Alam: Sunsuria Forum and long-weekend family stays', 'Sunsuria City and Sepang: Bell Suites, KLIA crews and airport stopovers, Xiamen University families', 'Cyberjaya and Puchong: project teams and monthly corporate housing']],
                ['h2' => 'Nightly, monthly or corporate', 'p' => 'Selangor demand is steadier than KL and less seasonal. Many MOKA owners here mix short stays with monthly furnished lets to corporate tenants, and we move a unit between the two as demand changes.', 'bullets' => ['Nightly and weekly stays on every major platform', 'Monthly furnished stays for relocations and project teams', 'Corporate housing agreements with one monthly invoice']],
                ['h2' => 'How the fee works', 'p' => 'Commission-only. No upfront charge, no monthly retainer, and the owner portal shows every booking, fee and payout.', 'bullets' => []],
            ],
            'faqs' => [
                ['q' => 'Do you manage units near KLIA?', 'a' => 'Yes. Bell Suites at Sunsuria City is one of our largest operations, serving airline crews, transit passengers and Xiamen University families.'],
                ['q' => 'Is Petaling Jaya good for short stays?', 'a' => 'Yes, for business and medical travel. PJ units earn more steadily across the year than tourist areas, and suit a mix of nightly and monthly stays.'],
                ['q' => 'Can I visit your office?', 'a' => 'Yes. MOKA is at Menara Lien Hoe, Tropicana, Petaling Jaya. Message us to arrange a time.'],
            ],
        ],

        [
            'slug'        => 'airbnb-management-penang',
            'label'       => 'Airbnb management Penang',
            'title'       => 'Airbnb & Short-Stay Management in Penang | MOKA',
            'description' => 'MOKA manages short-stay and monthly units in Penang and handles building consent and licensing under the 2026 short-term accommodation by-laws. Free estimate.',
            'eyebrow'     => 'Airbnb management Penang',
            'h1'          => 'Short-stay management in <em>Penang</em>, licensed and compliant',
            'sub'         => 'Penang is the first state to license short-term stays. MOKA operates units on the island and the mainland, and handles the consent, licence and reporting cycle so owners stay on the right side of MBPP and MBSP.',
            'updated'     => '2026-09-11',
            'sections'    => [
                ['h2' => 'What changed in Penang in 2026', 'p' => 'The Private Short-term Accommodation By-Laws 2026 require a council licence for any paid stay of up to six months. On the island, only commercial-titled strata such as serviced apartments and SoHo can be licensed; residential-titled condominiums cannot. The mainland allows a wider list. Every licence needs the building’s 75 percent consent, renewed yearly.', 'bullets' => ['Licence applications due before 1 November 2026', 'Building consent by special resolution, renewed each January', 'Fees from RM1,000 a year plus an accommodation fee per unit']],
                ['h2' => 'How MOKA handles it for you', 'p' => 'We tell you honestly whether your unit can be licensed, prepare the paperwork, work with your JMB or MC on the resolution, and keep the yearly renewals and December reporting on our calendar, not yours.', 'bullets' => ['Title and building check before anything is listed', 'Licence application and renewals with MBPP or MBSP', 'Monthly furnished stays under a tenancy for units that cannot be licensed nightly']],
                ['h2' => 'Where in Penang we manage', 'p' => 'George Town and the heritage zone for leisure travellers, Bayan Lepas and the Free Industrial Zone for engineers and project teams on weekly and monthly stays, and Seberang Perai on the mainland where more property types qualify.', 'bullets' => []],
            ],
            'faqs' => [
                ['q' => 'My Penang condo is residential-titled. Can I still earn from it?', 'a' => 'Not nightly on the island under the 2026 by-laws. MOKA can run it as monthly furnished stays under a tenancy agreement, which sit outside the short-stay definition, or as a conventional tenancy.'],
                ['q' => 'Who applies for the licence, the owner or MOKA?', 'a' => 'The application is in the owner’s name, and MOKA prepares and files it with you, including the building consent.'],
                ['q' => 'Where can I read the full rules?', 'a' => 'Our guide to Penang’s short-term rental by-laws on the MOKA blog explains the property types, fees and deadlines in plain language.'],
            ],
        ],

        [
            'slug'        => 'airbnb-management-johor-bahru',
            'label'       => 'Airbnb management Johor Bahru',
            'title'       => 'Airbnb & Short-Stay Management in Johor Bahru | MOKA',
            'description' => 'MOKA manages short-stay and monthly units in Johor Bahru and Iskandar Malaysia for the Singapore market and corporate relocations. Commission-only, free estimate.',
            'eyebrow'     => 'Airbnb management Johor Bahru',
            'h1'          => 'Short-stay management in <em>Johor Bahru</em>',
            'sub'         => 'JB earns from Singapore. Weekend families, cross-border commuters and companies housing staff on the Malaysian side keep well-run units busy all year. MOKA runs them to the standard Singapore guests expect.',
            'updated'     => '2026-09-11',
            'sections'    => [
                ['h2' => 'Why JB works for owners', 'p' => 'The Singapore dollar goes a long way in Johor Bahru, and demand follows it: weekend stays from Singapore, longer stays from employees who work across the Causeway, and corporate housing for companies expanding into Iskandar Malaysia. The JB–Singapore RTS Link adds a new wave of commuters.', 'bullets' => ['Weekend and school-holiday families from Singapore', 'Weekly and monthly stays for cross-border workers', 'Corporate housing for relocations into Iskandar Puteri and Medini']],
                ['h2' => 'Where in JB we manage', 'p' => 'JB city centre and Bukit Chagar for the Causeway and RTS, Iskandar Puteri and Medini for Legoland families and corporate stays, and the Tebrau and Mount Austin belt for value-seeking families.', 'bullets' => []],
                ['h2' => 'How the fee works', 'p' => 'Commission-only. No upfront charge, no monthly retainer, and every booking, fee and payout visible in the owner portal, whether you live in JB, KL or Singapore.', 'bullets' => []],
            ],
            'faqs' => [
                ['q' => 'I live in Singapore. Can MOKA manage my JB unit remotely?', 'a' => 'Yes. Many MOKA owners live outside Malaysia. Guests, cleaning and maintenance are handled locally, and your owner portal shows every booking and payout.'],
                ['q' => 'Does my JB building allow short stays?', 'a' => 'It depends on the building’s by-laws. Send us the address and we will check before anything is listed.'],
                ['q' => 'Is monthly rental better than nightly in JB?', 'a' => 'Often a mix works best: nightly on weekends and holidays, monthly to cross-border workers in between. MOKA switches a unit between the two as demand changes.'],
            ],
        ],

        [
            'slug'        => 'airbnb-management-kota-kinabalu',
            'label'       => 'Airbnb management Kota Kinabalu',
            'title'       => 'Airbnb & Short-Stay Management in Kota Kinabalu | MOKA',
            'description' => 'MOKA manages short-stay and monthly units in Kota Kinabalu for leisure, business and expatriate stays. Commission-only, free income estimate.',
            'eyebrow'     => 'Airbnb management Kota Kinabalu',
            'h1'          => 'Short-stay management in <em>Kota Kinabalu</em>',
            'sub'         => 'KK is a year-round leisure market with strong direct flights from China, Korea and Singapore, plus steady business and expatriate demand. MOKA runs KK units with the same standards, systems and owner portal as our Klang Valley operation.',
            'updated'     => '2026-09-11',
            'sections'    => [
                ['h2' => 'What KK guests book', 'p' => 'Families and couples heading to the islands and Mount Kinabalu want a clean, central unit with a kitchen and a view; business and government travellers want reliable Wi-Fi and easy parking; expatriates in oil and gas, education and tourism want furnished monthly stays.', 'bullets' => ['Nightly and weekly leisure stays in the city centre, Jesselton and Likas', 'Monthly furnished stays for relocations and project teams', 'Corporate housing with one monthly invoice']],
                ['h2' => 'How MOKA runs a KK unit', 'p' => 'Professional photography, listings on every major platform, daily pricing around flight schedules and Sabah holidays, self check-in, a 24-hour guest line and hotel-grade housekeeping.', 'bullets' => []],
                ['h2' => 'How the fee works', 'p' => 'Commission-only. No upfront charge, no monthly retainer, and the owner portal shows every booking, fee and payout wherever you live.', 'bullets' => []],
            ],
            'faqs' => [
                ['q' => 'Which areas of Kota Kinabalu suit short stays?', 'a' => 'The city centre and waterfront, Jesselton and Likas for leisure and business guests, and developments near the airport for stopovers. Send us your address and we will tell you what it could earn.'],
                ['q' => 'Is KK seasonal?', 'a' => 'Less than most people expect. Chinese New Year, school holidays and the climbing season peak, but business, government and expatriate demand keeps units busy between them.'],
                ['q' => 'Can I see my bookings from Peninsular Malaysia?', 'a' => 'Yes. The MOKA owner portal shows every booking, calendar and payout, and works as a home-screen app on your phone.'],
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
