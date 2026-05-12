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
        Schema::create('giohang', function (Blueprint $table) {
            $table->id('id_giohang');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->unsignedBigInteger('id_bienthe');

            $table->integer('soluong')->default(1);

            $table->timestamps();

            $table->foreign('id_bienthe')
                ->references('id_bienthe')
                ->on('bienthe')
                ->onDelete('cascade');

            $table->unique(['user_id', 'id_bienthe']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('giohang');
    }
};
