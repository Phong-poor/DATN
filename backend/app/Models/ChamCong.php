<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChamCong extends Model
{
    use HasFactory;

    protected $table = 'cham_cong';

    protected $fillable = [
        'id_nhanvien',
        'ngay_cham_cong',
        'gio_vao',
        'anh_vao',
        'gio_ra',
        'anh_ra',
        'di_tre_phut',
        'tong_gio',
        'tong_cong',
        'ghi_chu',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_nhanvien');
    }
}
