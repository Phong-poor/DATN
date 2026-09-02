<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vai_tro')) {
            return;
        }

        $names = [
            'admin' => 'Quản trị viên',
            'inventory' => 'Thủ kho',
            'order_manager' => 'Xử lý đơn hàng',
            'marketing' => 'Marketing',
            'affiliate_manager' => 'Quản lý Affiliate',
            'editor' => 'Biên tập viên',
            'support' => 'Tư vấn viên',
            'accountant' => 'Kế toán',
            'coin_and_minigame_manager' => 'Quản lý Xu & Minigame',
            'cskh' => 'CSKH',
        ];

        foreach ($names as $code => $name) {
            DB::table('vai_tro')->where('ma_vaitro', $code)->update([
                'ten_vaitro' => $name,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Không khôi phục chuỗi sai mã hóa vì sẽ làm giao diện xuất hiện ký tự "?" trở lại.
    }
};
