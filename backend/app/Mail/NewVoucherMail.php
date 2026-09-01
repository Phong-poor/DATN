<?php

namespace App\Mail;

use App\Models\Promotion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewVoucherMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Promotion $voucher,
        public string $subscriberEmail
    ) {}

    public function build(): self
    {
        return $this
            ->subject('🎁 Voucher mới dành cho bạn: ' . $this->voucher->code)
            ->view('emails.new-voucher')
            ->with([
                'voucher'         => $this->voucher,
                'subscriberEmail' => $this->subscriberEmail,
            ]);
    }
}
