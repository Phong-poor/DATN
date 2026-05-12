<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GioHang extends Model
{
    protected $table = 'giohang';
    protected $primaryKey = 'id_giohang';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'id_bienthe',
        'soluong',
    ];

    // Quan hệ với biến thể
    public function bienThe()
    {
        return $this->belongsTo(BienThe::class, 'id_bienthe', 'id_bienthe');
    }

    // Quan hệ với user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}