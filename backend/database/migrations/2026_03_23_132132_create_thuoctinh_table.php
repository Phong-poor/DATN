<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('thuoctinh', function (Blueprint $table) {
            $table->id('id_thuoctinh');
            $table->string('ten_thuoctinh');

            $table->unsignedBigInteger('id_nhom');

            $table->foreign('id_nhom')
                  ->references('id_nhom')
                  ->on('nhom_thuoctinh')
                  ->onDelete('cascade');

            $table->boolean('trangthai')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thuoctinh');
    }
};
