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

    protected $appends = ['online'];

    protected $fillable = [
        'ten',
        'email',
        'sodienthoai',
        'ngaysinh',
        'gioitinh',
        'anhdaidien',
        'matkhau',
        'vaitro',
        'id_facebook',
        'trangthai',
        'hoat_dong_cuoi_luc',
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

    public function getAuthPassword()
    {
        return $this->matkhau;
    }
}
