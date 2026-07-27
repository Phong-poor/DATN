<?php

namespace Database\Seeders;

use App\Models\ChamCong;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class ChamCongMayToJuly2026Seeder extends Seeder
{
    public function run(): void
    {
        $employees = User::query()
            ->where('vaitro', '!=', 'user')
            ->where('trangthai', 'active')
            ->get(['id']);

        $period = CarbonPeriod::create('2026-05-01', '2026-07-27');

        foreach ($employees as $employee) {
            foreach ($period as $date) {
                if ($date->isWeekend()) {
                    continue;
                }

                $seed = ($employee->id * 31) + $date->dayOfYear;
                $lateMinutes = $seed % 7 === 0 ? 15 : ($seed % 11 === 0 ? 8 : 0);
                $checkIn = Carbon::createFromTime(8, $lateMinutes)->format('H:i:s');
                $checkOutMinutes = $seed % 9 === 0 ? 20 : 30;
                $checkOut = Carbon::createFromTime(17, $checkOutMinutes)->format('H:i:s');
                $workedMinutes = Carbon::createFromFormat('H:i:s', $checkIn)
                    ->diffInMinutes(Carbon::createFromFormat('H:i:s', $checkOut));

                ChamCong::firstOrCreate(
                    [
                        'id_nhanvien' => $employee->id,
                        'ngay_cham_cong' => $date->toDateString(),
                    ],
                    [
                        'gio_vao' => $checkIn,
                        'gio_ra' => $checkOut,
                        'di_tre_phut' => $lateMinutes,
                        'tong_gio' => round($workedMinutes / 60, 2),
                        'tong_cong' => 1.00,
                        'ghi_chu' => 'Dữ liệu ngày công tháng 5-7/2026',
                    ]
                );
            }
        }
    }
}
