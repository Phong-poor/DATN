<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $table = 'vouchers';

    /**
     * Get the user vouchers for the promotion.
     */
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
        'name',
        'category',
        'type',
        'value',
        'start_date',
        'end_date',
        'status',
        'is_public',
    ];

    public $timestamps = false;

    public function getNameAttribute()
    {
        return $this->attributes['ten'] ?? null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['ten'] = $value;
    }

    public function getCategoryAttribute()
    {
        return $this->attributes['danhmuc'] ?? null;
    }

    public function setCategoryAttribute($value): void
    {
        $this->attributes['danhmuc'] = $value;
    }

    public function getTypeAttribute()
    {
        return $this->attributes['loai'] ?? null;
    }

    public function setTypeAttribute($value): void
    {
        $this->attributes['loai'] = $value;
    }

    public function getValueAttribute()
    {
        return $this->attributes['giatri'] ?? null;
    }

    public function setValueAttribute($value): void
    {
        $this->attributes['giatri'] = $value;
    }

    public function getStartDateAttribute()
    {
        return $this->attributes['ngaybatdau'] ?? null;
    }

    public function setStartDateAttribute($value): void
    {
        $this->attributes['ngaybatdau'] = $value;
    }

    public function getEndDateAttribute()
    {
        return $this->attributes['ngayketthuc'] ?? null;
    }

    public function setEndDateAttribute($value): void
    {
        $this->attributes['ngayketthuc'] = $value;
    }

    public function getStatusAttribute()
    {
        return $this->attributes['trangthai'] ?? null;
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['trangthai'] = $value;
    }

    public function getIsPublicAttribute()
    {
        return $this->attributes['congkhai'] ?? null;
    }

    public function setIsPublicAttribute($value): void
    {
        $this->attributes['congkhai'] = $value;
    }
}
