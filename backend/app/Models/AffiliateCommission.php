<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateCommission extends Model
{
    protected $table = 'lien_ket_affiliate';

    protected $appends = [
        'affiliate_user_id',
        'referred_user_id',
        'order_id',
        'amount',
        'status',
        'note',
        'approved_at',
        'paid_at',
    ];

    protected $fillable = [
        'id_affiliate_khachhang',
        'id_khachhang_duoc_gioithieu',
        'id_donhang',
        'so_tien',
        'trangthai',
        'duoc_duyet_luc',
        'duoc_thanh_toan_luc',
        'ghichu',
    ];

    protected $casts = [
        'duoc_duyet_luc' => 'datetime',
        'duoc_thanh_toan_luc' => 'datetime',
    ];

    public function affiliateUser()
    {
        return $this->belongsTo(User::class, 'id_affiliate_khachhang');
    }

    public function referredUser()
    {
        return $this->belongsTo(User::class, 'id_khachhang_duoc_gioithieu');
    }

    public function order()
    {
        return $this->belongsTo(DatHang::class, 'id_donhang', 'id_dathang');
    }

    public function getAffiliateUserIdAttribute()
    {
        return $this->id_affiliate_khachhang;
    }

    public function getReferredUserIdAttribute()
    {
        return $this->id_khachhang_duoc_gioithieu;
    }

    public function getOrderIdAttribute()
    {
        return $this->id_donhang;
    }

    public function getAmountAttribute()
    {
        return $this->so_tien;
    }

    public function getStatusAttribute()
    {
        return $this->trangthai;
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['trangthai'] = $value;
    }

    public function getNoteAttribute()
    {
        return $this->ghichu;
    }

    public function setNoteAttribute($value): void
    {
        $this->attributes['ghichu'] = $value;
    }

    public function getApprovedAtAttribute()
    {
        return $this->duoc_duyet_luc;
    }

    public function setApprovedAtAttribute($value): void
    {
        $this->attributes['duoc_duyet_luc'] = $value;
    }

    public function getPaidAtAttribute()
    {
        return $this->duoc_thanh_toan_luc;
    }

    public function setPaidAtAttribute($value): void
    {
        $this->attributes['duoc_thanh_toan_luc'] = $value;
    }
}
