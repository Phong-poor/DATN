<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nhat_ky_gui_ma_sinh_nhat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_khachhang')->constrained('khachhang')->onDelete('cascade');
            $table->integer('id_voucher')->nullable();
            $table->foreign('id_voucher')->references('id')->on('vouchers')->onDelete('set null');
            $table->string('mavoucher');
            $table->string('email');
            $table->date('ngaysinh');
            $table->timestamp('guiluc')->nullable();
            $table->string('trangthai')->default('pending');
            $table->text('thongbaoloi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nhat_ky_gui_ma_sinh_nhat');
    }
};
