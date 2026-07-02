<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('khachhang', function (Blueprint $table) {
            $table->id();

            $table->string('ten');
            $table->string('email')->unique();
            $table->string('sodienthoai')->nullable();

            $table->string('gioitinh', 10)->nullable();
            $table->date('ngaysinh')->nullable();

            $table->string('matkhau');

            $table->enum('vaitro', ['user', 'admin'])->default('user');
            $table->string('anhdaidien')->nullable();
            $table->string('id_facebook')->nullable();
            $table->enum('trangthai', ['active', 'locked'])->default('active');
            $table->timestamp('hoat_dong_cuoi_luc')->nullable();

            $table->string('otp_khoiphuc', 10)->nullable();
            $table->timestamp('otp_khoiphuc_hethan_luc')->nullable();

            $table->timestamp('email_verified_at')->nullable();

            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            $table->rememberToken();
            $table->string('api_token', 64)->nullable()->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('khachhang');
    }
};
