<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('don_xin_nghi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_nhanvien');
            $table->string('loai_nghi', 40);
            $table->string('thoi_luong', 20)->default('full_day');
            $table->date('tu_ngay');
            $table->date('den_ngay');
            $table->text('ly_do');
            $table->string('minh_chung')->nullable();
            $table->string('nguoi_ban_giao')->nullable();
            $table->text('ghi_chu_ban_giao')->nullable();
            $table->string('trang_thai', 24)->default('pending');
            $table->text('phan_hoi_quan_ly')->nullable();
            $table->unsignedBigInteger('xu_ly_boi')->nullable();
            $table->timestamp('xu_ly_luc')->nullable();
            $table->timestamps();

            $table->foreign('id_nhanvien')->references('id')->on('khachhang')->cascadeOnDelete();
            $table->foreign('xu_ly_boi')->references('id')->on('khachhang')->nullOnDelete();
            $table->index(['id_nhanvien', 'tu_ngay', 'den_ngay']);
            $table->index(['trang_thai', 'tu_ngay']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('don_xin_nghi');
    }
};
