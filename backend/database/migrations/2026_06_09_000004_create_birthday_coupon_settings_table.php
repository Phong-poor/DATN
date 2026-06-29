<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('birthday_coupon_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->string('run_time')->default('08:30');
            $table->string('promotion_code')->nullable(); // e.g. HAPPYBDAY100
            $table->string('email_template_id')->nullable()->default('tpl-bday-default');
            $table->boolean('send_once_per_year')->default(true);
            $table->boolean('retry_if_failed')->default(true);
            $table->boolean('notify_admin')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('birthday_coupon_settings');
    }
};
