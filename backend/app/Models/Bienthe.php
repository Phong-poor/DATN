<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\GiaTriThuocTinh;

class BienThe extends Model
{
    protected $table      = 'bienthe';
    protected $primaryKey = 'id_bienthe';
    public $timestamps    = false;

    protected $fillable = [
        'id_sanpham',
        'ten_bienthe',
        'gia',
        'soluong',
        'thuoc_tinh_json',
    ];

    public function hinhAnhs()
    {
        return $this->hasMany(BienTheHinhAnh::class, 'id_bienthe', 'id_bienthe');
    }

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'id_sanpham', 'id_sanpham');
    }

    public function giaTriThuocTinhs()
    {
        return $this->belongsToMany(
            GiaTriThuocTinh::class,
            'bienthe_thuoctinh',
            'id_bienthe',
            'id_giatri'
        );
    }
}