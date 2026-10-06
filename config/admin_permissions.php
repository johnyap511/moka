<?php

return [
    'groups' => [
        'Dashboard' => [
            'dashboard.view'    => 'View Dashboard',
            'dashboard.revenue' => 'View revenue figures on the dashboard',
        ],
        'Users' => [
            'users.view'   => 'View Users',
            'users.manage' => 'Create / Edit / Delete Users',
        ],
        'Owners' => [
            'owners.view'   => 'View Owners',
            'owners.manage' => 'Create / Edit / Delete Owners',
        ],
        'Listings' => [
            'listings.view'   => 'View Listings',
            'listings.manage' => 'Create / Edit Listings',
            'listings.delete' => 'Delete Listings',
        ],
        'Bookings' => [
            'bookings.view'   => 'View Bookings',
            'bookings.manage' => 'Create / Edit Bookings',
            'bookings.delete' => 'Delete Bookings',
        ],
        'EZEE' => [
            'ezee.view'    => 'View EZEE Bookings',
            'ezee.manage'  => 'Upload / Assign EZEE Bookings',
            'ezee.delete'  => 'Delete EZEE Bookings',
            'ezee.history' => 'Run Historical API',
        ],
        'Finance' => [
            'finance.view'   => 'View Payments & Reports',
            'finance.manage' => 'Edit Payment Records',
        ],
        'Settings' => [
            'settings.view'   => 'View Settings',
            'settings.manage' => 'Edit Settings',
            'roles.manage'    => 'Manage Admin Roles',
        ],
        'Calendar' => [
            'calendar.view' => 'View & Export Calendar',
        ],
        'Reports' => [
            'reports.generate' => 'Generate the monthly owner reports',
        ],
        'Sales' => [
            'sales.view'   => 'View sales commission (all sales persons)',
            'sales.manage' => 'Upload eZee Transaction Detail Reports',
            'sales.own'    => 'See own sales commission only',
        ],
    ],
    'roles' => [
        'super_admin' => [
            'label'       => 'Super Admin',
            'description' => 'Everything, including roles and mail settings',
            'permissions' => ['*'],
        ],
        'admin' => [
            'label'       => 'Admin',
            'description' => 'Everything except managing admin roles',
            'permissions' => [
                'dashboard.view', 'dashboard.revenue',
                'users.view', 'users.manage',
                'owners.view', 'owners.manage',
                'listings.view', 'listings.manage', 'listings.delete',
                'bookings.view', 'bookings.manage', 'bookings.delete',
                'ezee.view', 'ezee.manage', 'ezee.delete', 'ezee.history',
                'finance.view', 'finance.manage',
                'settings.view', 'settings.manage',
                'calendar.view',
                'sales.view', 'sales.manage', 'sales.own',
                'reports.generate',
            ],
        ],
        'operations_manager' => [
            'label'       => 'Operation Manager',
            'description' => 'Operation Team plus the sales commission of everyone, uploads, KPIs and reports',
            'permissions' => [
                'dashboard.view',
                'users.view',
                'owners.view',
                'listings.view', 'listings.manage',
                'bookings.view', 'bookings.manage',
                'ezee.view', 'ezee.manage', 'ezee.delete', 'ezee.history',
                'finance.view',
                'calendar.view',
                'settings.view',
                'sales.view', 'sales.manage', 'sales.own',
                'reports.generate',
            ],
        ],
        'operations' => [
            'label'       => 'Operation Team',
            'description' => 'Bookings, eZee assignments and own commission',
            'permissions' => [
                'dashboard.view',
                'owners.view',
                'listings.view',
                'bookings.view', 'bookings.manage',
                'ezee.view', 'ezee.manage', 'ezee.delete', 'ezee.history',
                'calendar.view',
                'sales.own',
                'reports.generate',
            ],
        ],
        'finance_manager' => [
            'label'       => 'Finance Manager',
            'description' => 'Finance Team plus owners, users and settings',
            'permissions' => [
                'dashboard.view', 'dashboard.revenue',
                'users.view',
                'owners.view', 'owners.manage',
                'listings.view', 'listings.manage',
                'bookings.view', 'bookings.manage',
                'ezee.view', 'ezee.manage', 'ezee.delete', 'ezee.history',
                'finance.view', 'finance.manage',
                'calendar.view',
                'settings.view',
                'sales.view', 'sales.manage',
                'reports.generate',
            ],
        ],
        'finance' => [
            'label'       => 'Finance Team',
            'description' => 'Payments, revenue, eZee, sales commission uploads and owner reports',
            'permissions' => [
                'dashboard.view', 'dashboard.revenue',
                'owners.view',
                'listings.view', 'listings.manage',
                'bookings.view', 'bookings.manage',
                'ezee.view', 'ezee.manage', 'ezee.delete', 'ezee.history',
                'finance.view', 'finance.manage',
                'calendar.view',
                'sales.view', 'sales.manage',
                'reports.generate',
            ],
        ],
        'sales' => [
            'label'       => 'Sales Person',
            'description' => 'Sees only their own sales commission',
            'permissions' => [
                'sales.own',
            ],
        ],
    ],
];
