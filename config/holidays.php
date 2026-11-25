<?php

/*
 * Holidays the Revenue desk marks on its calendar (Sam, 25 Nov 2026), for Kuala Lumpur.
 *
 * Checked on 25 Nov 2026 against officeholidays.com (Kuala Lumpur 2026 and 2027) and
 * ecentral.my (school calendar, Kumpulan B, which includes Kuala Lumpur and Selangor).
 * 'confirm' => true marks a date that can still move: Islamic holidays follow the moon
 * sighting, and the two sources disagree on Deepavali 2027 (28 or 29 October).
 *
 * The eve of each public holiday is added by the page itself (the night before), so
 * only list an eve here when it is not the day before a public holiday.
 * Update this file each year when the Prime Minister's Department gazettes the list.
 */
return [
    'checked' => '2026-11-25',

    'public' => [
        ['date' => '2026-12-25', 'name' => 'Christmas Day'],
        ['date' => '2027-01-01', 'name' => "New Year's Day"],
        ['date' => '2027-01-22', 'name' => 'Thaipusam'],
        ['date' => '2027-02-01', 'name' => 'Federal Territory Day'],
        ['date' => '2027-02-06', 'name' => 'Chinese New Year'],
        ['date' => '2027-02-07', 'name' => 'Chinese New Year, day 2'],
        ['date' => '2027-02-24', 'name' => 'Nuzul Al-Quran', 'confirm' => true],
        ['date' => '2027-03-10', 'name' => 'Hari Raya Aidilfitri', 'confirm' => true],
        ['date' => '2027-03-11', 'name' => 'Hari Raya Aidilfitri, day 2', 'confirm' => true],
        ['date' => '2027-05-01', 'name' => 'Labour Day'],
        ['date' => '2027-05-17', 'name' => 'Hari Raya Haji', 'confirm' => true],
        ['date' => '2027-05-20', 'name' => 'Wesak Day'],
        ['date' => '2027-06-06', 'name' => 'Awal Muharram', 'confirm' => true],
        ['date' => '2027-06-07', 'name' => "Agong's Birthday"],
        ['date' => '2027-08-15', 'name' => 'Maulidur Rasul', 'confirm' => true],
        ['date' => '2027-08-31', 'name' => 'National Day'],
        ['date' => '2027-09-16', 'name' => 'Malaysia Day'],
        ['date' => '2027-10-29', 'name' => 'Deepavali', 'confirm' => true],
        ['date' => '2027-12-25', 'name' => 'Christmas Day'],
    ],

    // Busy nights that are not the eve of a public holiday.
    'eves' => [
        // New Year's Eve and Christmas Eve are already the night before a public holiday.
    ],

    // School holidays (Kumpulan B) and the extra festive school days around them.
    'seasons' => [
        ['from' => '2026-12-05', 'to' => '2026-12-31', 'name' => 'Year-end school holidays'],
        ['from' => '2027-02-05', 'to' => '2027-02-12', 'name' => 'Chinese New Year school break'],
        ['from' => '2027-03-06', 'to' => '2027-03-14', 'name' => 'School holidays, term 1 (Hari Raya week)'],
        ['from' => '2027-05-17', 'to' => '2027-05-21', 'name' => 'Hari Raya Haji and Wesak school break'],
        ['from' => '2027-05-22', 'to' => '2027-06-06', 'name' => 'Mid-year school holidays'],
        ['from' => '2027-08-28', 'to' => '2027-09-05', 'name' => 'School holidays, term 2'],
        ['from' => '2027-10-27', 'to' => '2027-10-29', 'name' => 'Deepavali school break'],
        ['from' => '2027-12-04', 'to' => '2027-12-31', 'name' => 'Year-end school holidays'],
    ],
];
