<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSaleSession extends Model
{
    protected $table = 'danh_sach_flashsale';
    protected $primaryKey = 'id_session';

    protected $fillable = [
        'ten_dot',
        'thoi_gian_bat_dau',
        'thoi_gian_ket_thuc',
        'trang_thai',
    ];

    protected $casts = [
        'thoi_gian_bat_dau' => 'datetime',
        'thoi_gian_ket_thuc' => 'datetime',
        'trang_thai' => 'integer',
    ];

    public function products()
    {
        return $this->hasMany(FlashSaleProduct::class, 'id_danhsach', 'id_session');
    }

    // Helper to check if session is active
    public function isActive()
    {
        $now = now();
        return $this->trang_thai === 1 
            && $this->thoi_gian_bat_dau 
            && $this->thoi_gian_ket_thuc
            && $now->gte($this->thoi_gian_bat_dau)
            && $now->lte($this->thoi_gian_ket_thuc);
    }
}
