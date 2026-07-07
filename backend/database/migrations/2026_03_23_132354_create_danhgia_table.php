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
        if (Schema::hasTable('danhgia')) {
            return;
        }

        Schema::create('danhgia', function (Blueprint $table) {
            $table->id('id_danhgia');
            $table->unsignedBigInteger('id_dathang');
            $table->unsignedBigInteger('id_bienthe');
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('danhgia');
            $table->text('binhluan')->nullable();
            $table->string('trangthai')->default('pending');
            $table->timestamp('created_at')->nullable();

            $table->unique(['id_dathang', 'id_bienthe', 'user_id']);

            $table->foreign('id_dathang')
                ->references('id_dathang')
                ->on('dathang')
                ->onDelete('cascade');

            $table->foreign('id_bienthe')
                ->references('id_bienthe')
                ->on('bienthe')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('khachhang')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('danhgia');
    }
};
