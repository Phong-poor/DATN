<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LichSuQuay extends Model
{
    protected $table = 'lich_su_quay';

    protected $fillable = [
        'id_khachhang',
        'id_vongquay',
        'ten_qua',
        'loai_qua',
        'gia_tri_qua',
    ];

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_khachhang');
    }

    /**
     * Relationship with Wheel Slot
     */
    public function vongQuay(): BelongsTo
    {
        return $this->belongsTo(VongQuay::class, 'id_vongquay');
    }
}
