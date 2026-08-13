<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComboSanPham extends Model
{
    protected $table = 'combo_sanpham';

    protected $fillable = [
        'id_combo',
        'id_sanpham',
    ];
}
