<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    /**
     * Get the user vouchers for the promotion.
     */
    public function userVouchers()
    {
        return $this->hasMany(UserVoucher::class, 'id_promotion');
    }

    protected $fillable = [
        'name',
        'category',
        'code',
        'type',
        'value',
        'start_date',
        'end_date',
        'status',
        'mota',
        'loai_dieu_kien',
        'dieu_kien',
    ];

    public $timestamps = false;
}