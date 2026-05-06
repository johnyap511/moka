<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class MonthlyReport extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The order instance.
     * @var $data22
     */
    public $data22;

    /**
     * Create a new message instance.
     *
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
        $address = 'notification@homemoka.com';
        $name = 'Moka';
        $subject = 'Monthly Property Performance Report';
        return $this->view('email.monthlymail')
            ->from($address, $name)
            // ->cc(['Vshaiksyda@gmail.com', 'mobitplusdev@gmail.com'])
            ->subject($subject)->attach($this->data22['filename']);
    }
}