<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = 'tintuc';

    protected $fillable = [
        'tieude',
        'slug',
        'tomtat',
        'noidung',
        'danhmuc',
        'tacgia',
        'hinhanh',
        'mota_hinhanh',
        'trangthai',
        'dang_luc',
        'luotxem',
    ];
    protected $casts = [
        'dang_luc' => 'datetime',
    ];
}
