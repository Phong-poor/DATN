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
        Schema::create('bienthe_thuoctinh', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_bienthe');
            $table->unsignedBigInteger('id_giatri');

            $table->timestamps();

            $table->foreign('id_bienthe')
                ->references('id_bienthe')
                ->on('bienthe')
                ->onDelete('cascade');

            $table->foreign('id_giatri')
                ->references('id_giatri')
                ->on('giatri_thuoctinh')
                ->onDelete('cascade');

            $table->unique(['id_bienthe', 'id_giatri']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bienthe_thuoctinh');
    }
};
