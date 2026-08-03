<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Đại diện một phiên bản sản phẩm theo cấu hình thuộc tính, giá và tồn kho.
 */
class BienThe extends Model
{
    protected $table = 'bienthe';

    protected $primaryKey = 'id_bienthe';

    public $timestamps = false;

    protected $fillable = [
        'id_sanpham',
        'ten_bienthe',
        'gia',
        'soluong',
        'thuoc_tinh_json',
    ];

    public function hinhAnhs()
    {
        return $this->hasMany(BienTheHinhAnh::class, 'id_bienthe', 'id_bienthe');
    }

    public function reviews()
    {
        return $this->hasMany(DanhGia::class, 'id_bienthe', 'id_bienthe');
    }

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'id_sanpham', 'id_sanpham');
    }

    public function giaTriThuocTinhs()
    {
        return $this->belongsToMany(
            GiaTriThuocTinh::class,
            'bienthe_thuoctinh',
            'id_bienthe',
            'id_giatri'
        );
    }

    public function comboOffers()
    {
        return $this->belongsToMany(Combo::class, 'bienthe_combo_offers', 'id_bienthe', 'id_combo')
            ->withPivot(['loai_uudai', 'giakhuyenmai_override', 'mota_uudai', 'trangthai', 'ngay_het_han', 'gioi_han_soluong', 'da_su_dung'])
            ->wherePivot('trangthai', 1);
    }
}
