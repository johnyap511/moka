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
        'SubBookingId',
        'TransactionId',
        'IsConfirmed',
        'RateplanName',
        'RoomTypeName',
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

}
