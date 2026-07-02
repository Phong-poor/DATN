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
        if (! Schema::hasTable('banners')) {
            return;
        }

        Schema::table('banners', function (Blueprint $table) {
            if (! Schema::hasColumn('banners', 'description')) {
                $table->text('description')->nullable()->after('subtitle');
            }

            if (! Schema::hasColumn('banners', 'highlight')) {
                $table->string('highlight')->nullable()->after('title');
            }

            if (! Schema::hasColumn('banners', 'device_image')) {
                $table->string('device_image')->nullable()->after('mobile_image');
            }

            if (! Schema::hasColumn('banners', 'button_primary_text')) {
                $table->string('button_primary_text')->nullable()->default('Mua ngay')->after('link_url');
            }

            if (! Schema::hasColumn('banners', 'button_secondary_text')) {
                $table->string('button_secondary_text')->nullable()->default('Xem bộ sưu tập')->after('button_primary_text');
            }

            if (! Schema::hasColumn('banners', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('button_secondary_text');
            }
        });

        if (Schema::hasTable('sanpham') && Schema::hasColumn('banners', 'product_id')) {
            Schema::table('banners', function (Blueprint $table) {
                $table->foreign('product_id')
                    ->references('id_sanpham')
                    ->on('sanpham')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('banners')) {
            return;
        }

        Schema::table('banners', function (Blueprint $table) {
            if (Schema::hasColumn('banners', 'product_id')) {
                $table->dropForeign(['product_id']);
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('banners', 'description') ? 'description' : null,
                Schema::hasColumn('banners', 'highlight') ? 'highlight' : null,
                Schema::hasColumn('banners', 'device_image') ? 'device_image' : null,
                Schema::hasColumn('banners', 'button_primary_text') ? 'button_primary_text' : null,
                Schema::hasColumn('banners', 'button_secondary_text') ? 'button_secondary_text' : null,
                Schema::hasColumn('banners', 'product_id') ? 'product_id' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
