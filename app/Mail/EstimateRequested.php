<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/** An owner asked for an income estimate on the website; goes to hello@homemoka.com. */
class EstimateRequested extends Mailable
{
    public function __construct(public array $data)
    {
    }

    public function build()
    {
        $d = $this->data;
        $lines = [
            'New estimate request from homemoka.com',
            '',
            'Name:     ' . ($d['name'] ?? ''),
            'Email:    ' . ($d['email'] ?? ''),
            'Phone:    ' . ($d['phone'] ?? ''),
            'Address:  ' . ($d['address'] ?? ''),
            'Bedrooms: ' . ($d['bedroom'] ?? ''),
            'Type:     ' . ($d['type'] ?? ''),
            '',
            'Received: ' . now()->format('d M Y H:i'),
            'Reply to the owner directly; this address is the sender only.',
        ];

        return $this->subject('Estimate request: ' . ($d['name'] ?? 'owner') . ', ' . ($d['bedroom'] ?? '?') . ' bedroom ' . ($d['type'] ?? ''))
            ->replyTo($d['email'] ?: config('mail.from.address'), $d['name'] ?? null)
            ->text('emails.plain', ['body' => implode("\n", $lines)]);
    }
}
