<?php

/*
|--------------------------------------------------------------------------
| Blog posts
|--------------------------------------------------------------------------
|
| The blog is deliberately file-based: each post is a Blade view under
| resources/views/blog/posts, and this file is the index of them. Both
| BlogController and SitemapController read from here, so adding a post means
| adding a Blade view and one entry below — no migration, no admin screen.
|
| 'slug'        URL segment; the post lives at /blog/{slug}
| 'view'        Blade view under blog.posts
| 'title'       <title> and og:title
| 'heading'     the on-page <h1>, usually shorter than the title
| 'description' meta description and the excerpt on the index
| 'published'   ISO date, used for sorting, display and article:published_time
| 'updated'     ISO date, used for sitemap <lastmod>
| 'image'       card and social image, relative to public/ (a 1600px project photo; the card uses its -thumb.webp)
| 'topic'       Hosting, Renovation or Regulations; drives the filter chips on the index
| 'read_time'   rough minutes, shown on the index
|
*/

return [

    'posts' => [

        [
            'slug'        => 'muslim-friendly-hosting-malaysia-short-stay-guide',
            'view'        => 'muslim-friendly-hosting-malaysia-short-stay-guide',
            'title'       => 'Hosting Muslim-Friendly Guests in Malaysia | MOKA',
            'heading'     => 'Hosting Muslim-friendly guests: what Malaysia\'s No. 1 ranking means for your listing',
            'description' => 'Malaysia keeps its Global Muslim Travel Index No. 1 ranking for an 11th year. A practical guide to hosting Muslim-friendly guests in your short-stay unit.',
            'published'   => '2026-09-15',
            'updated'     => '2026-09-15',
            'image'       => 'new-theme23/images/projects/skyawani-5/3.jpg',
            'figures'     => [
                ['new-theme23/images/blog/mount-kinabalu.jpg', 'Malaysia has topped the Global Muslim Travel Index for eleven years running'],
                ['new-theme23/images/projects/curvo/2.jpg', 'Innspace interior at Curvo'],
            ],
            'topic'       => 'Hosting',
            'read_time'   => 5,
            'cta_heading' => 'Want your listing ready for this segment?',
            'cta_body'    => 'We can walk through your unit and suggest low-cost additions that matter to Muslim-friendly travellers.',
            'cta_label'   => 'Get a free estimate',
            'cta_url'     => '/get/estimate',
        ],

        [
            'slug'        => 'short-stay-news-malaysia-sea-15-september-2026',
            'view'        => 'short-stay-news-malaysia-sea-15-september-2026',
            'title'       => 'Short-Stay News Malaysia & SEA: 15 Sep 2026 | MOKA',
            'heading'     => 'Short-stay news, Malaysia and Southeast Asia: week of 15 September 2026',
            'description' => 'A cultural festival packs Titiwangsa, Malaysia keeps its Muslim-friendly No.1 ranking, the property overhang grows, and Thailand halves its visa-free stay.',
            'published'   => '2026-09-15',
            'updated'     => '2026-09-15',
            'image'       => 'new-theme23/images/blog/nasi-lemak.jpg',
            'figures'     => [
                ['new-theme23/images/blog/singapore-supertrees.jpg', 'Southeast Asia\'s travel and short-stay rules are moving at different speeds'],
                ['new-theme23/images/blog/villa-pool.jpg', 'Malaysia\'s overhang of unsold completed homes is still climbing'],
            ],
            'topic'       => 'News',
            'read_time'   => 5,
            'cta_heading' => 'Want this read for your building?',
            'cta_body'    => 'Tell us where your unit is and we will say which of these stories applies and what to do about it.',
            'cta_label'   => 'Get a free estimate',
            'cta_url'     => '/get/estimate',
        ],

        [
            'slug'        => 'short-stay-news-malaysia-sea-16-september-2026',
            'view'        => 'short-stay-news-malaysia-sea-16-september-2026',
            'title'       => 'Short-Stay News Malaysia & SEA: 16 Sep 2026 | MOKA',
            'heading'     => 'Short-stay news, Malaysia and Southeast Asia: week of 16 September 2026',
            'description' => 'Singapore arrivals up 4.39%, F1 back at Sepang on 2 to 4 October, Penang licences due 1 November, Selangor’s 180-night cap still a proposal. What it means for you.',
            'published'   => '2026-09-16',
            'updated'     => '2026-09-16',
            'image'       => 'new-theme23/images/blog/kl-skyline-night.jpg',
            'figures'     => [
                ['new-theme23/images/blog/singapore-marina-bay.jpg', 'Singapore remains Malaysia’s largest source of visitors'],
                ['new-theme23/images/blog/johor-bahru-sign.jpg', 'Johor Bahru, a year ahead of the RTS Link'],
            ],
            'topic'       => 'News',
            'read_time'   => 5,
            'cta_heading' => 'Want this read for your building?',
            'cta_body'    => 'Tell us where your unit is and we will say which of these stories applies and what to do about it.',
            'cta_label'   => 'Get a free estimate',
            'cta_url'     => '/get/estimate',
        ],

        [
            'slug'        => 'f1-sepang-2026-short-stay-pricing-guide',
            'view'        => 'f1-sepang-2026-short-stay-pricing-guide',
            'title'       => 'F1 at Sepang 2026: Pricing Guide for KL Owners | MOKA',
            'heading'     => 'F1 is back at Sepang: a pricing plan for short-stay owners in KL',
            'description' => 'Sepang hosts Formula 1 on 2 to 4 October 2026 and hotel bookings near the circuit are up 1,600%. A six-step plan to price your KL or KLIA unit for the weekend.',
            'published'   => '2026-09-16',
            'updated'     => '2026-09-16',
            'image'       => 'new-theme23/images/blog/race-track.jpg',
            'figures'     => [
                ['new-theme23/images/blog/pit-crew.jpg', 'Race weekends bring three nights of demand, not one'],
                ['new-theme23/images/blog/airport-travellers.jpg', 'Most race visitors arrive through KLIA, next to the circuit'],
            ],
            'topic'       => 'News',
            'read_time'   => 5,
            'cta_heading' => 'Want your unit priced for race weekend?',
            'cta_body'    => 'We set minimum stays and rate steps for every event weekend and report what each unit earned against its normal rate.',
            'cta_label'   => 'Get a free estimate',
            'cta_url'     => '/get/estimate',
        ],

        [
            'slug'        => 'moka-joins-mystra-malaysia-short-term-rental-association',
            'view'        => 'moka-joins-mystra-malaysia-short-term-rental-association',
            'title'       => 'MOKA Joins the MySTRA Community | MOKA',
            'heading'     => 'MOKA joins the Malaysia Short-Term Rental Association community',
            'description' => 'MOKA is now part of MySTRA, the Malaysia Short-Term Rental Association: what it is, why we joined as the national rules are written, and what owners gain.',
            'published'   => '2026-09-16',
            'updated'     => '2026-09-16',
            'image'       => 'new-theme23/images/blog/conference-hall.jpg',
            'figures'     => [
                ['new-theme23/images/projects/skyawani-5/1.jpg', 'A MOKA-managed unit at Sky Awani 5'],
                ['new-theme23/images/projects/curvo/1.jpg', 'Innspace interior at Curvo'],
            ],
            'topic'       => 'Community',
            'read_time'   => 3,
            'cta_heading' => 'Developer, agent or building manager?',
            'cta_body'    => 'Talk to us about how the coming short-stay rules affect your building or your clients’ units.',
            'cta_label'   => 'Contact us',
            'cta_url'     => '/contact',
        ],

        [
            'slug'        => 'skyworld-solution-plus-early-bird-renovate-before-vp',
            'view'        => 'skyworld-solution-plus-early-bird-renovate-before-vp',
            'title'       => 'Solution+ Early Bird: Renovate Before VP | MOKA',
            'heading'     => 'Solution+ Early Bird: renovated before VP, ready to move in on handover day',
            'description' => 'How SkyWorld’s Solution+ Early Bird event worked, why renovating before vacant possession saves owners months of empty instalments, and how MOKA’s custom interior design fits inside the developer’s pre-VP schedule.',
            'published'   => '2026-09-09',
            'updated'     => '2026-09-09',
            'image'       => 'new-theme23/images/projects/the-valley/1.jpg',
            'figures'     => [
                ['new-theme23/images/projects/skyvogue/2.jpg', 'Living room by Innspace at SkyVogue'],
                ['new-theme23/images/projects/the-valley/7.jpg', 'Kitchen by Innspace at The Valley'],
            ],
            'topic'       => 'Renovation',
            'read_time'   => 6,
            'cta_heading' => 'Collecting keys from SkyWorld soon?',
            'cta_body'    => 'Send us your unit number and floor plan and we will tell you what can be finished before your VP date.',
            'cta_label'   => 'Speak to our designers',
            'cta_url'     => '/contact',
        ],

        [
            'slug'        => 'penang-short-term-rental-rules-2026',
            'view'        => 'penang-short-term-rental-rules-2026',
            'title'       => 'Penang Short-Term Rental Rules 2026 Explained | MOKA',
            'heading'     => 'Penang’s new short-term rental by-laws: what owners need to know',
            'description' => 'Penang’s Private Short-term Accommodation By-Laws 2026 explained: which properties can be licensed on the island and the mainland, the 75% building consent, fees from RM1,000 a year, and the 1 November 2026 deadline.',
            'published'   => '2026-09-09',
            'updated'     => '2026-09-09',
            'image'       => 'new-theme23/images/blog/penang-colonial-dusk.jpg',
            'figures'     => [
                ['new-theme23/images/blog/penang-rickshaw-street.jpg', 'George Town, Penang'],
                ['new-theme23/images/projects/skyvogue/5.jpg', 'Innspace interior at SkyVogue'],
            ],
            'topic'       => 'Regulations',
            'read_time'   => 7,
            'cta_heading' => 'Own a unit in Penang?',
            'cta_body'    => 'MOKA handles building consent, licensing and reporting for the owners we manage. Ask us where your unit stands.',
            'cta_label'   => 'Talk to us',
            'cta_url'     => '/contact',
        ],

        [
            'slug'        => 'working-with-building-management-and-authorities',
            'view'        => 'working-with-building-management-and-authorities',
            'title'       => 'Working With Your JMB and the Council as a Host | MOKA',
            'heading'     => 'How to keep your JMB, your guards and the council on your side',
            'description' => 'The building decides whether you can host. A practical guide for Malaysian short-stay hosts on consent, guest registration, house rules, licensing and the daily habits that keep a JMB or MC happy.',
            'published'   => '2026-09-09',
            'updated'     => '2026-09-09',
            'image'       => 'new-theme23/images/blog/meeting-table.jpg',
            'figures'     => [
                ['new-theme23/images/blog/city-lights-night.jpg', 'High-rise living in Kuala Lumpur'],
                ['new-theme23/images/projects/the-valley/5.jpg', 'A MOKA-managed unit at The Valley'],
            ],
            'topic'       => 'Regulations',
            'read_time'   => 7,
        ],

        [
            'slug'        => 'airbnb-management-kuala-lumpur-owner-guide',
            'view'        => 'airbnb-management-kuala-lumpur-owner-guide',
            'title'       => 'Airbnb Management in Kuala Lumpur: A Property Owner’s Guide | MOKA',
            'heading'     => 'Airbnb management in Kuala Lumpur: what owners should expect',
            'description' => 'What a short-stay management company actually does for a KL condo, how fees are usually structured, and the questions to ask before you hand over your keys.',
            'published'   => '2026-09-01',
            'updated'     => '2026-09-01',
            'image'       => 'new-theme23/images/blog/petronas-towers.jpg',
            'figures'     => [
                ['new-theme23/images/projects/the-valley/2.jpg', 'A MOKA-managed unit at The Valley'],
                ['new-theme23/images/blog/kl-aerial-buildings.jpg', 'Kuala Lumpur from above'],
            ],
            'topic'       => 'Hosting',
            'read_time'   => 7,
        ],

        [
            'slug'        => 'skyworld-solution-plus-home-renovation',
            'view'        => 'skyworld-solution-plus-home-renovation',
            'title'       => 'Renovating Your SkyWorld Home with Solution+ | MOKA Interior Design',
            'heading'     => 'Renovating your SkyWorld home, from handover to move-in',
            'description' => 'MOKA is a listed renovation partner on SkyWorld’s Solution+ marketplace. Eight years of renovation experience, custom design and full interior design services — plus how MyDeco financing works.',
            'published'   => '2026-09-01',
            'updated'     => '2026-09-01',
            'image'       => 'new-theme23/images/projects/skyvogue/4.jpg',
            'figures'     => [
                ['new-theme23/images/blog/kitchen-renovation.jpg', 'Renovation before vacant possession'],
                ['new-theme23/images/blog/paint-roller.jpg', 'Painting and touch-up'],
            ],
            'topic'       => 'Renovation',
            'read_time'   => 8,
            'cta_heading' => 'Planning your renovation?',
            'cta_body'    => 'Talk to the MOKA design team about a custom design for your home.',
            'cta_label'   => 'Speak to our designers',
            'cta_url'     => '/contact',
        ],

        [
            'slug'        => 'airbnb-vs-long-term-rental-malaysia',
            'view'        => 'airbnb-vs-long-term-rental-malaysia',
            'title'       => 'Airbnb vs Long-Term Rental in Malaysia: Which Suits Your Unit? | MOKA',
            'heading'     => 'Airbnb or long-term tenant? How to decide for your unit',
            'description' => 'Short-stay pays more per night but costs more to run, and not every unit suits it. A practical framework for Malaysian owners weighing the two.',
            'published'   => '2026-09-01',
            'updated'     => '2026-09-01',
            'image'       => 'new-theme23/images/blog/keys-house-keychain.jpg',
            'figures'     => [
                ['new-theme23/images/blog/calendar-screen.jpg', 'Occupancy is what separates the two models'],
                ['new-theme23/images/projects/skyawani-4/6.jpg', 'Bedroom, Sky Awani 4'],
            ],
            'topic'       => 'Hosting',
            'read_time'   => 8,
        ],

        [
            'slug'        => 'furnishing-condo-for-airbnb-malaysia',
            'view'        => 'furnishing-condo-for-airbnb-malaysia',
            'title'       => 'How to Furnish a Condo for Airbnb in Malaysia | MOKA',
            'heading'     => 'Furnishing a condo for short-stay: what guests actually notice',
            'description' => 'Where furnishing budget earns its keep, what guests complain about most, and the details that quietly decide whether your listing gets five stars.',
            'published'   => '2026-09-01',
            'updated'     => '2026-09-01',
            'image'       => 'new-theme23/images/projects/skyawani-4/3.jpg',
            'figures'     => [
                ['new-theme23/images/projects/skyawani-4/2.jpg', 'Living area, Sky Awani 4'],
                ['new-theme23/images/projects/skyvogue/3.jpg', 'Innspace interior at SkyVogue'],
            ],
            'topic'       => 'Renovation',
            'read_time'   => 7,
        ],

        [
            'slug'        => 'new-condo-handover-checklist-short-stay',
            'view'        => 'new-condo-handover-checklist-short-stay',
            'title'       => 'New Condo Handover: Getting Your Unit Ready to Host | MOKA',
            'heading'     => 'From handover to first booking: a new-condo checklist',
            'description' => 'Defect inspection, utilities, access cards, building rules and insurance — the handover steps that decide how quickly a new unit can start earning.',
            'published'   => '2026-09-01',
            'updated'     => '2026-09-01',
            'image'       => 'new-theme23/images/blog/house-keys-door.jpg',
            'figures'     => [
                ['new-theme23/images/projects/skyawani-4/1.jpg', 'Living and dining, Sky Awani 4'],
                ['new-theme23/images/blog/notepad-flatlay.jpg', 'Walk the unit with a checklist on handover day'],
            ],
            'topic'       => 'Hosting',
            'read_time'   => 6,
        ],

    ],

];
