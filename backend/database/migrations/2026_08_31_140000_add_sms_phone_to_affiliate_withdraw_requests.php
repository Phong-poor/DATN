<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_yeu_cau_rut_tien', function (Blueprint $table) {
            $table->string('so_dien_thoai_nhan_sms', 15)->nullable()->after('so_tai_khoan');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_yeu_cau_rut_tien', function (Blueprint $table) {
            $table->dropColumn('so_dien_thoai_nhan_sms');
        });
    }
};
