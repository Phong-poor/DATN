<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CauHinhCaLam extends Model
{
    protected $table = 'cau_hinh_ca_lam';

    protected $fillable = [
        'ca_sang_bat_dau',
        'ca_sang_ket_thuc',
        'ca_chieu_bat_dau',
        'ca_chieu_ket_thuc',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'ca_sang_bat_dau' => '08:00',
            'ca_sang_ket_thuc' => '12:00',
            'ca_chieu_bat_dau' => '13:30',
            'ca_chieu_ket_thuc' => '17:30',
        ]);
    }

    public function toScheduleArray(): array
    {
        $morningStart = substr((string) $this->ca_sang_bat_dau, 0, 5);
        $morningEnd = substr((string) $this->ca_sang_ket_thuc, 0, 5);
        $afternoonStart = substr((string) $this->ca_chieu_bat_dau, 0, 5);
        $afternoonEnd = substr((string) $this->ca_chieu_ket_thuc, 0, 5);

        return [
            'morning_start' => $morningStart,
            'morning_end' => $morningEnd,
            'afternoon_start' => $afternoonStart,
            'afternoon_end' => $afternoonEnd,
            'morning' => "$morningStart - $morningEnd",
            'break' => "$morningEnd - $afternoonStart",
            'afternoon' => "$afternoonStart - $afternoonEnd",
        ];
    }
}
