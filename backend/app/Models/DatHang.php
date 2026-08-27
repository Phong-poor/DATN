<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Đại diện đơn hàng, trạng thái thanh toán, vận chuyển và thông tin nhận hàng.
 */
class DatHang extends Model
{
    protected $table = 'dathang';
    protected $primaryKey = 'id_dathang';

    protected $fillable = [
        'id_khachhang',
        'id_nhanvien',
        'user_id',
        'tongtien',
        'trangthai',
        'diachi',
        'PTTT',
        'lydo',
        'refund_proof',
        'minh_chung_hoan_tien',
        'id_khuyenmai',
        'giam_gia',
        'xu_dung',
        'xu_nhan',
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

    protected static function booted()
    {
        static::saving(function ($order) {
            if (($order->trangthai === 'done' || $order->trangthai === 'completed') && strtolower(trim((string)$order->PTTT)) === 'cod') {
                $order->trang_thai_thanh_toan = 'paid';
                if (!$order->thanh_toan_luc) {
                    $order->thanh_toan_luc = now();
                }
            }
        });

        static::saved(function (DatHang $order) {
            if ($order->wasChanged(['trangthai', 'trang_thai_thanh_toan'])) {
                app(\App\Services\AffiliateCommissionService::class)->syncOrderStatus($order);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_khachhang');
    }

    public function getUserIdAttribute()
    {
        return $this->id_khachhang;
    }

    public function setUserIdAttribute($value): void
    {
        $this->attributes['id_khachhang'] = $value;
    }

    public function chi_tiets()
    {
        return $this->hasMany(DatHangChiTiet::class, 'id_dathang', 'id_dathang');
    }

    public function chiTiets()
    {
        return $this->chi_tiets();
    }

    public function nhanVien()
    {
        return $this->belongsTo(Admin::class, 'id_nhanvien', 'id');
    }
}
