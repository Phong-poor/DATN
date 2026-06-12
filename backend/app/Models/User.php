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

    protected $appends = ['online'];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'avatar',
        'password',
        'role',
        'facebook_id',
        'status',
        'last_active_at',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_active_at' => 'datetime',
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
        return $this->hasOne(AffiliateProfile::class, 'user_id');
    }

    public function affiliateReferrals()
    {
        return $this->hasMany(AffiliateReferral::class, 'affiliate_user_id');
    }

    public function referredByAffiliate()
    {
        return $this->hasOne(AffiliateReferral::class, 'referred_user_id');
    }

    public function affiliateWithdrawRequests()
    {
        return $this->hasMany(AffiliateWithdrawRequest::class, 'affiliate_user_id');
    }

    public function getOnlineAttribute(): bool
    {
        if (!$this->last_active_at) {
            return false;
        }
        return $this->last_active_at->diffInMinutes(now()) < 5;
    }
}
