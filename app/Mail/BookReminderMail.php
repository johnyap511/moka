<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class BookReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The order instance.
     * @var $data22
     */
    public $data22;

    /**
     * Create a new message instance.
     * @return void
     */
    public function __construct($data22)
    {
        $this->data22 = $data22;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $address = 'sam@homemoka.com';
        $name = 'Moka';
        $subject = 'Booking Reminder';
        return $this->view('email.bookingReminder')
            ->from($address, $name)
            ->subject($subject);
    }
}
