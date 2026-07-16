<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CauHinhDiemDanh extends Model
{
    use HasFactory;

    protected $table = 'cauhinh_diem_danh';

    protected $fillable = [
        'thu_tu',
        'ten_ngay',
        'so_xu_thuong',
    ];
}
