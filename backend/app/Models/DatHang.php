<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatHang extends Model
{
    protected $table = 'dathang';
    protected $primaryKey = 'id_dathang';

    protected $fillable = [
        'user_id',
        'tongtien',
        'trangthai',
        'diachi',
        'PTTT',
        'lydo',
        'refund_proof',
        'promotion_id',
        'giam_gia',
        'payment_provider',
        'payment_status',
        'payment_order_id',
        'payment_request_id',
        'payment_transaction_id',
        'payment_result_code',
        'payment_message',
        'payment_pay_type',
        'payment_paid_at',
        'payment_payload',
    ];

    protected $casts = [
        'payment_payload' => 'array',
        'payment_paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chi_tiets()
    {
        return $this->hasMany(DatHangChiTiet::class, 'id_dathang', 'id_dathang');
    }

    public function chiTiets()
    {
        return $this->chi_tiets();
    }
}
