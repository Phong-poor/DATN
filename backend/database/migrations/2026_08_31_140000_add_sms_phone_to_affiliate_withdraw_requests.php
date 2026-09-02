<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('affiliate_yeu_cau_rut_tien')
            || Schema::hasColumn('affiliate_yeu_cau_rut_tien', 'so_dien_thoai_nhan_sms')) {
            return;
        }

        Schema::table('affiliate_yeu_cau_rut_tien', function (Blueprint $table): void {
            $table->string('so_dien_thoai_nhan_sms', 15)->nullable()->after('so_tai_khoan');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('affiliate_yeu_cau_rut_tien')
            || ! Schema::hasColumn('affiliate_yeu_cau_rut_tien', 'so_dien_thoai_nhan_sms')) {
            return;
        }

        Schema::table('affiliate_yeu_cau_rut_tien', function (Blueprint $table): void {
            $table->dropColumn('so_dien_thoai_nhan_sms');
        });
    }
};
