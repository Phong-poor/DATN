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
        Schema::table('dathang_chitiet', function (Blueprint $table) {
            if (! Schema::hasColumn('dathang_chitiet', 'hoantien')) {
                $table->boolean('hoantien')->default(0)->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dathang_chitiet', function (Blueprint $table) {
            if (Schema::hasColumn('dathang_chitiet', 'hoantien')) {
                $table->dropColumn('hoantien');
            }
        });
    }
};
