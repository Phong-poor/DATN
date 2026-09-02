<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lich_lam_nhan_vien', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_nhanvien')->unique();
            $table->enum('loai_ca', ['full_day', 'morning', 'afternoon'])->default('full_day');
            $table->date('ngay_bat_dau');
            $table->date('ngay_ket_thuc')->nullable();
            $table->json('thu_lam_viec');
            $table->timestamps();
            $table->foreign('id_nhanvien')->references('id')->on('admins')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lich_lam_nhan_vien');
    }
};
