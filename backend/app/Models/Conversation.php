<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $table = 'cuoc_tro_chuyen';

    protected $fillable = [
        'id_khachhang',
        'tin_nhan_cuoi',
        'tin_nhan_cuoi_luc',
    ];

    protected $casts = [
        'tin_nhan_cuoi_luc' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_khachhang');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'id_cuoc_tro_chuyen');
    }
}
