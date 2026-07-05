<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CauHinhXu extends Model
{
    protected $table = 'cauhinh_xu';

    protected $fillable = [
        'ti_le_quy_doi',
        'ti_le_tich_luy',
        'phan_tram_giam_toi_da',
        'trang_thai',
    ];

    protected $casts = [
        'trang_thai' => 'boolean',
        'ti_le_tich_luy' => 'float',
    ];
}
