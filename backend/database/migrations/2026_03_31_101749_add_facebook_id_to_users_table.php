<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Thêm dòng tạo facebook_id trước
            $table->string('facebook_id')->nullable();
            // Sau đó mới thêm avatar đứng sau facebook_id
            $table->string('avatar')->nullable()->after('facebook_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['facebook_id', 'avatar']);
        });
    }
};
