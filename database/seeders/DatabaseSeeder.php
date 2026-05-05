<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Owner user
        DB::table('users')->insert([
            'name'       => 'MOKA Owner',
            'email'      => 'owner@moka.app',
            'password'   => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sample listings
        $listings = [
            ['title' => 'Luxury Suite @ KLCC', 'zone' => 'KLCC', 'address' => 'Jalan Ampang, KLCC, 50450 KL', 'price' => 320, 'beds' => 2, 'baths' => 2, 'guests' => 4, 'rating' => 4.9, 'reviews' => 128],
            ['title' => 'Cosy Studio @ Bangsar', 'zone' => 'Bangsar', 'address' => 'Jalan Telawi, Bangsar, 59100 KL', 'price' => 180, 'beds' => 1, 'baths' => 1, 'guests' => 2, 'rating' => 4.8, 'reviews' => 94],
            ['title' => 'Modern Loft @ Mont Kiara', 'zone' => 'Mont Kiara', 'address' => 'Jalan Kiara, Mont Kiara, 50480 KL', 'price' => 250, 'beds' => 2, 'baths' => 2, 'guests' => 4, 'rating' => 4.7, 'reviews' => 67],
            ['title' => 'Penthouse @ Damansara', 'zone' => 'Damansara', 'address' => 'Tropicana, 47410 Petaling Jaya', 'price' => 480, 'beds' => 3, 'baths' => 3, 'guests' => 6, 'rating' => 5.0, 'reviews' => 43],
            ['title' => 'Garden View @ Desa Park City', 'zone' => 'Desa Park City', 'address' => 'Desa Park City, 52200 KL', 'price' => 290, 'beds' => 2, 'baths' => 2, 'guests' => 4, 'rating' => 4.9, 'reviews' => 81],
            ['title' => 'Sea View Suite @ Penang', 'zone' => 'Penang', 'address' => 'Gurney Drive, Georgetown, 10250 Penang', 'price' => 220, 'beds' => 2, 'baths' => 1, 'guests' => 4, 'rating' => 4.8, 'reviews' => 112],
        ];

        foreach ($listings as $l) {
            $id = DB::table('listings')->insertGetId([
                'title'          => $l['title'],
                'slug'           => Str::slug($l['title']),
                'description'    => 'A beautifully managed MOKA property in ' . $l['zone'] . '. Fully equipped with smart locks, premium linen, and 24/7 guest support. Perfect for business or leisure travel.',
                'address'        => $l['address'],
                'zone'           => $l['zone'],
                'price_per_night'=> $l['price'],
                'bedrooms'       => $l['beds'],
                'bathrooms'      => $l['baths'],
                'max_guests'     => $l['guests'],
                'rating'         => $l['rating'],
                'review_count'   => $l['reviews'],
                'featured'       => rand(0, 1),
                'status'         => 'active',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // Amenities
            foreach (['WiFi', 'Air Conditioning', 'Smart Lock', 'Kitchen', 'Washer', 'TV', 'Parking'] as $amenity) {
                DB::table('listing_amenities')->insert([
                    'listing_id' => $id, 'name' => $amenity, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            // Reviews
            $reviewers = [
                ['Ahmad Razif', 5, 'Absolutely stunning place! MOKA team was incredibly helpful throughout our stay.'],
                ['Sarah Lim',   5, 'Perfect location and spotlessly clean. Will definitely book again!'],
                ['James Tan',   4, 'Great value for money. The smart lock made check-in super easy.'],
            ];
            foreach ($reviewers as [$name, $rating, $comment]) {
                DB::table('listing_reviews')->insert([
                    'listing_id'    => $id,
                    'reviewer_name' => $name,
                    'rating'        => $rating,
                    'comment'       => $comment,
                    'stay_date'     => now()->subDays(rand(10, 90)),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }
}
