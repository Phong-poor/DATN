<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateProfile extends Model
{
    protected $fillable = [
        'user_id',
        'affiliate_code',
        'commission_rate',
        'status',
        'total_earned',
        'total_paid',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

