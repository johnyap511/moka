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
    // Ground rule 12: the one channel list (app/Support/Channel.php).
    public const SOURCES = [
        Channel::BOOKING, Channel::AGODA, Channel::EXPEDIA, Channel::AIRBNB, Channel::TRIP, Channel::TRAVELOKA,
        Channel::TIKET, Channel::WEBSITE, Channel::LTR, Channel::OWNER,
    ];

    public const CATEGORIES = [
        'Accommodation',
        'Vacation',
    ];
}
