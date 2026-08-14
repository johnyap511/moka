<?php

namespace App\Support;

/**
 * Shared dropdown options for the booking forms.
 *
 * The source values must match the strings the fee calculation branches on
 * (see EzeePricing and BookController::ezeeBookingStoreEdit) — a mismatch here
 * silently sends a booking down the default M&A rate instead of its own.
 */
class BookingOptions
{
    public const SOURCES = [
        'Booking.com',
        'Airbnb',
        'Agoda',
        'Expedia',
        'Traveloka',
        'CTrip',
        'Trip.com',
        'Tiket.com',
        'Walk In',
        'PMS',
        'Website',
        'Long Term Rental',
        'Owner',
    ];

    public const CATEGORIES = [
        'Accommodation',
        'Vacation',
    ];
}
