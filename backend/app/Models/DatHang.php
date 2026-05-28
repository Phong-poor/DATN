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
        'lydo',
        'refund_proof',
        'promotion_id',
        'giam_gia'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chi_tiets()
    {
        return $this->hasMany(DatHangChiTiet::class, 'id_dathang', 'id_dathang');
    }
}
