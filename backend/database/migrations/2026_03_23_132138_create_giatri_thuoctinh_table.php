<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('giatri_thuoctinh', function (Blueprint $table) {
            $table->id('id_giatri');

            $table->unsignedBigInteger('id_thuoctinh');
            $table->string('giatri'); // 16GB, i5, RTX 3060...

            $table->foreign('id_thuoctinh')
                  ->references('id_thuoctinh')
                  ->on('thuoctinh')
                  ->onDelete('cascade');

            $table->boolean('trangthai')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giatri_thuoctinh');
    }
};
