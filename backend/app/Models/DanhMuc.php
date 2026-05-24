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
        'parent_id'
    ];

    /**
     * Relationship: Danh mục cha
     */
    public function parent()
    {
        return $this->belongsTo(DanhMuc::class, 'parent_id', 'id_danhmuc');
    }

    /**
     * Relationship: Danh mục con
     */
    public function children()
    {
        return $this->hasMany(DanhMuc::class, 'parent_id', 'id_danhmuc');
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
        if ($this->parent_id) {
            $idsFromParent = $this->parent->getInheritedAttributeIds();
        }

        return array_merge($idsFromSelf, $idsFromParent);
    }
}
