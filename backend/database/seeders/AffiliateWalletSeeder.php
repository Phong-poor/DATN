<?php

namespace Database\Seeders;

use App\Models\AffiliateWallet;
use App\Models\AffiliateWithdrawal;
use App\Models\User;
use Illuminate\Database\Seeder;

class AffiliateWalletSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->orderBy('id')->first();
        if (!$user) {
            $this->command?->warn('Khong co user nao de tao vi affiliate demo.');
            return;
        }

        AffiliateWallet::updateOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 2500000,
                'pending_balance' => 300000,
                'total_withdrawn' => 1000000,
            ]
        );

        $samples = [
            ['amount' => 500000, 'status' => 'success', 'sms_status' => 'sent', 'before' => 3500000, 'after' => 3000000],
            ['amount' => 300000, 'status' => 'success', 'sms_status' => 'sent', 'before' => 3000000, 'after' => 2700000],
            ['amount' => 200000, 'status' => 'failed', 'sms_status' => 'failed', 'before' => 2700000, 'after' => 2700000],
        ];

        foreach ($samples as $index => $sample) {
            AffiliateWithdrawal::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'transaction_code' => 'WD-DEMO-' . ($index + 1),
                ],
                [
                    'amount' => $sample['amount'],
                    'bank_name' => 'Vietcombank',
                    'phone_account' => $user->sodienthoai ?: '0987654321',
                    'account_name' => strtoupper($user->ten ?: 'NEXTGEN DEMO'),
                    'idempotency_key' => 'seed-demo-' . ($index + 1),
                    'status' => $sample['status'],
                    'sms_status' => $sample['sms_status'],
                    'sms_message_id' => $sample['sms_status'] === 'sent' ? 'DEMO-SEED-' . ($index + 1) : null,
                    'sms_error' => $sample['sms_status'] === 'failed' ? 'Demo failed transaction' : null,
                    'balance_before' => $sample['before'],
                    'balance_after' => $sample['after'],
                    'completed_at' => now()->subDays(3 - $index),
                ]
            );
        }
    }
}
