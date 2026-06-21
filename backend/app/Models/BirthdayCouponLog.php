<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BirthdayCouponLog extends Model
{
    use HasFactory;

    protected $table = 'nhat_ky_gui_ma_sinh_nhat';

    protected $fillable = [
        'id_khachhang',
        'id_voucher',
        'id_khachhang_voucher',
        'mavoucher',
        'email',
        'ngaysinh',
        'guiluc',
        'trangthai',
        'thongbaoloi',
    ];

    protected $casts = [
        'ngaysinh' => 'date',
        'guiluc' => 'datetime',
        'id_khachhang_voucher' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_khachhang');
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'id_voucher');
    }

    public function userVoucher()
    {
        return $this->belongsTo(UserVoucher::class, 'id_khachhang_voucher');
    }
}
