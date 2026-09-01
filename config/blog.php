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
| 'image'       social card, relative to public/
| 'read_time'   rough minutes, shown on the index
|
*/

return [

    'posts' => [

        [
            'slug'        => 'airbnb-management-kuala-lumpur-owner-guide',
            'view'        => 'airbnb-management-kuala-lumpur-owner-guide',
            'title'       => 'Airbnb Management in Kuala Lumpur: A Property Owner’s Guide | MOKA',
            'heading'     => 'Airbnb management in Kuala Lumpur: what owners should expect',
            'description' => 'What a short-stay management company actually does for a KL condo, how fees are usually structured, and the questions to ask before you hand over your keys.',
            'published'   => '2026-09-01',
            'updated'     => '2026-09-01',
            'image'       => 'images/layout/og-cover.jpg',
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
            'image'       => 'images/layout/og-cover.jpg',
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
            'image'       => 'images/layout/og-cover.jpg',
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
            'image'       => 'images/layout/og-cover.jpg',
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
            'image'       => 'images/layout/og-cover.jpg',
            'read_time'   => 6,
        ],

    ],

];
