<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiaTriThuocTinh extends Model
{
    protected $table = 'giatri_thuoctinh';

    protected $primaryKey = 'id_giatri';

    public $timestamps = false;

    protected $fillable = [
        'id_thuoctinh',
        'giatri',
        'trangthai',
        'gia_cong_them',
        'danh_muc_ids',
    ];

    protected $casts = [
        'danh_muc_ids' => 'array',
    ];

    public function thuocTinh()
    {
        return $this->belongsTo(ThuocTinh::class, 'id_thuoctinh', 'id_thuoctinh');
    }

    public function bienThes()
    {
        return $this->belongsToMany(
            BienThe::class,
            'bienthe_thuoctinh',
            'id_giatri',
            'id_bienthe'
        );
    }
}
