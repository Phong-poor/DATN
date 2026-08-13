<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsRevision extends Model
{
    protected $fillable = ['news_id', 'version', 'editor', 'user_id', 'title', 'summary', 'content', 'note', 'snapshot'];

    protected $casts = ['snapshot' => 'array'];
}
