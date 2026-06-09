<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SanPham extends Model
{
    protected $table      = 'sanpham';
    protected $primaryKey = 'id_sanpham';
    public $timestamps    = false;

    protected $fillable = [
        'id_danhmuc',
        'id_thuonghieu',
        'tenSP',
        'SKU',
        'trangthai',
        'hinhanh',
        'khoiluong',
        'thong_so_ky_thuat',
    ];

    protected $casts = [
        'thong_so_ky_thuat' => 'array',
    ];

    public function danhMuc()
    {
        return $this->belongsTo(DanhMuc::class, 'id_danhmuc', 'id_danhmuc');
    }

    public function thuongHieu()
    {
        return $this->belongsTo(ThuongHieu::class, 'id_thuonghieu', 'id_thuonghieu');
    }

    public function bienThes()
    {
        return $this->hasMany(BienThe::class, 'id_sanpham', 'id_sanpham');
    }
    public function hinhAnhs()
    {
        return $this->hasMany(BienTheHinhAnh::class, 'id_sanpham', 'id_sanpham');
    }
    public function reviews()
    {
        return $this->hasManyThrough(
            DanhGia::class,
            BienThe::class,
            'id_sanpham',
            'id_bienthe',
            'id_sanpham',
            'id_bienthe'
        );
    }
}
