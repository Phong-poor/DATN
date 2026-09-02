<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vai_tro')) {
            return;
        }

        Schema::create('vai_tro', function (Blueprint $table) {
            $table->id('id_vaitro');
            $table->string('ten_vaitro');
            $table->string('ma_vaitro')->unique();
            $table->text('mo_ta')->nullable();
            $table->json('quyen')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vai_tro');
    }
};
