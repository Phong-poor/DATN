<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'eyebrow',
        'highlight',
        'description',
        'image',
        'media_type',
        'mobile_image',
        'mobile_media_type',
        'link_url',
        'product_id',
        'primary_label',
        'secondary_label',
        'product_badge',
        'product_feature',
        'position',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}
