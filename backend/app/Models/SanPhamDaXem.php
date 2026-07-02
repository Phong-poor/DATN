<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanPhamDaXem extends Model
{
    use HasFactory;

    protected $table = 'sanpham_daxem';

    protected $fillable = [
        'id_khachhang',
        'id_sanpham',
        'xem_luc',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_khachhang');
    }

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'id_sanpham', 'id_sanpham');
    }
}
