<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('khachhang')->cascadeOnDelete();
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('pending_balance', 15, 2)->default(0);
            $table->decimal('total_withdrawn', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('affiliate_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('khachhang')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('bank_name', 120);
            $table->string('phone_account', 20);
            $table->string('account_name', 120);
            $table->string('transaction_code', 40)->unique();
            $table->string('idempotency_key', 80)->nullable();
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending');
            $table->enum('sms_status', ['pending', 'sent', 'failed'])->default('pending');
            $table->string('sms_message_id')->nullable();
            $table->text('sms_error')->nullable();
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index('sms_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_withdrawals');
        Schema::dropIfExists('affiliate_wallets');
    }
};
