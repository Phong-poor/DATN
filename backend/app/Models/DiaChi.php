<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiaChi extends Model
{
    use SoftDeletes;

    protected $table = 'diachi';

    protected $primaryKey = 'id_diachi';

    protected $fillable = [
        'id_user',
        'tinh_thanhpho',
        'quan_huyen',
        'phuong_xa',
        'diachi_cuthe',
        'latitude',
        'longitude',
        'loai_diachi',
        'mac_dinh',
    ];

    protected $appends = [
        'dia_chi_day_du'
    ];

    protected $casts = [
        'mac_dinh' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function getDiaChiDayDuAttribute(): string
    {
        return collect([
            $this->diachi_cuthe,
            $this->phuong_xa,
            $this->quan_huyen,
            $this->tinh_thanhpho,
        ])->filter(fn ($value) => $value && $value !== 'Không xác định')->implode(', ');
    }
}
