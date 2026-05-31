<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DanhMucCha extends Model
{
    public $timestamps = false;
    protected $table = 'danhmuc_cha';
    protected $primaryKey = 'id_danhmuc_cha';

    protected $fillable = [
        'ten_danhmuc',
        'trangthai',
    ];

    /**
     * Danh sách các danh mục con thuộc danh mục cha này
     */
    public function children()
    {
        return $this->hasMany(DanhMuc::class, 'id_danhmuc_cha', 'id_danhmuc_cha');
    }

    /**
     * Lấy danh sách ID thuộc tính được kế thừa bởi danh mục cha này
     */
    public function getInheritedAttributeIds()
    {
        return DB::table('giatri_thuoctinh')
            ->whereJsonContains('danh_muc_ids', $this->id_danhmuc_cha)
            ->distinct()
            ->pluck('id_thuoctinh')
            ->toArray();
    }
}
