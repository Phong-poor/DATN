<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    use HasFactory;

    protected $table = 'nhat_ky_admin';

    protected $appends = [
        'action',
        'model_name',
        'description',
        'ip_address',
    ];

    protected $fillable = [
        'id_khachhang',
        'hanhdong',
        'tenmodel',
        'id_doituong',
        'mota',
        'diachi_ip',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(Admin::class, 'id_khachhang');
    }

    public function getActionAttribute()
    {
        return $this->hanhdong;
    }

    public function getModelNameAttribute()
    {
        return $this->tenmodel;
    }

    public function getDescriptionAttribute()
    {
        return $this->mota;
    }

    public function getIpAddressAttribute()
    {
        return $this->diachi_ip;
    }
}
