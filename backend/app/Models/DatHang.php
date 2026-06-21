<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatHang extends Model
{
    protected $table = 'dathang';
    protected $primaryKey = 'id_dathang';

    protected $fillable = [
        'id_khachhang',
        'tongtien',
        'trangthai',
        'diachi',
        'PTTT',
        'lydo',
        'minh_chung_hoan_tien',
        'id_khuyenmai',
        'giam_gia',
        'nha_cung_cap_thanh_toan',
        'trang_thai_thanh_toan',
        'ma_don_hang_thanh_toan',
        'ma_yeu_cau_thanh_toan',
        'ma_giao_dich_thanh_toan',
        'ma_ket_qua_thanh_toan',
        'thong_bao_thanh_toan',
        'kieu_thanh_toan',
        'thanh_toan_luc',
        'du_lieu_thanh_toan',
    ];

    protected $casts = [
        'du_lieu_thanh_toan' => 'array',
        'thanh_toan_luc' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_khachhang');
    }

    public function chi_tiets()
    {
        return $this->hasMany(DatHangChiTiet::class, 'id_dathang', 'id_dathang');
    }

    public function chiTiets()
    {
        return $this->chi_tiets();
    }
}
