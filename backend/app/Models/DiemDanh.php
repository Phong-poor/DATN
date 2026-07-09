<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiemDanh extends Model
{
    use HasFactory;

    protected $table = 'lich_su_diem_danh';

    protected $fillable = [
        'id_khachhang',
        'ngay_diem_danh',
        'streak',
        'so_xu_nhan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_khachhang');
    }
}
