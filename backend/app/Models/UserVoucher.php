<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVoucher extends Model
{
    protected $table = 'khachhang_voucher';

    protected $fillable = [
        'id_user',
        'id_voucher',
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
     * Relationship with Voucher (formerly Promotion)
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Promotion::class, 'id_voucher');
    }

    /**
     * Relationship with Promotion (legacy support)
     */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class, 'id_voucher');
    }
}
