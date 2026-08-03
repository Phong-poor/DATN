<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lienhe', function (Blueprint $table) {
            $table->string('loai_yeu_cau', 40)->nullable()->index();
            $table->unsignedBigInteger('showroom_id')->nullable();
            $table->string('showroom_ten', 150)->nullable();
            $table->string('showroom_diachi', 255)->nullable();
            $table->date('ngay_hen')->nullable()->index();
            $table->string('khung_gio', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lienhe', function (Blueprint $table) {
            $table->dropIndex(['loai_yeu_cau']);
            $table->dropIndex(['ngay_hen']);
            $table->dropColumn([
                'loai_yeu_cau',
                'showroom_id',
                'showroom_ten',
                'showroom_diachi',
                'ngay_hen',
                'khung_gio',
            ]);
        });
    }
};
