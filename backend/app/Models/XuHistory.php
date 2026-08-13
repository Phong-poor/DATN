<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XuHistory extends Model
{
    use HasFactory;

    protected $table = 'lich_su_xu';
    protected $primaryKey = 'id_lichsu';

    protected $fillable = [
        'id_khachhang',
        'so_xu',
        'loai_giao_dich',
        'id_dathang',
        'mo_ta',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_khachhang');
    }

    public function order()
    {
        return $this->belongsTo(DatHang::class, 'id_dathang');
    }
}
