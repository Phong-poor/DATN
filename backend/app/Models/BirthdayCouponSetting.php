<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BirthdayCouponSetting extends Model
{
    use HasFactory;

    protected $table = 'birthday_coupon_settings';

    protected $fillable = [
        'enabled',
        'run_time',
        'promotion_code',
        'promotion_id',
        'email_template_id',
        'send_once_per_year',
        'retry_if_failed',
        'notify_admin',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'send_once_per_year' => 'boolean',
        'retry_if_failed' => 'boolean',
        'notify_admin' => 'boolean',
        'promotion_id' => 'integer',
    ];
}
