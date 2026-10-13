<?php

namespace App\Models;

use App\Booking;
use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * One deposit refund request from a guest. Bank account holder and number are
 * encrypted at rest (APP_KEY); they are shown only to roles with refunds.manage.
 * Rows are never deleted (Sam, 11 Oct 2026).
 */
class RefundRequest extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'bank_holder'  => 'encrypted',
        'bank_account' => 'encrypted',
        'check_out'    => 'date',
        'due_on'       => 'date',
        'paid_at'      => 'datetime',
        'deduction_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function link()
    {
        return $this->belongsTo(RefundLink::class, 'refund_link_id');
    }

    /** Last four digits only, for lists seen by roles without refunds.manage. */
    public function maskedAccount(): string
    {
        $n = preg_replace('/\D/', '', (string) $this->bank_account);
        return $n === '' ? '—' : str_repeat('•', max(0, strlen($n) - 4)) . substr($n, -4);
    }

    /** Deposit less deduction, when the deposit is known. */
    public function refundDue(): ?float
    {
        return $this->deposit_amount === null ? null : max(0, round((float) $this->deposit_amount - (float) $this->deduction_amount, 2));
    }

    public function deductor()
    {
        return $this->belongsTo(User::class, 'deduction_by');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_on && $this->due_on->isPast() && !$this->due_on->isToday();
    }
}
