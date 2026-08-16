<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Lưu giờ vào, giờ ra, công làm việc và ảnh minh chứng của nhân viên theo ngày.
 */
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
        'trang_thai',
        'ly_do_dieu_chinh',
        'dieu_chinh_boi',
        'dieu_chinh_luc',
    ];

    protected $casts = [
        'ngay_cham_cong' => 'date:Y-m-d',
        'dieu_chinh_luc' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(Admin::class, 'id_nhanvien');
    }

    public function nguoiDieuChinh()
    {
        return $this->belongsTo(Admin::class, 'dieu_chinh_boi');
    }
}
