<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatHangChiTiet extends Model
{
    protected $table = 'dathang_chitiet';

    protected $fillable = [
        'id_dathang',
        'id_bienthe',
        'soluong',
        'gia'
    ];

    public function datHang()
    {
        return $this->belongsTo(DatHang::class, 'id_dathang', 'id_dathang');
    }

    public function bienThe()
    {
        return $this->belongsTo(BienThe::class, 'id_bienthe', 'id_bienthe');
    }
}
