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
            'slug'        => 'skyworld-solution-plus-early-bird-renovate-before-vp',
            'view'        => 'skyworld-solution-plus-early-bird-renovate-before-vp',
            'title'       => 'SkyWorld Solution+ Early Bird: Renovate Before VP, Move In on Handover Day | MOKA',
            'heading'     => 'Solution+ Early Bird: renovated before VP, ready to move in on handover day',
            'description' => 'How SkyWorld’s Solution+ Early Bird event worked, why renovating before vacant possession saves owners months of empty instalments, and how MOKA’s custom interior design fits inside the developer’s pre-VP schedule.',
            'published'   => '2026-09-09',
            'updated'     => '2026-09-09',
            'image'       => 'new-theme23/images/projects/the-valley/1.jpg',
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
            'title'       => 'Penang Short-Term Rental Rules 2026: Licences, Fees and Which Units Qualify | MOKA',
            'heading'     => 'Penang’s new short-term rental by-laws: what owners need to know',
            'description' => 'Penang’s Private Short-term Accommodation By-Laws 2026 explained: which properties can be licensed on the island and the mainland, the 75% building consent, fees from RM1,000 a year, and the 1 November 2026 deadline.',
            'published'   => '2026-09-09',
            'updated'     => '2026-09-09',
            'image'       => 'new-theme23/images/projects/skyvogue/1.jpg',
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
            'title'       => 'How Airbnb Hosts Should Work With Building Management and Local Authorities in Malaysia | MOKA',
            'heading'     => 'How to keep your JMB, your guards and the council on your side',
            'description' => 'The building decides whether you can host. A practical guide for Malaysian short-stay hosts on consent, guest registration, house rules, licensing and the daily habits that keep a JMB or MC happy.',
            'published'   => '2026-09-09',
            'updated'     => '2026-09-09',
            'image'       => 'new-theme23/images/projects/skyawani-4/1.jpg',
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
            'image'       => 'new-theme23/images/projects/the-valley/3.jpg',
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
            'image'       => 'new-theme23/images/projects/the-valley/6.jpg',
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
            'image'       => 'new-theme23/images/projects/skyawani-4/5.jpg',
            'topic'       => 'Hosting',
            'read_time'   => 6,
        ],

    ],

];
