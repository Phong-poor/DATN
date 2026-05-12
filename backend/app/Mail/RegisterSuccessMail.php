<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class RegisterSuccessMail extends Mailable
{
    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Đăng ký thành công 🎉')
                    ->view('emails.register-success')
                    ->with([
                        'user' => $this->user
                    ]);
    }
}