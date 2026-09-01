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