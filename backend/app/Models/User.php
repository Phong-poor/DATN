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

    protected $appends = ['online', 'name', 'role', 'avatar', 'last_active_at', 'cac_quyen', 'ten_vaitro_hienthi', 'phone', 'gender', 'date_of_birth', 'facebook_id'];

    protected $fillable = [
        'ten',
        'name',
        'email',
        'sodienthoai',
        'ngaysinh',
        'gioitinh',
        'anhdaidien',
        'matkhau',
        'vaitro',
        'id_facebook',
        'id_google',
        'trangthai',
        'hoat_dong_cuoi_luc',
        'last_active_at',
        'xu',
        'luot_quay',
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

    /**
     * Lịch sử giao dịch xu
     */
    public function xuHistories()
    {
        return $this->hasMany(XuHistory::class, 'id_khachhang');
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

    public function getCacQuyenAttribute()
    {
        if ($this->vaitro === 'user') {
            return [];
        }
        if ($this->vaitro === 'admin') {
            return [
                'san_pham_xem', 'san_pham_sua', 'nhap_xuat_kho', 
                'danh_muc_xem', 'danh_muc_sua', 
                'thuong_hieu_xem', 'thuong_hieu_sua', 
                'bien_the_xem', 'bien_the_sua', 
                'don_hang_xem', 'don_hang_sua', 'hoa_don_xem', 
                'marketing_quan_ly', 'affiliate_quan_ly', 
                'tin_tuc_quan_ly', 'binh_luan_quan_ly', 'banner_quan_ly', 
                'lien_he_quan_ly', 'tai_khoan_quan_ly', 'vai_tro_quan_ly', 'nhat_ky_quan_ly',
                'xu_quan_ly', 'vong_quay_quan_ly', 'diem_danh_quan_ly'
            ];
        }

        $role = VaiTro::where('ma_vaitro', $this->vaitro)->first();
        if ($role) {
            return is_array($role->quyen) ? $role->quyen : (json_decode($role->quyen, true) ?: []);
        }

        $defaults = [
            'inventory' => ['san_pham_xem', 'nhap_xuat_kho', 'danh_muc_xem', 'thuong_hieu_xem', 'bien_the_xem'],
            'order_manager' => ['don_hang_xem', 'don_hang_sua'],
            'marketing' => ['marketing_quan_ly'],
            'affiliate_manager' => ['affiliate_quan_ly'],
            'editor' => ['tin_tuc_quan_ly', 'binh_luan_quan_ly', 'banner_quan_ly'],
            'support' => ['lien_he_quan_ly'],
            'accountant' => ['don_hang_xem', 'hoa_don_xem'],
        ];

        return $defaults[strtolower($this->vaitro)] ?? [];
    }

    public function getTenVaitroHienthiAttribute()
    {
        if ($this->vaitro === 'user') {
            return 'Khách hàng';
        }
        if ($this->vaitro === 'admin') {
            return 'Quản trị viên';
        }

        $role = VaiTro::where('ma_vaitro', $this->vaitro)->first();
        if ($role) {
            return $role->ten_vaitro;
        }

        $defaults = [
            'inventory' => 'Thủ kho',
            'order_manager' => 'Xử lý đơn hàng',
            'marketing' => 'Marketing',
            'affiliate_manager' => 'Quản lý Affiliate',
            'editor' => 'Biên tập viên',
            'support' => 'Tư vấn viên',
            'accountant' => 'Kế toán',
        ];

        return $defaults[strtolower($this->vaitro)] ?? 'Nhân viên';
    }

    public function getAuthPassword()
    {
        return $this->matkhau;
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
}
