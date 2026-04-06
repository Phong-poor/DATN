<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BirthdayCouponMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $customerName;
    public string $couponCode;

    public function __construct(string $customerName, string $couponCode)
    {
        $this->customerName = $customerName;
        $this->couponCode = $couponCode;
    }

    public function build(): self
    {
        return $this
            ->subject('Chúc mừng sinh nhật - Mã giảm giá dành cho bạn')
            ->view('emails.birthday-coupon');
    }
}