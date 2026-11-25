<?php

/*
 * Big events the Revenue desk marks on its calendar (Sam, 25 Nov 2026): concerts,
 * exhibitions, festivals, and convocations at universities near our buildings.
 *
 * Kept by hand. Every entry was read from a public page (its 'url') on the 'checked'
 * date below; nothing here is from memory. Check again about once a month: add what
 * has been announced, and correct anything marked 'tentative'.
 *
 * 'area' decides which buildings count as "nearby". The map below is read from each
 * building's address; change it if the team knows better.
 *
 * 'size' sets the starting rate rise: stadium (40,000+) and convocation get one; arena
 * (5,000 to 20,000), expo, festival and graduation are only watched. A 'tentative' event
 * never gets a starting rise.
 */
return [
    'checked' => '2026-11-25',

    'areas' => [
        'Bukit Jalil'   => ['EkoCheras', 'Arte Cheras', 'Queensville'],
        'Cheras'        => ['EkoCheras', 'Arte Cheras', 'Queensville'],
        'KL city'       => ['Damai 88', 'EkoCheras', 'Arte Cheras', 'Queensville'],
        'KLCC'          => ['Damai 88'],
        'Shah Alam'     => ['Alinea', 'Forum'],
        'Sepang'        => ['Bell Suites', 'Mesahill'],
        'Sunsuria City' => ['Bell Suites'],
        'Nilai'         => ['Mesahill'],
        // Sunway, Dutamas (MITEC), Petaling Jaya, Genting, Batu Caves, Bangi (UKM): no building of ours is near enough to list.
        // If the team finds UKM families do stay at EkoCheras, add: 'Bangi' => ['EkoCheras'].
    ],

    // Read on 25 Nov 2026. 'tentative' = seen on one listing site only, or the venue or date is not final.
    'events' => [
        ['from' => '2026-11-28', 'to' => '2026-11-29', 'name' => 'Alan Tam, final world tour', 'venue' => 'Unifi Arena, Bukit Jalil', 'area' => 'Bukit Jalil', 'size' => 'arena',
            'url' => 'https://www.thestar.com.my/lifestyle/entertainment/2026/09/15/cantopop-legend-alan-tam-to-hold-concert-in-kl-on-nov-28-and-29'],
        ['from' => '2026-11-28', 'name' => 'Evanescence', 'venue' => 'National Hockey Stadium, Bukit Jalil', 'area' => 'Bukit Jalil', 'size' => 'arena',
            'url' => 'https://songkick.com/venues/4582935-national-hockey-stadium'],
        ['from' => '2026-12-01', 'to' => '2026-12-04', 'name' => 'SIGGRAPH Asia 2026', 'venue' => 'Kuala Lumpur Convention Centre', 'area' => 'KLCC', 'size' => 'expo',
            'url' => 'https://www.klccconventioncentre.com/whats-on'],
        ['from' => '2026-12-04', 'name' => '5 Seconds of Summer', 'venue' => 'Unifi Arena, Bukit Jalil', 'area' => 'Bukit Jalil', 'size' => 'arena',
            'url' => 'https://thesmartlocal.my/5sos-concert-kl/'],
        ['from' => '2026-12-05', 'name' => 'Ella: Sidang Penutup', 'venue' => 'Unifi Arena, Bukit Jalil', 'area' => 'Bukit Jalil', 'size' => 'arena',
            'url' => 'https://hitmanlive.com/'],
        ['from' => '2026-12-09', 'name' => 'Limp Bizkit', 'venue' => 'National Hockey Stadium, Bukit Jalil', 'area' => 'Bukit Jalil', 'size' => 'arena',
            'url' => 'https://www.businesstoday.com.my/2026/06/25/kl-lands-limp-bizkit-for-exclusive-asia-stop-in-december/'],
        ['from' => '2026-12-12', 'to' => '2026-12-13', 'name' => "BTS World Tour 'Arirang'", 'venue' => 'TM Stadium Nasional, Bukit Jalil', 'area' => 'Bukit Jalil', 'size' => 'stadium',
            'url' => 'https://www.livenation.my/bts-world-tour-arirang-in-kuala-lumpur-kuala-lumpur-edp1675786'],
        ['from' => '2026-12-19', 'to' => '2026-12-20', 'name' => 'Comic Fiesta 2026', 'venue' => 'Kuala Lumpur Convention Centre', 'area' => 'KLCC', 'size' => 'expo',
            'url' => 'https://www.rostercon.com/en/event-convention/comic-fiesta-2026'],
        ['from' => '2026-12-26', 'name' => 'BOYNEXTDOOR', 'venue' => 'Unifi Arena, Bukit Jalil', 'area' => 'Bukit Jalil', 'size' => 'arena',
            'url' => 'https://songkick.com/venues/3632319-unifi-arena'],
        ['from' => '2027-01-09', 'to' => '2027-01-10', 'name' => 'BIGBANG, 20th anniversary tour', 'venue' => 'TM Stadium Nasional, Bukit Jalil', 'area' => 'Bukit Jalil', 'size' => 'stadium',
            'url' => 'https://says.com/my/entertainment/bigbang-announces-2nd-night-in-kl-after-first-show-sells-out'],
        ['from' => '2027-01-09', 'name' => 'NCT 127', 'venue' => 'Unifi Arena, Bukit Jalil', 'area' => 'Bukit Jalil', 'size' => 'arena',
            'url' => 'https://www.thestar.com.my/lifestyle/entertainment/2026/11/13/k-pop-boy-group-nct-127-to-hold-concert-in-kl-on-jan-9'],
        ['from' => '2027-01-13', 'to' => '2027-01-14', 'name' => 'Westlife 25', 'venue' => 'Unifi Arena, Bukit Jalil', 'area' => 'Bukit Jalil', 'size' => 'arena',
            'url' => 'https://says.com/my/entertainment/westlife-is-coming-back-to-kl-in-2027'],
        ['from' => '2027-01-21', 'to' => '2027-01-31', 'name' => 'Malaysia Open, badminton', 'venue' => 'Unifi Arena, Bukit Jalil (venue to confirm)', 'area' => 'Bukit Jalil', 'size' => 'arena', 'tentative' => true,
            'url' => 'https://www.thestar.com.my/sport/badminton/2026/06/30/bwf-releases-world-tour-calendar-malaysian-open-in-january-malaysian-masters-in-august'],
        ['from' => '2027-01-23', 'name' => "Jay Chou 'Carnival II'", 'venue' => 'TM Stadium Nasional, Bukit Jalil', 'area' => 'Bukit Jalil', 'size' => 'stadium',
            'url' => 'https://www.malaymail.com/news/showbiz/2026/05/22/mandopop-king-jay-chou-to-return-to-malaysia-in-2027-carnival-ii-world-tour-concert/220956'],
        ['from' => '2027-01-29', 'to' => '2027-02-01', 'name' => 'MotoGP shakedown and official test', 'venue' => 'Sepang International Circuit', 'area' => 'Sepang', 'size' => 'festival',
            'url' => 'https://www.motogp.com/en/news/2026/09/02/2027-motogp-pre-season-dates-confirmed/1088122'],
        ['from' => '2027-01-30', 'name' => 'NU Fest 2027', 'venue' => 'Stadium Merdeka', 'area' => 'KL city', 'size' => 'festival',
            'url' => 'https://www.thestar.com.my/aseanplus/aseanplus-news/2026/10/13/nu-fest-2027-stadium-merdeka-to-become-a-celebration-of-music-culture-and-community'],
        ['from' => '2027-01-30', 'to' => '2027-01-31', 'name' => 'One Love Asia Festival 2027', 'venue' => 'Sunway Lagoon Surf Beach', 'area' => 'Sunway', 'size' => 'festival',
            'url' => 'https://www.etix.my/'],
        ['from' => '2027-03-01', 'to' => '2027-03-04', 'name' => 'Malaysian International Furniture Fair', 'venue' => 'MITEC and World Trade Centre KL', 'area' => 'KL city', 'size' => 'expo',
            'url' => 'https://miff.com.my/wp-content/uploads/2026/08/MIFF-2027-Fact-Sheet.pdf'],
        ['from' => '2027-03-27', 'to' => '2027-03-28', 'name' => 'IU, 2027 world tour', 'venue' => 'TM Stadium Nasional, Bukit Jalil', 'area' => 'Bukit Jalil', 'size' => 'stadium', 'tentative' => true,
            'url' => 'https://thesmartlocal.my/upcoming-concerts-malaysia-2027/'],

        // CONVOCATIONS at universities near our buildings (Sam, 25 Nov 2026). 'convocation' = several
        // sessions or days, families stay overnight; 'graduation' = one day on campus, watched only.
        ['from' => '2026-11-21', 'to' => '2026-11-26', 'name' => 'UniKL 23rd Convocation', 'venue' => 'World Trade Centre KL', 'area' => 'KL city', 'size' => 'graduation',
            'url' => 'https://convo.unikl.edu.my/convocation-sessions/'],
        ['from' => '2026-11-21', 'to' => '2026-11-30', 'name' => 'UKM 54th Convocation (14,311 graduates)', 'venue' => 'DECTAR, UKM Bangi', 'area' => 'Bangi', 'size' => 'convocation',
            'url' => 'https://www.ukm.my/konvo/'],
        // UiTM ke-105 runs 17 Oct to 16 Dec across all campuses. The Shah Alam day-by-day list is a
        // Google Drive file that needs a sign-in (https://bit.ly/3T1qMfT), so the Shah Alam days are
        // NOT confirmed: Shah Alam may already have finished. Tentative until someone checks.
        ['from' => '2026-11-25', 'to' => '2026-12-16', 'name' => 'UiTM 105th Convocation (Shah Alam days to confirm)', 'venue' => 'Dewan Agung Tuanku Canselor, UiTM Shah Alam', 'area' => 'Shah Alam', 'size' => 'convocation', 'tentative' => true,
            'url' => 'https://konvokesyen.uitm.edu.my/v2/index.php/istiadat-konvokesyen'],
        ['from' => '2026-11-28', 'name' => 'APU graduation ceremony', 'venue' => 'APU campus, Bukit Jalil', 'area' => 'Bukit Jalil', 'size' => 'graduation',
            'url' => 'https://graduation.apu.edu.my/'],
        ['from' => '2026-12-05', 'name' => 'UCSI 39th Convocation, December sessions', 'venue' => 'Le Quadri, UCSI Cheras', 'area' => 'Cheras', 'size' => 'graduation',
            'url' => 'https://www.ucsiuniversity.edu.my/dec-2026-convocation-ceremony-ii'],
        ['from' => '2026-12-05', 'to' => '2026-12-06', 'name' => 'MSU 38th Convocation', 'venue' => 'Chancellor Hall, MSU Shah Alam', 'area' => 'Shah Alam', 'size' => 'convocation',
            'url' => 'https://www.msu.edu.my/convocation/'],
        // Not announced yet on 25 Nov 2026; add when the university publishes the date:
        //   Xiamen University Malaysia (Bell Suites): usually September or October (2026: 10-11 Oct).
        //   USIM, Nilai (Mesahill): usually November (2026: 3-6 Nov).
        //   UiTM 106th, Shah Alam (Alinea, Forum): expected April or May 2027.
        //   MSU, Shah Alam: usually May and early December.
        //   Nilai University: 2026 was 26 Sep. INTI now holds its graduation in Bangsar South, not Nilai.
    ],
];
