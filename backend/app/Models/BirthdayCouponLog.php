<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BirthdayCouponLog extends Model
{
    use HasFactory;

    protected $table = 'birthday_coupon_logs';

    protected $fillable = [
        'user_id',
        'promotion_id',
        'user_voucher_id',
        'voucher_code',
        'email',
        'birthday_date',
        'sent_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'birthday_date' => 'date',
        'sent_at' => 'datetime',
        'user_voucher_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    public function userVoucher()
    {
        return $this->belongsTo(UserVoucher::class, 'user_voucher_id');
    }
}
