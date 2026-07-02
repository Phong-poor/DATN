<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DanhMuc extends Model
{
    public $timestamps = false;

    protected $table = 'danhmuc';

    protected $primaryKey = 'id_danhmuc';

    protected $fillable =
        [
            'ten_danhmuc',
            'trangthai',
            'id_danhmuc_cha',
        ];

    /**
     * Relationship: Danh mục cha
     */
    public function danhMucCha()
    {
        return $this->belongsTo(DanhMucCha::class, 'id_danhmuc_cha', 'id_danhmuc_cha');
    }

    /**
     * Get all inherited attribute groups from parent and self
     */
    public function getInheritedAttributeIds()
    {
        $idsFromSelf = \DB::table('giatri_thuoctinh')
            ->whereJsonContains('danh_muc_ids', $this->id_danhmuc)
            ->distinct()
            ->pluck('id_thuoctinh')
            ->toArray();

        $idsFromParent = [];
        if ($this->id_danhmuc_cha) {
            $idsFromParent = $this->danhMucCha->getInheritedAttributeIds();
        }

        return array_merge($idsFromSelf, $idsFromParent);
    }
}
