<?php

namespace App\OtherModel;

use Illuminate\Database\Eloquent\Model;

class EzeeBooking extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'ezee_bookings';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'book_id',
        'ezee_group_id',
        'SubBookingId',
        'VoucherNo',
        'folio_no',
        'TransactionId',
        'IsConfirmed',
        'ezee_status',
        'ezee_current_status',
        'ezee_modified_at',
        'RateplanName',
        'RoomTypeName',
        'RoomName',
        'Start',
        'End',
        'CurrencyCode',
        'TotalAmountAfterTax',
        'TotalAmountBeforeTax',
        'TotalDiscount',
        'TotalExtraCharge',
        'TotalPayment',
        'TACommision',
        'FirstName',
        'LastName',
        'Mobile',
        'Email',
        'Country',
        'Source',
        'status',
        'created_at'
    ];

    protected static function booted()
    {
        // EZEE reports only the final room on a reservation, so a guest moved
        // mid-stay looks like they were in the last room throughout. The move
        // itself cannot be recovered from the API, but the fact that the
        // reservation changed after we assigned it can be — and that is what a
        // person needs to know to go and look.
        static::updating(function (self $booking) {
            if (!$booking->book_id || !$booking->isDirty('ezee_modified_at')) {
                return;
            }

            $was = $booking->getOriginal('ezee_modified_at');

            // Nothing to report the first time the field is populated, or if
            // EZEE hands back an older timestamp than the one already held.
            if (!$was || $booking->ezee_modified_at <= $was) {
                return;
            }

            $booking->logAmendment($was);
        });
    }

    /**
     * Raise a review item, unless one is already open for this booking — the
     * queue is only useful while it stays short.
     */
    private function logAmendment($previous): void
    {
        $open = \App\EzeeAssignmentLog::where('ezee_booking_id', $this->id)
            ->where('method', 'modified')
            ->whereNull('resolved_at')
            ->exists();

        if ($open) {
            return;
        }

        $listingId = \App\Booking::withoutGlobalScopes()
            ->where('id', $this->book_id)
            ->value('listing_id');

        \App\EzeeAssignmentLog::create([
            'ezee_booking_id' => $this->id,
            'listing_id'      => $listingId,
            'method'          => 'modified',
            'note'            => "Amended in EZEE on {$this->ezee_modified_at} (previously {$previous}). "
                               . 'The booking API reports only the final room, so check the stay in EZEE '
                               . 'for a room change or a date change.',
        ]);
    }
}
