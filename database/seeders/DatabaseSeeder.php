<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('permission_role')->truncate();
        DB::table('permission_user')->truncate();
        DB::table('role_user')->truncate();
        DB::table('users')->truncate();
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();
        DB::table('listings')->truncate();
        Schema::enableForeignKeyConstraints();

        // Create roles
        foreach ([
            ['name' => 'admin', 'display_name' => 'Administrator',  'description' => 'Full system access'],
            ['name' => 'owner', 'display_name' => 'Property Owner', 'description' => 'Property management access'],
            ['name' => 'user',  'display_name' => 'Guest User',     'description' => 'Booking access'],
        ] as $r) {
            \App\Role::create($r);
        }

        // Create staging accounts
        $accounts = [
            ['name' => 'MOKA Admin', 'email' => 'admin@moka.app', 'password' => Hash::make('Admin@2026'), 'role' => 'admin'],
            ['name' => 'MOKA Owner', 'email' => 'owner@moka.app', 'password' => Hash::make('Owner@2026'), 'role' => 'owner'],
            ['name' => 'Guest User', 'email' => 'user@moka.app',  'password' => Hash::make('User@2026'),  'role' => 'user'],
        ];

        foreach ($accounts as $a) {
            $role = $a['role'];
            unset($a['role']);
            $user = \App\User::create(array_merge($a, ['status' => 1]));
            $user->attachRole(\App\Role::where('name', $role)->first());
        }

        // Sample listings linked to owner
        $owner = \App\User::where('email', 'owner@moka.app')->first();
        $listings = [
            ['klcc-luxury-suite',  'Luxury Suite @ KLCC',          'Jalan Ampang, KLCC, 50450 KL',           320],
            ['bangsar-cosy-studio','Cosy Studio @ Bangsar',         'Jalan Telawi, Bangsar, 59100 KL',        180],
            ['mont-kiara-loft',    'Modern Loft @ Mont Kiara',      'Jalan Kiara, Mont Kiara, 50480 KL',      250],
            ['damansara-penthouse','Penthouse @ Damansara',         'Tropicana, 47410 Petaling Jaya',         480],
            ['desa-park-garden',   'Garden View @ Desa Park City',  'Desa Park City, 52200 KL',               290],
            ['penang-sea-view',    'Sea View Suite @ Penang',       'Gurney Drive, Georgetown, 10250 Penang', 220],
        ];

        foreach ($listings as [$key, $title, $address, $price]) {
            \App\Listing::create([
                'user_id'       => $owner->id,
                'name'          => $key,
                'key'           => $key,
                'title'         => $title,
                'address'       => $address,
                'default_price' => $price,
                'type'          => 'solo',
                'status'        => 1,
            ]);
        }

        $this->command->info('Roles: admin, owner, user created');
        $this->command->info('Accounts: admin@moka.app | owner@moka.app | user@moka.app');
        $this->command->info('6 sample listings seeded');
    }
}
