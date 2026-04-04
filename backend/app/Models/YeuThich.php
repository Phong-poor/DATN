<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YeuThich extends Model
{
    use HasFactory;

    protected $table = 'yeuthich';

    protected $fillable = [
        'user_id',
        'id_bienthe',
        'soluong',
    ];

    // Quan hệ lấy ra thông tin biến thể và sản phẩm tương ứng
    public function bienthe()
    {
        return $this->belongsTo(Bienthe::class, 'id_bienthe', 'id_bienthe')->with('sanpham');
    }
}