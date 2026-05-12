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
        Schema::create('dathang', function (Blueprint $table) {
            $table->id('id_dathang');

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->decimal('tongtien', 12, 2);
            $table->string('trangthai')->default('pending');
            $table->string('diachi')->nullable();
            $table->string('PTTT')->nullable(); // phương thức thanh toán

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dathang');
    }
};
