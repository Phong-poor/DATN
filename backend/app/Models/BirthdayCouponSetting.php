<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BirthdayCouponSetting extends Model
{
    use HasFactory;

    protected $table = 'cai_dat_ma_sinh_nhat';

    protected $fillable = [
        'kichhoat',
        'giochay',
        'thoi_han_ngay',
        'mavoucher',
        'id_voucher',
        'id_mau_email',
        'gui_mot_lan_moi_nam',
        'thu_lai_khi_that_bai',
        'thongbao_admin',
    ];

    protected $casts = [
        'kichhoat' => 'boolean',
        'gui_mot_lan_moi_nam' => 'boolean',
        'thu_lai_khi_that_bai' => 'boolean',
        'thongbao_admin' => 'boolean',
        'id_voucher' => 'integer',
        'thoi_han_ngay' => 'integer',
    ];
}
