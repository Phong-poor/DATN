<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lienhe')) {
            return;
        }

        Schema::create('lienhe', function (Blueprint $table) {
            $table->id();
            $table->string('hoten', 100);
            $table->string('email', 100);
            $table->string('sodienthoai', 20)->nullable();
            $table->text('noidung');
            $table->string('danhmuc', 100)->nullable();
            $table->string('trangthai', 30)->default('new')->index();
            $table->text('phanhoi')->nullable();
            $table->timestamp('phan_hoi_luc')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Giữ nguyên bảng để rollback không làm mất dữ liệu liên hệ hiện có.
    }
};
