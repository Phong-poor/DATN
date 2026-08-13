<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cau_hinh_ca_lam', function (Blueprint $table) {
            $table->id();
            $table->time('ca_sang_bat_dau')->default('08:00:00');
            $table->time('ca_sang_ket_thuc')->default('12:00:00');
            $table->time('ca_chieu_bat_dau')->default('13:30:00');
            $table->time('ca_chieu_ket_thuc')->default('17:30:00');
            $table->timestamps();
        });

        DB::table('cau_hinh_ca_lam')->insert([
            'ca_sang_bat_dau' => '08:00:00',
            'ca_sang_ket_thuc' => '12:00:00',
            'ca_chieu_bat_dau' => '13:30:00',
            'ca_chieu_ket_thuc' => '17:30:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cau_hinh_ca_lam');
    }
};
