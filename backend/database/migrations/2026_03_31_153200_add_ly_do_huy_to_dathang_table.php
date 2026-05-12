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
        Schema::table('dathang', function (Blueprint $table) {
            $table->text('ly_do_huy')->nullable()->after('PTTT');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dathang', function (Blueprint $table) {
            $table->dropColumn('ly_do_huy');
        });
    }
};
