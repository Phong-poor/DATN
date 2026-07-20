<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateWithdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'bank_name',
        'phone_account',
        'account_name',
        'transaction_code',
        'idempotency_key',
        'status',
        'sms_status',
        'sms_message_id',
        'sms_error',
        'balance_before',
        'balance_after',
        'completed_at',
    ];

    protected $appends = [
        'masked_phone_account',
        'status_label',
        'sms_status_label',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getMaskedPhoneAccountAttribute(): string
    {
        $phone = (string) $this->phone_account;
        if (strlen($phone) <= 6) {
            return $phone;
        }

        return substr($phone, 0, 3) . str_repeat('*', max(3, strlen($phone) - 6)) . substr($phone, -3);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'success' => 'Thanh cong',
            'processing' => 'Dang xu ly',
            'failed' => 'That bai',
            default => 'Dang cho',
        };
    }

    public function getSmsStatusLabelAttribute(): string
    {
        return match ($this->sms_status) {
            'sent' => 'Da gui SMS',
            'failed' => 'Gui SMS that bai',
            default => 'Dang cho gui SMS',
        };
    }
}
