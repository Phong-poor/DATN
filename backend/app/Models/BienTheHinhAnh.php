<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BienTheHinhAnh extends Model
{
    protected $table      = 'bienthe_hinhanh';
    protected $primaryKey = 'id_bienthe_hinhanh';
    public $timestamps    = false;

    protected $fillable = [
        'id_sanpham',
        'duongdan',
        'thutu',
    ];

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'id_sanpham', 'id_sanpham');
    }
}