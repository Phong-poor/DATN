<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admins')) {
            return;
        }

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('ten');
            $table->string('email')->unique();
            $table->string('sodienthoai')->nullable();
            $table->string('gioitinh', 10)->nullable();
            $table->date('ngaysinh')->nullable();
            $table->string('matkhau');
            $table->string('vaitro')->default('admin');
            $table->text('anhdaidien')->nullable();
            $table->enum('trangthai', ['active', 'locked'])->default('active');
            $table->timestamp('hoat_dong_cuoi_luc')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('otp_khoiphuc', 10)->nullable();
            $table->timestamp('otp_khoiphuc_hethan_luc')->nullable();
            $table->string('id_google')->nullable();
            $table->string('id_facebook')->nullable();
            $table->string('so_cccd', 12)->nullable()->unique();
            $table->date('ngay_cap_cccd')->nullable();
            $table->string('noi_cap_cccd')->nullable();
            $table->string('anh_cccd_mat_truoc')->nullable();
            $table->string('anh_cccd_mat_sau')->nullable();
            $table->rememberToken();
            $table->longText('face_descriptor')->nullable();
            $table->boolean('face_registered')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
