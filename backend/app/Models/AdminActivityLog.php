<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    use HasFactory;

    protected $table = 'nhat_ky_admin';

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
        return $this->belongsTo(User::class, 'id_khachhang');
    }
}
