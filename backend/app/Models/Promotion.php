<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $table = 'vouchers';

    public function userVouchers()
    {
        return $this->hasMany(UserVoucher::class, 'id_voucher');
    }

    protected $fillable = [
        'ten',
        'danhmuc',
        'code',
        'loai',
        'giatri',
        'ngaybatdau',
        'ngayketthuc',
        'trangthai',
        'mota',
        'loai_dieu_kien',
        'dieu_kien',
        'congkhai',
        'dieu_kien_tang',
        'so_luong_phat',
    ];

    public $timestamps = false;
}
