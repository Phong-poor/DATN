<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThuocTinh extends Model
{
    protected $table      = 'thuoctinh';
    protected $primaryKey = 'id_thuoctinh';
    public $timestamps    = false;

    protected $fillable = [
        'ten_thuoctinh',
        'id_nhom',
        'trangthai',
    ];

    // 1 thuộc tính có nhiều giá trị
    public function giatriThuocTinhs()
    {
        return $this->hasMany(GiaTriThuocTinh::class, 'id_thuoctinh', 'id_thuoctinh');
    }

    // thuộc tính thuộc về nhóm
    public function nhomThuocTinh()
    {
        return $this->belongsTo(NhomThuocTinh::class, 'id_nhom', 'id_nhom');
    }
}