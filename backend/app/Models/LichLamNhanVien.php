<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LichLamNhanVien extends Model
{
    protected $table = 'lich_lam_nhan_vien';

    protected $fillable = ['id_nhanvien', 'loai_ca', 'ngay_bat_dau', 'ngay_ket_thuc', 'thu_lam_viec'];

    protected $casts = [
        'ngay_bat_dau' => 'date:Y-m-d',
        'ngay_ket_thuc' => 'date:Y-m-d',
        'thu_lam_viec' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_nhanvien');
    }
}
