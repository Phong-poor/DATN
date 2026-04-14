<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVoucher extends Model
{
    protected $table = 'users_voucher';

    protected $fillable = [
        'id_user',
        'id_promotion',
        'trang_thai',
        'ngay_nhan',
    ];

    public $timestamps = false; // Based on the screenshot, it doesn't look like it has created_at/updated_at

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Relationship with Promotion
     */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class, 'id_promotion');
    }
}
