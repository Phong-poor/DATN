<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateProfile extends Model
{
    protected $table = 'khach_hang_affiliate';

    protected $fillable = [
        'id_khachhang',
        'ma_affiliate',
        'ty_le_hoa_hong',
        'trangthai',
        'tong_thu_nhap',
        'tong_da_thanh_toan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_khachhang');
    }
}

