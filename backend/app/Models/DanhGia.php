<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Lưu đánh giá sản phẩm, trạng thái kiểm duyệt và phản hồi của quản trị viên.
 */
class DanhGia extends Model
{
    use HasFactory;

    protected $table = 'danhgia';

    protected $primaryKey = 'id_danhgia';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_dathang',
        'id_bienthe',
        'user_id',
        'danhgia',
        'binhluan',
        'trangthai',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bienThe()
    {
        return $this->belongsTo(BienThe::class, 'id_bienthe');
    }

    public function datHang()
    {
        return $this->belongsTo(DatHang::class, 'id_dathang');
    }
}
