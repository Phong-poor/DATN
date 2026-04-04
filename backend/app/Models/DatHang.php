<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatHang extends Model
{
    protected $table = 'dathang';
    protected $primaryKey = 'id_dathang';

    protected $fillable = [
        'user_id',
        'tongtien',
        'trangthai',
        'diachi',
        'PTTT',
        'ly_do_huy'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chiTiets()
    {
        return $this->hasMany(DatHangChiTiet::class, 'id_dathang', 'id_dathang');
    }
}
