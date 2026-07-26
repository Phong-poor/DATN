<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LienHe extends Model
{
    protected $table = 'lienhe';

    protected $fillable = [
        'hoten',
        'email',
        'sodienthoai',
        'noidung',
        'trangthai',
        'phanhoi',
        'danhmuc',
        'phan_hoi_luc',
        'loai_yeu_cau',
        'showroom_id',
        'showroom_ten',
        'showroom_diachi',
        'ngay_hen',
        'khung_gio'
    ];
}
