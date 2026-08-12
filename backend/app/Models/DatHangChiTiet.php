<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lưu sản phẩm, số lượng và giá tại thời điểm phát sinh một đơn hàng.
 */
class DatHangChiTiet extends Model
{
    protected $table = 'dathang_chitiet';

    protected $fillable = [
        'id_dathang',
        'id_bienthe',
        'soluong',
        'gia',
        'id_combo',
        'id_nhom_combo',
        'hoantien',
    ];

    protected $casts = [
        'hoantien' => 'boolean',
    ];

    protected $appends = [
        'is_refund',
    ];

    /**
     * Keep the API compatible with clients that use the more descriptive name.
     */
    public function getIsRefundAttribute(): bool
    {
        return (bool) $this->hoantien;
    }

    public function datHang()
    {
        return $this->belongsTo(DatHang::class, 'id_dathang', 'id_dathang');
    }

    public function bienThe()
    {
        return $this->belongsTo(BienThe::class, 'id_bienthe', 'id_bienthe');
    }

    // Quan hệ với combo
    public function combo()
    {
        return $this->belongsTo(Combo::class, 'id_combo', 'id_combo');
    }
}
