<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yeuthich', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_khachhang')->constrained('khachhang')->onDelete('cascade');
            $table->unsignedBigInteger('id_bienthe');
            // Khoá ngoại liên kết với bảng bienthe
            $table->foreign('id_bienthe')->references('id_bienthe')->on('bienthe')->onDelete('cascade');
            $table->integer('soluong')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yeuthich');
    }
};
