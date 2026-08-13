<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $table = 'noi_dung_tro_chuyen';

    protected $fillable = [
        'id_cuoc_tro_chuyen',
        'id_nguoigui',
        'noidung',
        'daxem',
        'duongdan_dinhkem',
        'ten_dinhkem',
    ];

    protected $appends = [
        'duongdan_dinhkem_url',
    ];

    public function getDuongdanDinhkemUrlAttribute()
    {
        if (!$this->duongdan_dinhkem) {
            return null;
        }

        $filename = basename(str_replace('\\', '/', $this->duongdan_dinhkem));
        $base = rtrim((string) config('app.url'), '/');

        return $base . '/api/chat/attachments/' . $filename;
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'id_cuoc_tro_chuyen');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'id_nguoigui');
    }
}
