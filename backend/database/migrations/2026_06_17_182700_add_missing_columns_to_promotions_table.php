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
        if (! Schema::hasTable('vouchers')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('vouchers', 'congkhai')) {
                $table->boolean('congkhai')->default(1);
            }

            if (! Schema::hasColumn('vouchers', 'dieu_kien_tang')) {
                $table->decimal('dieu_kien_tang', 15, 2)->default(0);
            }

            if (! Schema::hasColumn('vouchers', 'so_luong_phat')) {
                $table->integer('so_luong_phat')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // These columns are part of the Vietnamese vouchers schema, so rollback should not remove them.
    }
};
