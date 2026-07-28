<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsRevision extends Model
{
    protected $fillable = ['news_id', 'version', 'editor', 'note', 'snapshot'];

    protected $casts = ['snapshot' => 'array'];
}
