<?php

namespace App;

use App\OtherModel\EzeeBooking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'bookings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'listing_id', 'folio_no', 'server_folio_no', 'check_in', 'check_out', 'adult', 'infant', 'remark', 'nights', 'price_night', 'cleaning_fee', 'ota_fee', 'sst', 'sst_cf',
        'price', 'source', 'category', 'status', 'tourism_tax', 'discount_fee', 'water',
        //Status
        // 1->Pending booking
        // 3->Processing
        // 4->pay by cash
        // 5->Confirmed
        // 6->user reviewed 7->owner reviewed 8->completed
    ];

    /**
     * Get the user that owns the booking.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the listing that owns the booking.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    /**
     * A unit may hold only one live booking on any given night.
     *
     * Enforced on the model rather than in each screen because bookings are
     * created from the admin, the owner portal, the EZEE sync, the automatic
     * assignment, the public site and spreadsheet imports. Guarding those one at
     * a time leaves whichever is added next unguarded, and a double booking is
     * not a display problem — it is two guests sent to one apartment.
     *
     * Cancelled bookings (status 1) neither block nor are blocked.
     */
    protected static function booted()
    {
        static::saving(function (self $booking) {
            if (static::$skipOverlapCheck) {
                return;
            }

            if ((int) $booking->status === 1) {
                return;
            }

            if (!$booking->listing_id || !$booking->check_in || !$booking->check_out) {
                return;
            }

            // An untouched row being re-saved for an unrelated reason must not
            // be rejected by a clash that already existed.
            if ($booking->exists && !$booking->isDirty(['listing_id', 'check_in', 'check_out', 'status'])) {
                return;
            }

            $clash = static::where('listing_id', $booking->listing_id)
                ->where('status', '!=', 1)
                ->when($booking->getKey(), fn ($q) => $q->where('id', '!=', $booking->getKey()))
                ->where('check_in', '<', $booking->check_out)
                ->where('check_out', '>', $booking->check_in)
                ->first();

            if ($clash) {
                throw new \App\Exceptions\OverlappingBookingException($clash, sprintf(
                    'Unit already has booking #%d from %s to %s. A unit cannot hold two bookings on the same night.',
                    $clash->id,
                    $clash->check_in,
                    $clash->check_out
                ));
            }
        });
    }

    /**
     * Historical data repair only.
     *
     * The table already contains overlapping rows from before this rule existed;
     * correcting them means saving rows that still clash while the work is in
     * progress.
     */
    protected static bool $skipOverlapCheck = false;

    public static function withoutOverlapCheck(callable $work)
    {
        static::$skipOverlapCheck = true;

        try {
            return $work();
        } finally {
            static::$skipOverlapCheck = false;
        }
    }

    public function listing(): BelongsTo
    {
        // withoutGlobalScope: an archived property disappears from lists and
        // pickers, but a record already pointing at one must still resolve it —
        // history should stay readable after a property is handed back.
        return $this->belongsTo(Listing::class, 'listing_id', 'id')->withoutGlobalScope('notArchived');
    }

    /**
     * Get the ezee booking information associated with the booking.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function ezeeBooking()
    {
        return $this->hasOne(EzeeBooking::class, 'book_id', 'id');
    }

    /**
     * Get the SubBookingId attribute from associated ezee booking.
     *
     * @return string|null
     */
    public function getReservationNoAttribute()
    {
        return $this->ezeeBooking ? $this->ezeeBooking->SubBookingId : null;
    }
}