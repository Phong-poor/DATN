<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateReferral extends Model
{
    protected $table = 'affiliate_gioi_thieu';

    protected $fillable = [
        'id_affiliate_khachhang',
        'id_khachhang_duoc_gioithieu',
        'ma_ref',
        'da_dang_ky_luc',
    ];

    protected $casts = [
        'da_dang_ky_luc' => 'datetime',
    ];

    public function affiliateUser()
    {
        return $this->belongsTo(User::class, 'id_affiliate_khachhang');
    }

    public function referredUser()
    {
        return $this->belongsTo(User::class, 'id_khachhang_duoc_gioithieu');
    }
}

