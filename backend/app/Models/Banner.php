<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'tieude',
        'phude',
        'chudenho',
        'noibat',
        'mota',
        'hinhanh',
        'loaimedia',
        'hinhanh_mobile',
        'loai_media_mobile',
        'duongdan',
        'id_sanpham',
        'nhanchinh',
        'nhanphu',
        'huyhieu_sanpham',
        'dactinh_sanpham',
        'vitri',
        'trangthai',
        'batdauluc',
        'ketthucluc',
    ];

    protected $casts = [
        'trangthai' => 'boolean',
        'batdauluc' => 'datetime',
        'ketthucluc' => 'datetime',
    ];
}
