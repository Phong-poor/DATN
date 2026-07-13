<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_affiliate_khachhang')->constrained('khachhang')->onDelete('cascade');
            $table->unsignedBigInteger('id_sanpham')->nullable();
            $table->string('tieu_de');
            $table->text('mo_ta')->nullable();
            $table->string('video_path')->nullable();
            $table->string('video_url')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('trangthai')->default('pending');
            $table->boolean('noi_bat')->default(false);
            $table->unsignedInteger('luot_xem')->default(0);
            $table->unsignedInteger('luot_click')->default(0);
            $table->text('ly_do_tu_choi')->nullable();
            $table->timestamp('duoc_duyet_luc')->nullable();
            $table->timestamps();

            $table->index(['trangthai', 'noi_bat']);
            $table->index('id_sanpham');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_videos');
    }
};
