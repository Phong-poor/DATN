<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class EventCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['ten' => 'TETDUONGLICH', 'code' => 'TETDUONGLICH', 'date' => '01-01'],
            ['ten' => 'TETNGUYENDAN', 'code' => 'TETNGUYENDAN', 'date' => '17-02'],
            ['ten' => 'QUOCTEPHUNU', 'code' => 'QUOCTEPHUNU', 'date' => '08-03'],
            ['ten' => 'GIOTOHUNGVUONG', 'code' => 'GIOTOHUNGVUONG', 'date' => '26-04'],
            ['ten' => 'GIAIPHONGMIENNAM', 'code' => 'GIAIPHONGMIENNAM', 'date' => '30-04'],
            ['ten' => 'QUOCTELAODONG', 'code' => 'QUOCTELAODONG', 'date' => '01-05'],
            ['ten' => 'QUOCTETHIEUNHI', 'code' => 'QUOCTETHIEUNHI', 'date' => '01-06'],
            ['ten' => 'QUOCKHANH', 'code' => 'QUOCKHANH', 'date' => '02-09'],
            ['ten' => 'TETTRUNGTHU', 'code' => 'TETTRUNGTHU', 'date' => '25-09'],
            ['ten' => 'PHUNUVIETNAM', 'code' => 'PHUNUVIETNAM', 'date' => '20-10'],
            ['ten' => 'NHAGIAOVIETNAM', 'code' => 'NHAGIAOVIETNAM', 'date' => '20-11'],
            ['ten' => 'GIANGSINH', 'code' => 'GIANGSINH', 'date' => '25-12'],
        ];

        foreach ($holidays as $holiday) {
            $promotion = Promotion::where('danhmuc', 'event')
                ->where(fn ($query) => $query->where('code', $holiday['code'])->orWhere('ngay_su_kien', $holiday['date']))
                ->first() ?? new Promotion();
            $promotion->fill([
                    'ten' => $holiday['ten'],
                    'code' => $holiday['code'],
                    'ngay_su_kien' => $holiday['date'],
                    'danhmuc' => 'event',
                    'loai' => 'percent',
                    'giatri' => 10,
                    'ngaybatdau' => null,
                    'ngayketthuc' => null,
                    'trangthai' => 'open',
                    'mota' => 'Chiến dịch ngày lễ '.$holiday['ten'],
                    'loai_dieu_kien' => null,
                    'dieu_kien' => null,
                    'congkhai' => 1,
                    'dieu_kien_tang' => null,
                    'so_luong_phat' => null,
                ])->save();
        }

        $this->command?->info('Đã tạo/cập nhật '.count($holidays).' chiến dịch ngày lễ.');
    }
}
