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
        Schema::create('nhat_ky_admin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_khachhang')->constrained('admins')->cascadeOnDelete();
            $table->string('hanhdong')->nullable();
            $table->string('tenmodel')->nullable();
            $table->string('id_doituong')->nullable();
            $table->text('mota')->nullable();
            $table->string('diachi_ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhat_ky_admin');
    }
};
