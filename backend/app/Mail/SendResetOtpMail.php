<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class SendResetOtpMail extends Mailable
{
    public $otp;

    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Mã OTP đặt lại mật khẩu')
                    ->view('emails.reset-otp');
    }
}