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
        Schema::table('banners', function (Blueprint $table) {
            $table->text('description')->nullable()->after('subtitle');
            $table->string('highlight')->nullable()->after('title');
            $table->string('device_image')->nullable()->after('mobile_image');
            $table->string('button_primary_text')->nullable()->default('Mua ngay')->after('link_url');
            $table->string('button_secondary_text')->nullable()->default('Xem bộ sưu tập')->after('button_primary_text');
            $table->unsignedBigInteger('product_id')->nullable()->after('button_secondary_text');

            $table->foreign('product_id')
                  ->references('id_sanpham')
                  ->on('sanpham')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn([
                'description',
                'highlight',
                'device_image',
                'button_primary_text',
                'button_secondary_text',
                'product_id',
            ]);
        });
    }
};
