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
        Schema::create('cham_cong', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_nhanvien');
            $table->date('ngay_cham_cong')->index();
            $table->time('gio_vao')->nullable();
            $table->string('anh_vao')->nullable();
            $table->time('gio_ra')->nullable();
            $table->string('anh_ra')->nullable();
            $table->integer('di_tre_phut')->default(0);
            $table->decimal('tong_gio', 4, 2)->default(0.00);
            $table->decimal('tong_cong', 3, 2)->default(0.00);
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->foreign('id_nhanvien')->references('id')->on('admins')->onDelete('cascade');
            $table->unique(['id_nhanvien', 'ngay_cham_cong']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cham_cong');
    }
};
