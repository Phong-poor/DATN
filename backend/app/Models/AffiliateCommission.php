<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateCommission extends Model
{
    protected $table = 'lien_ket_affiliate';

    protected $fillable = [
        'id_affiliate_khachhang',
        'id_khachhang_duoc_gioithieu',
        'id_donhang',
        'so_tien',
        'trangthai',
        'duoc_duyet_luc',
        'duoc_thanh_toan_luc',
        'ghichu',
    ];

    protected $casts = [
        'duoc_duyet_luc' => 'datetime',
        'duoc_thanh_toan_luc' => 'datetime',
    ];

    public function affiliateUser()
    {
        return $this->belongsTo(User::class, 'id_affiliate_khachhang');
    }

    public function referredUser()
    {
        return $this->belongsTo(User::class, 'id_khachhang_duoc_gioithieu');
    }

    public function order()
    {
        return $this->belongsTo(DatHang::class, 'id_donhang', 'id_dathang');
    }
}

