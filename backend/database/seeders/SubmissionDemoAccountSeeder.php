<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SubmissionDemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('NextGen@123');

        $accounts = [
            [
                'ten' => 'NextGen Admin Demo',
                'email' => 'admin.demo@nextgen.local',
                'sodienthoai' => '0900000001',
                'vaitro' => 'admin',
            ],
            [
                'ten' => 'Nhân viên Demo',
                'email' => 'nhanvien.demo@nextgen.local',
                'sodienthoai' => '0900000002',
                'vaitro' => 'accountant',
            ],
            [
                'ten' => 'Khách hàng Demo',
                'email' => 'khachhang.demo@nextgen.local',
                'sodienthoai' => '0900000003',
                'vaitro' => 'user',
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                $account + [
                    'matkhau' => $password,
                    'trangthai' => 'active',
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command?->info('Đã chuẩn bị 3 tài khoản demo. Mật khẩu: NextGen@123');
    }
}
