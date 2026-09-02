<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email
    ) {}

    public function build(): self
    {
        return $this
            ->subject('🎉 Chào mừng bạn đến với NextGen Newsletter!')
            ->view('emails.newsletter-welcome')
            ->with(['email' => $this->email]);
    }
}
