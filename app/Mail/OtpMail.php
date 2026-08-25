<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code, public string $name = '') {}

    public function build()
    {
        return $this->subject('Kode Verifikasi E-Trace: ' . $this->code)
            ->view('emails.otp');
    }
}
