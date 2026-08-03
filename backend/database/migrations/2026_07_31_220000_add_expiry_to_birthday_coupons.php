<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cai_dat_ma_sinh_nhat') && ! Schema::hasColumn('cai_dat_ma_sinh_nhat', 'thoi_han_ngay')) {
            Schema::table('cai_dat_ma_sinh_nhat', function (Blueprint $table) {
                $table->unsignedSmallInteger('thoi_han_ngay')->default(30)->after('giochay');
            });
        }

        if (Schema::hasTable('khachhang_voucher')) {
            Schema::table('khachhang_voucher', function (Blueprint $table) {
                if (! Schema::hasColumn('khachhang_voucher', 'het_han_luc')) {
                    $table->dateTime('het_han_luc')->nullable()->after('ngay_nhan')->index();
                }
                if (! Schema::hasColumn('khachhang_voucher', 'da_su_dung_luc')) {
                    $table->dateTime('da_su_dung_luc')->nullable()->after('het_han_luc');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('khachhang_voucher')) {
            Schema::table('khachhang_voucher', function (Blueprint $table) {
                if (Schema::hasColumn('khachhang_voucher', 'het_han_luc')) {
                    $table->dropIndex(['het_han_luc']);
                    $table->dropColumn('het_han_luc');
                }
                if (Schema::hasColumn('khachhang_voucher', 'da_su_dung_luc')) {
                    $table->dropColumn('da_su_dung_luc');
                }
            });
        }

        if (Schema::hasTable('cai_dat_ma_sinh_nhat') && Schema::hasColumn('cai_dat_ma_sinh_nhat', 'thoi_han_ngay')) {
            Schema::table('cai_dat_ma_sinh_nhat', function (Blueprint $table) {
                $table->dropColumn('thoi_han_ngay');
            });
        }
    }
};
