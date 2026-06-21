<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateWithdrawRequest extends Model
{
    protected $table = 'affiliate_yeu_cau_rut_tien';

    protected $fillable = [
        'id_affiliate_khachhang',
        'so_tien',
        'ten_ngan_hang',
        'ten_chu_tai_khoan',
        'so_tai_khoan',
        'trangthai',
        'ghichu',
        'duoc_duyet_luc',
        'duoc_thanh_toan_luc',
    ];

    protected $casts = [
        'duoc_duyet_luc' => 'datetime',
        'duoc_thanh_toan_luc' => 'datetime',
    ];

    public function affiliateUser()
    {
        return $this->belongsTo(User::class, 'id_affiliate_khachhang');
    }
}

