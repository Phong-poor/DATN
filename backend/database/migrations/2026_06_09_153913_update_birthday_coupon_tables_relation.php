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
        Schema::table('birthday_coupon_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('birthday_coupon_settings', 'promotion_id')) {
                $table->integer('promotion_id')->nullable()->after('run_time');
                $table->foreign('promotion_id')->references('id')->on('promotions')->onDelete('set null');
            }
        });

        Schema::table('birthday_coupon_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('birthday_coupon_logs', 'user_voucher_id')) {
                $table->integer('user_voucher_id')->nullable()->after('promotion_id');
                $table->foreign('user_voucher_id')->references('id')->on('users_voucher')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('birthday_coupon_settings', function (Blueprint $table) {
            $table->dropForeign(['promotion_id']);
            $table->dropColumn('promotion_id');
        });

        Schema::table('birthday_coupon_logs', function (Blueprint $table) {
            $table->dropForeign(['user_voucher_id']);
            $table->dropColumn('user_voucher_id');
        });
    }
};
