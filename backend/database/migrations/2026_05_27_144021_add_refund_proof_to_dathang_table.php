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
            $table->text('refund_proof')->nullable()->after('lydo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dathang', function (Blueprint $table) {
            $table->dropColumn('refund_proof');
        });
    }
};
