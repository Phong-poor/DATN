<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonXinNghi extends Model
{
    protected $table = 'don_xin_nghi';

    protected $fillable = [
        'id_nhanvien', 'loai_nghi', 'thoi_luong', 'tu_ngay', 'den_ngay', 'ly_do',
        'minh_chung', 'nguoi_ban_giao', 'ghi_chu_ban_giao', 'trang_thai',
        'phan_hoi_quan_ly', 'xu_ly_boi', 'xu_ly_luc',
    ];

    protected $casts = [
        'tu_ngay' => 'date:Y-m-d',
        'den_ngay' => 'date:Y-m-d',
        'xu_ly_luc' => 'datetime',
    ];

    public function nhanVien()
    {
        return $this->belongsTo(Admin::class, 'id_nhanvien');
    }

    public function nguoiXuLy()
    {
        return $this->belongsTo(Admin::class, 'xu_ly_boi');
    }
}
