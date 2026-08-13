<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cai_dat_ma_sinh_nhat', function (Blueprint $table) {
            if (! Schema::hasColumn('cai_dat_ma_sinh_nhat', 'id_voucher')) {
                $table->integer('id_voucher')->nullable()->after('giochay');
                $table->foreign('id_voucher')->references('id')->on('vouchers')->onDelete('set null');
            }
        });

        Schema::table('nhat_ky_gui_ma_sinh_nhat', function (Blueprint $table) {
            if (! Schema::hasColumn('nhat_ky_gui_ma_sinh_nhat', 'id_khachhang_voucher')) {
                $table->integer('id_khachhang_voucher')->nullable()->after('id_voucher');
                $table->foreign('id_khachhang_voucher')->references('id')->on('khachhang_voucher')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nhat_ky_gui_ma_sinh_nhat', function (Blueprint $table) {
            if (Schema::hasColumn('nhat_ky_gui_ma_sinh_nhat', 'id_khachhang_voucher')) {
                $table->dropForeign(['id_khachhang_voucher']);
                $table->dropColumn('id_khachhang_voucher');
            }
        });
    }
};
