<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AffiliateVideo extends Model
{
    protected $table = 'affiliate_videos';

    protected $fillable = [
        'id_affiliate_khachhang',
        'id_sanpham',
        'tieu_de',
        'mo_ta',
        'video_path',
        'video_url',
        'thumbnail_path',
        'trangthai',
        'noi_bat',
        'luot_xem',
        'luot_click',
        'ly_do_tu_choi',
        'duoc_duyet_luc',
    ];

    protected $casts = [
        'noi_bat' => 'boolean',
        'duoc_duyet_luc' => 'datetime',
    ];

    protected $appends = [
        'affiliate_user_id',
        'product_id',
        'product_ids',
        'products',
        'title',
        'description',
        'status',
        'featured',
        'views',
        'clicks',
        'reject_reason',
        'approved_at',
        'video_src',
        'thumbnail_src',
    ];

    public function affiliateUser()
    {
        return $this->belongsTo(User::class, 'id_affiliate_khachhang');
    }

    public function product()
    {
        return $this->belongsTo(SanPham::class, 'id_sanpham', 'id_sanpham');
    }

    public function getAffiliateUserIdAttribute()
    {
        return $this->id_affiliate_khachhang;
    }

    public function getProductIdAttribute()
    {
        $ids = $this->product_ids;
        return $ids[0] ?? null;
    }

    public function getProductIdsAttribute()
    {
        if ($this->id_sanpham === null || $this->id_sanpham === '') {
            return [];
        }

        if (is_array($this->id_sanpham)) {
            return array_values(array_unique(array_map('intval', $this->id_sanpham)));
        }

        $decoded = json_decode((string) $this->id_sanpham, true);
        if (is_array($decoded)) {
            return array_values(array_unique(array_map('intval', $decoded)));
        }

        if (is_numeric($this->id_sanpham)) {
            return [(int) $this->id_sanpham];
        }

        return [];
    }

    public function getProductsAttribute()
    {
        $ids = $this->product_ids;
        if (empty($ids)) {
            return collect();
        }

        return \App\Models\SanPham::whereIn('id_sanpham', $ids)->get();
    }

    public function getTitleAttribute()
    {
        return $this->tieu_de;
    }

    public function getDescriptionAttribute()
    {
        return $this->mo_ta;
    }

    public function getStatusAttribute()
    {
        return $this->trangthai;
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['trangthai'] = $value;
    }

    public function getFeaturedAttribute()
    {
        return (bool) $this->noi_bat;
    }

    public function getViewsAttribute()
    {
        return (int) $this->luot_xem;
    }

    public function getClicksAttribute()
    {
        return (int) $this->luot_click;
    }

    public function getRejectReasonAttribute()
    {
        return $this->ly_do_tu_choi;
    }

    public function getApprovedAtAttribute()
    {
        return $this->duoc_duyet_luc;
    }

    public function getVideoSrcAttribute()
    {
        if ($this->video_url) {
            return $this->video_url;
        }

        return $this->video_path ? Storage::url($this->video_path) : null;
    }

    public function getThumbnailSrcAttribute()
    {
        return $this->thumbnail_path ? Storage::url($this->thumbnail_path) : null;
    }
}
