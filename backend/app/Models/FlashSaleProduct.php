<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSaleProduct extends Model
{
    protected $table = 'flash_sale_products';
    protected $primaryKey = 'id_flash_sale_product';

    protected $fillable = [
        'session_id',
        'id_bienthe',
        'gia_flash_sale',
        'so_luong_gioi_han',
        'so_luong_da_ban',
    ];

    protected $casts = [
        'gia_flash_sale' => 'float',
        'so_luong_gioi_han' => 'integer',
        'so_luong_da_ban' => 'integer',
    ];

    public function session()
    {
        return $this->belongsTo(FlashSaleSession::class, 'session_id', 'id_session');
    }

    public function bienThe()
    {
        return $this->belongsTo(BienThe::class, 'id_bienthe', 'id_bienthe');
    }
}
