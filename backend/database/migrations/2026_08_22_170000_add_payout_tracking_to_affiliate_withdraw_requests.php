<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_yeu_cau_rut_tien', function (Blueprint $table) {
            $table->string('ma_yeu_cau', 40)->nullable()->unique()->after('id');
            $table->string('nha_cung_cap', 30)->nullable()->after('trangthai');
            $table->string('ma_giao_dich', 120)->nullable()->index()->after('nha_cung_cap');
            $table->json('du_lieu_chi_tra')->nullable()->after('ma_giao_dich');
            $table->timestamp('bat_dau_xu_ly_luc')->nullable()->after('du_lieu_chi_tra');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_yeu_cau_rut_tien', function (Blueprint $table) {
            $table->dropUnique(['ma_yeu_cau']);
            $table->dropIndex(['ma_giao_dich']);
            $table->dropColumn(['ma_yeu_cau', 'nha_cung_cap', 'ma_giao_dich', 'du_lieu_chi_tra', 'bat_dau_xu_ly_luc']);
        });
    }
};
