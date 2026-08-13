<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateProfile extends Model
{
    protected $table = 'khach_hang_affiliate';

    protected $appends = [
        'user_id',
        'affiliate_code',
        'commission_rate',
        'status',
        'total_earned',
        'total_paid',
    ];

    protected $fillable = [
        'id_khachhang',
        'ma_affiliate',
        'ty_le_hoa_hong',
        'trangthai',
        'tong_thu_nhap',
        'tong_da_thanh_toan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_khachhang');
    }

    public function getUserIdAttribute()
    {
        return $this->id_khachhang;
    }

    public function getAffiliateCodeAttribute()
    {
        return $this->ma_affiliate;
    }

    public function getCommissionRateAttribute()
    {
        return $this->ty_le_hoa_hong;
    }

    public function setCommissionRateAttribute($value): void
    {
        $this->attributes['ty_le_hoa_hong'] = $value;
    }

    public function getStatusAttribute()
    {
        return $this->trangthai;
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['trangthai'] = $value;
    }

    public function getTotalEarnedAttribute()
    {
        return $this->tong_thu_nhap;
    }

    public function getTotalPaidAttribute()
    {
        return $this->tong_da_thanh_toan;
    }
}
