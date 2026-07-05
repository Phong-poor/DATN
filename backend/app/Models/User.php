<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasApiTokens;

    protected $table = 'khachhang';

    protected $appends = ['online', 'name', 'role', 'avatar', 'last_active_at'];

    protected $fillable = [
        'ten',
        'name',
        'email',
        'sodienthoai',
        'phone',
        'ngaysinh',
        'date_of_birth',
        'gioitinh',
        'gender',
        'anhdaidien',
        'avatar',
        'matkhau',
        'password',
        'vaitro',
        'role',
        'id_facebook',
        'facebook_id',
        'trangthai',
        'status',
        'hoat_dong_cuoi_luc',
        'last_active_at',
        'xu',
    ];

    protected $hidden = [
        'matkhau',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'matkhau' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'hoat_dong_cuoi_luc' => 'datetime',
        ];
    }

    /**
     * Get the vouchers for the user.
     */
    public function vouchers()
    {
        return $this->hasMany(UserVoucher::class, 'id_user');
    }



    public function diaChis()
    {
        return $this->hasMany(DiaChi::class, 'id_user');
    }

    public function affiliateProfile()
    {
        return $this->hasOne(AffiliateProfile::class, 'id_khachhang');
    }

    public function affiliateReferrals()
    {
        return $this->hasMany(AffiliateReferral::class, 'id_affiliate_khachhang');
    }

    public function referredByAffiliate()
    {
        return $this->hasOne(AffiliateReferral::class, 'id_khachhang_duoc_gioithieu');
    }

    public function affiliateWithdrawRequests()
    {
        return $this->hasMany(AffiliateWithdrawRequest::class, 'id_affiliate_khachhang');
    }

    public function getOnlineAttribute(): bool
    {
        if (!$this->hoat_dong_cuoi_luc) {
            return false;
        }
        return $this->hoat_dong_cuoi_luc->diffInMinutes(now()) < 5;
    }

    public function getNameAttribute(): ?string
    {
        return $this->ten;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['ten'] = $value;
    }

    public function getPasswordAttribute(): ?string
    {
        return $this->matkhau;
    }

    public function setPasswordAttribute($value): void
    {
        $this->attributes['matkhau'] = $value;
    }

    public function getRoleAttribute(): ?string
    {
        return $this->vaitro;
    }

    public function setRoleAttribute($value): void
    {
        $this->attributes['vaitro'] = $value;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->trangthai;
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['trangthai'] = $value;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->sodienthoai;
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['sodienthoai'] = $value;
    }

    public function getDateOfBirthAttribute()
    {
        return $this->ngaysinh;
    }

    public function setDateOfBirthAttribute($value): void
    {
        $this->attributes['ngaysinh'] = $value;
    }

    public function getGenderAttribute(): ?string
    {
        return $this->gioitinh;
    }

    public function setGenderAttribute($value): void
    {
        $this->attributes['gioitinh'] = $value;
    }

    public function getFacebookIdAttribute(): ?string
    {
        return $this->id_facebook;
    }

    public function setFacebookIdAttribute($value): void
    {
        $this->attributes['id_facebook'] = $value;
    }

    public function getAvatarAttribute(): ?string
    {
        return $this->anhdaidien;
    }

    public function setAvatarAttribute($value): void
    {
        $this->attributes['anhdaidien'] = $value;
    }

    public function getLastActiveAtAttribute()
    {
        return $this->hoat_dong_cuoi_luc;
    }

    public function setLastActiveAtAttribute($value): void
    {
        $this->attributes['hoat_dong_cuoi_luc'] = $value;
    }

    public function getAuthPassword()
    {
        return $this->matkhau;
    }
}
