<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NhomThuocTinh extends Model
{
    protected $table      = 'nhom_thuoctinh';
    protected $primaryKey = 'id_nhom';
    public $timestamps    = false;

    protected $fillable = [
        'ten_nhom',
        'trangthai',
    ];

    // 1 nhóm có nhiều thuộc tính
    public function thuocTinhs()
    {
        return $this->hasMany(ThuocTinh::class, 'id_nhom', 'id_nhom');
    }
}