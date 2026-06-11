<?php

return [
    'groups' => [
        'Dashboard' => [
            'dashboard.view' => 'View Dashboard',
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
    ],
    'roles' => [
        'super_admin' => [
            'label'       => 'Super Admin',
            'description' => 'Full access to everything',
            'permissions' => ['*'],
        ],
        'manager' => [
            'label'       => 'Manager',
            'description' => 'Manage owners, listings, and bookings',
            'permissions' => [
                'dashboard.view',
                'users.view',
                'owners.view', 'owners.manage',
                'listings.view', 'listings.manage',
                'bookings.view', 'bookings.manage',
                'calendar.view',
                'settings.view',
            ],
        ],
        'finance' => [
            'label'       => 'Finance',
            'description' => 'Access payments and revenue reports',
            'permissions' => [
                'dashboard.view',
                'owners.view',
                'listings.view',
                'bookings.view',
                'finance.view', 'finance.manage',
            ],
        ],
        'operations' => [
            'label'       => 'Operations',
            'description' => 'Manage EZEE bookings and assignments',
            'permissions' => [
                'dashboard.view',
                'owners.view',
                'listings.view',
                'bookings.view', 'bookings.manage',
                'ezee.view', 'ezee.manage', 'ezee.delete', 'ezee.history',
                'calendar.view',
            ],
        ],
    ],
];
