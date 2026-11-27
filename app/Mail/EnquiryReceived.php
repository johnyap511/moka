<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/**
 * The acknowledgement a visitor receives after sending the estimate form or the contact form
 * (Sam, 27 Nov 2026). Plain text. Nothing the visitor typed is repeated back except a cleaned
 * name, so a bot cannot use it to carry its own text to someone else's inbox.
 */
class EnquiryReceived extends Mailable
{
    /** @param string $kind 'estimate' (an owner asking for a rental estimate) or 'contact' */
    public function __construct(public string $kind, public string $name, public array $replyToAddresses = [])
    {
    }

    public function build()
    {
        $phone = config('moka.company.phone', '+603-6789 2288');
        $estimate = $this->kind === 'estimate';
        $lines = [
            self::greeting($this->name),
            '',
            $estimate
                ? 'Thank you for your interest in MOKA. We have received your request for a rental estimate.'
                : 'Thank you for contacting MOKA. We have received your message.',
            '',
            $estimate
                ? 'Our team will contact you within one working day, on the phone number you gave us.'
                : 'We will reply within one working day.',
            '',
            'If it is urgent, reply to this email or call us at ' . $phone . '.' . ($estimate ? '' : ' For an existing booking, please include your booking number.'),
            '',
            'MOKA',
            'homemoka.com',
        ];
        $mail = $this->subject($estimate ? 'MOKA: we have received your request' : 'MOKA: we have received your message')
            ->text('emails.plain', ['body' => implode("\n", $lines)]);
        foreach ($this->replyToAddresses as $address) {
            $mail->replyTo($address);
        }

        return $mail;
    }

    /** "Hi Name," when the name is plainly a name; otherwise a neutral greeting. */
    public static function greeting(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        $plain = $name !== '' && mb_strlen($name) <= 40 && preg_match('/^[\p{L}\p{M} .\'’\/@-]+$/u', $name) && !preg_match('/\.[a-z]{2,}\b/i', $name);

        return $plain ? 'Hi ' . $name . ',' : 'Hello,';
    }
}
