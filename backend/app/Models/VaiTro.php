<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VaiTro extends Model
{
    use HasFactory;

    protected $table = 'vai_tro';
    protected $primaryKey = 'id_vaitro';

    protected $fillable = [
        'ten_vaitro',
        'ma_vaitro',
        'mo_ta',
        'quyen',
    ];

    protected $casts = [
        'quyen' => 'array',
    ];
}
