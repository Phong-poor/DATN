<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GioHang extends Model
{
    protected $table = 'giohang';
    protected $primaryKey = 'id_giohang';
    public $timestamps = true;

    protected $fillable = [
        'id_khachhang',
        'id_bienthe',
        'soluong',
        'id_combo',
        'id_nhom_combo',
    ];

    // Quan hệ với biến thể
    public function bienThe()
    {
        return $this->belongsTo(BienThe::class, 'id_bienthe', 'id_bienthe');
    }

    // Quan hệ với user
    public function user()
    {
        return $this->belongsTo(User::class, 'id_khachhang', 'id');
    }

    // Quan hệ với combo
    public function combo()
    {
        return $this->belongsTo(Combo::class, 'id_combo', 'id_combo');
    }
}