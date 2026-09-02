<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateWithdrawRequest extends Model
{
    protected $table = 'affiliate_yeu_cau_rut_tien';

    protected $appends = [
        'affiliate_user_id',
        'amount',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'sms_phone',
        'status',
        'note',
        'approved_at',
        'paid_at',
        'request_code',
        'payout_provider',
        'transaction_id',
        'processing_at',
    ];

    protected $fillable = [
        'id_affiliate_khachhang',
        'so_tien',
        'ten_ngan_hang',
        'ten_chu_tai_khoan',
        'so_tai_khoan',
        'so_dien_thoai_nhan_sms',
        'trangthai',
        'ghichu',
        'duoc_duyet_luc',
        'duoc_thanh_toan_luc',
        'ma_yeu_cau',
        'nha_cung_cap',
        'ma_giao_dich',
        'du_lieu_chi_tra',
        'bat_dau_xu_ly_luc',
    ];

    protected $casts = [
        'duoc_duyet_luc' => 'datetime',
        'duoc_thanh_toan_luc' => 'datetime',
        'du_lieu_chi_tra' => 'array',
        'bat_dau_xu_ly_luc' => 'datetime',
    ];

    public function affiliateUser()
    {
        return $this->belongsTo(User::class, 'id_affiliate_khachhang');
    }

    public function getAffiliateUserIdAttribute()
    {
        return $this->id_affiliate_khachhang;
    }

    public function getAmountAttribute()
    {
        return $this->so_tien;
    }

    public function getBankNameAttribute()
    {
        return $this->ten_ngan_hang;
    }

    public function getBankAccountNameAttribute()
    {
        return $this->ten_chu_tai_khoan;
    }

    public function getBankAccountNumberAttribute()
    {
        return $this->so_tai_khoan;
    }

    public function getSmsPhoneAttribute()
    {
        return $this->so_dien_thoai_nhan_sms;
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

    public function getRequestCodeAttribute() { return $this->ma_yeu_cau; }
    public function getPayoutProviderAttribute() { return $this->nha_cung_cap; }
    public function getTransactionIdAttribute() { return $this->ma_giao_dich; }
    public function getProcessingAtAttribute() { return $this->bat_dau_xu_ly_luc; }
}
