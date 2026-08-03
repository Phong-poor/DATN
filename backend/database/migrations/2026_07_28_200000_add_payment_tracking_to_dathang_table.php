<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('dathang', 'trang_thai_thanh_toan')) {
            Schema::table('dathang', function (Blueprint $table) {
                $table->string('trang_thai_thanh_toan', 20)
                    ->default('unpaid')
                    ->after('PTTT')
                    ->index();
            });
        }

        if (!Schema::hasColumn('dathang', 'thanh_toan_luc')) {
            Schema::table('dathang', function (Blueprint $table) {
                $table->timestamp('thanh_toan_luc')
                    ->nullable()
                    ->after('trang_thai_thanh_toan');
            });
        }
    }

    public function down(): void
    {
        // Giữ nguyên dữ liệu thanh toán khi rollback.
    }
};
