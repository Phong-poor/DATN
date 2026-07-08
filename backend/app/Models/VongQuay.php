<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VongQuay extends Model
{
    protected $table = 'vong_quays';

    protected $fillable = [
        'ten',
        'ti_le',
        'loai',
        'giatri',
        'id_voucher',
        'mau_sac',
        'mau_chu',
    ];

    /**
     * Relationship with Voucher (Promotion)
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Promotion::class, 'id_voucher');
    }
}
