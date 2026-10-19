<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/** Receipt to the guest after a deposit refund request, so they know it arrived and do not submit twice. */
class RefundReceived extends Mailable
{
    /** @param \App\Models\RefundRequest[] $requests one per unit, same submission */
    public function __construct(public array $requests)
    {
    }

    public function build()
    {
        $first = $this->requests[0];
        $refs  = implode(', ', array_map(fn ($r) => '#' . $r->id, $this->requests));
        $units = implode(', ', array_map(fn ($r) => $r->unit_no, $this->requests));
        $lines = [
            'Hi ' . $first->name . ',',
            '',
            'We have received your deposit refund request.',
            '',
            'Reference:  ' . $refs,
            'Property:   ' . $first->property,
            'Unit:       ' . $units,
            'Check-out:  ' . $first->check_out->format('j M Y'),
            'Refund due: by ' . $first->due_on->format('l, j M Y') . ' (3 working days)',
            '',
            'The deposit is returned to the bank account you gave us. There is no need to submit the form again;',
            'if anything needs confirming we will WhatsApp or call the number you entered.',
            '',
            'Moka Venture Sdn Bhd · hello@homemoka.com · homemoka.com',
        ];
        return $this->subject('Deposit refund request received, ref ' . $refs)
            ->text('emails.plain', ['body' => implode("\n", $lines)]);
    }
}
