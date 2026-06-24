<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Combo extends Model
{
    protected $table = 'combos';
    protected $primaryKey = 'id_combo';

    protected $fillable = [
        'ten_combo',
        'hinhanh',
        'mota',
        'giakhuyenmai',
        'trangthai',
    ];

    /**
     * Relationship: Products in this combo
     */
    public function sanPhams()
    {
        return $this->belongsToMany(SanPham::class, 'combo_sanpham', 'id_combo', 'id_sanpham');
    }

    public function triggeringVariants()
    {
        return $this->belongsToMany(BienThe::class, 'bienthe_combo_offers', 'id_combo', 'id_bienthe')
            ->withPivot(['loai_uudai', 'giakhuyenmai_override', 'mota_uudai', 'trangthai', 'ngay_het_han', 'gioi_han_soluong', 'da_su_dung']);
    }
}
