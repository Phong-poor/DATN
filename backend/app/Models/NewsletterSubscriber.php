<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $table = 'news_subscribers';

    protected $fillable = [
        'email',
        'active',
        'token',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'active'          => 'boolean',
        'subscribed_at'   => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
