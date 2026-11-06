<?php

return [
    // The Delete button on the bookings list. Hidden since 7 Sep 2026: Cancel keeps history; delete does not.
    'show_delete_button' => (bool) env('MOKA_SHOW_DELETE', false),

    /*
     * Ground rule 27. The last month whose report has been sent to owners, as YYYY-MM.
     * That month and everything before it is stamped: jobs leave its bookings alone and
     * staff need a super admin password to change one. Move it forward when the next
     * monthly report goes out. It can only lock more than rule 17 does, never less.
     */
    'reported_through' => env('MOKA_REPORTED_THROUGH', '2026-08'),

    // Owner reports, Part B (Sam, 6 Nov 2026): the pooled groups whose tabs Homemoka writes into the
    // reconciled workbook for the engine. 'group' matches the start of the Homemoka group name;
    // 'markers' maps a group type to the engine's marker (Bell Suites is split by points), 'marker'
    // is the single label for an evenly split pool. A new pooled group is a row here plus the engine.
    'report_groups' => [
        ['tab' => 'BS Group',       'group' => 'Bell Suites', 'markers' => ['Type A' => 'STUDIO', 'Type B' => '3BR']],
        ['tab' => 'Alinea Group',   'group' => 'Alinea',      'marker' => 'ALINEA'],
        ['tab' => 'Mesahill Group', 'group' => 'Mesahill',    'marker' => 'MESAHILL'],
    ],

    // PMS month sheet (Sam, 6 Nov 2026): the thresholds of the reconciliation rules, in RM
    // unless stated. A stay longer than ltr_min_nights is treated as long term; differences
    // within sst_rounding are exact, within rounding are accumulated SST rounding; a whole-stay
    // difference below materiality is immaterial; deposit is the security deposit eZee holds.
    'pms_check' => [
        'ltr_min_nights' => (int) env('MOKA_PMS_LTR_MIN_NIGHTS', 300),
        'sst_rounding'   => (float) env('MOKA_PMS_SST_ROUNDING', 0.02),
        'rounding'       => (float) env('MOKA_PMS_ROUNDING', 0.50),
        'materiality'    => (float) env('MOKA_PMS_MATERIALITY', 100),
        'deposit'        => (float) env('MOKA_PMS_DEPOSIT', env('MOKA_REFUND_DEPOSIT', 200)),
    ],

    // Sales commission (Sam, 29 Sep 2026): 2% of room charges before SST, every sales person, every source.
    'sales_commission_rate' => (float) env('MOKA_SALES_COMMISSION_RATE', 0.02),

    // Owner reports (6 Oct 2026): the private Cloud Run service that runs the locked
    // report engine (repo moka-report-service). Token comes from the VM metadata server;
    // MOKA_REPORTS_TOKEN is only for a manual test from elsewhere.
    'reports_url'   => env('MOKA_REPORTS_URL'),
    'reports_token' => env('MOKA_REPORTS_TOKEN'),

    // Deposit refund portal (12 Oct 2026). Optional Cloudflare Turnstile on the guest
    // form: set both keys to turn the bot check on; leave empty and the form works without it.
    // Deposit refunds (Sam, 13 Oct 2026): every stay holds RM 200; Operations record deductions by noon
    // the next working day after the request; Finance confirms receipt before paying.
    'refund_deposit'         => (float) env('MOKA_REFUND_DEPOSIT', 200),
    'refund_ops_cutoff_hour' => (int) env('MOKA_REFUND_OPS_CUTOFF_HOUR', 12),

    // Admin accounts never offered in the guest form's "who served you" list (shared logins).
    'refund_staff_exclude' => ['Intern', 'Intern Moka'],

    // Company registration number shown on the guest refund page (SSM), once Sam supplies it.
    'company_reg'      => env('MOKA_COMPANY_REG', '202001018827 (1375147-V)'),

    // Company block printed on tenancy settlement statements (from Finance's calculator, 15 Oct 2026).
    'company' => [
        'name'  => 'MOKA VENTURE SDN. BHD.',
        'reg'   => env('MOKA_COMPANY_REG', '202001018827 (1375147-V)'),
        'addr1' => '11.1, 11th Floor Menara Lien Hoe, 8, Persiaran Tropicana, Tropicana Golf & Country Resort,',
        'addr2' => '47410 Petaling Jaya, Selangor Darul Ehsan, Malaysia.',
        'email' => 'hello@homemoka.com',
        'phone' => '+603-6789 2288',
    ],

    'turnstile_site'   => env('MOKA_TURNSTILE_SITE'),
    'turnstile_secret' => env('MOKA_TURNSTILE_SECRET'),
];
