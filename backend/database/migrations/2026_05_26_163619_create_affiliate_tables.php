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
        Schema::create('khach_hang_affiliate', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_khachhang')->constrained('khachhang')->onDelete('cascade');
            $table->string('ma_affiliate')->unique();
            $table->decimal('ty_le_hoa_hong', 5, 2)->default(0);
            $table->string('trangthai')->default('active');
            $table->decimal('tong_thu_nhap', 15, 2)->default(0);
            $table->decimal('tong_da_thanh_toan', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('affiliate_gioi_thieu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_affiliate_khachhang')->constrained('khachhang')->onDelete('cascade');
            $table->foreignId('id_khachhang_duoc_gioithieu')->constrained('khachhang')->onDelete('cascade');
            $table->string('ma_ref')->nullable();
            $table->timestamp('da_dang_ky_luc')->nullable();
            $table->timestamps();
        });

        Schema::create('affiliate_yeu_cau_rut_tien', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_affiliate_khachhang')->constrained('khachhang')->onDelete('cascade');
            $table->decimal('so_tien', 15, 2);
            $table->string('ten_ngan_hang')->nullable();
            $table->string('ten_chu_tai_khoan')->nullable();
            $table->string('so_tai_khoan')->nullable();
            $table->string('trangthai')->default('pending');
            $table->text('ghichu')->nullable();
            $table->timestamp('duoc_duyet_luc')->nullable();
            $table->timestamp('duoc_thanh_toan_luc')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_yeu_cau_rut_tien');
        Schema::dropIfExists('affiliate_gioi_thieu');
        Schema::dropIfExists('khach_hang_affiliate');
    }
};
