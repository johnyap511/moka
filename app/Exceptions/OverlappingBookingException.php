<?php

namespace App\Exceptions;

use App\Booking;
use RuntimeException;

/**
 * A unit cannot hold two live bookings on the same night.
 */
class OverlappingBookingException extends RuntimeException
{
    public Booking $conflicting;

    public function __construct(Booking $conflicting, string $message)
    {
        $this->conflicting = $conflicting;

        parent::__construct($message);
    }
}
