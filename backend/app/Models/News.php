<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = 'tintuc';

    protected $fillable = [
        'tieude',
        'slug',
        'tomtat',
        'noidung',
        'danhmuc',
        'tacgia',
        'hinhanh',
        'mota_hinhanh',
        'trangthai',
        'dang_luc',
        'luotxem',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'canonical_url',
        'no_index',
        'noi_bat',
        'ghim',
        'workflow_status',
        'reading_time',
        'share_count',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'dang_luc' => 'datetime',
        'reviewed_at' => 'datetime',
        'no_index' => 'boolean',
        'noi_bat' => 'boolean',
        'ghim' => 'boolean',
    ];

    protected $with = ['tags'];

    public function tags()
    {
        return $this->belongsToMany(NewsTag::class, 'news_tag', 'news_id', 'tag_id');
    }

    public function revisions()
    {
        return $this->hasMany(NewsRevision::class)->latest('version');
    }
}
