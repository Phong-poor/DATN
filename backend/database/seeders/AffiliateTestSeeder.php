<?php

namespace Database\Seeders;

use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateReferral;
use App\Models\AffiliateWithdrawRequest;
use App\Models\DatHang;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AffiliateTestSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $emails = collect(range(1, 10))
                ->map(fn ($index) => sprintf('affiliate.test%02d@nextgen.test', $index));

            $publisherIds = User::whereIn('email', $emails)->pluck('id');

            if ($publisherIds->isNotEmpty()) {
                AffiliateCommission::whereIn('affiliate_user_id', $publisherIds)->delete();
                AffiliateWithdrawRequest::whereIn('affiliate_user_id', $publisherIds)->delete();
                AffiliateReferral::whereIn('affiliate_user_id', $publisherIds)->delete();
                AffiliateProfile::whereIn('user_id', $publisherIds)->delete();
            }

            $orders = DatHang::query()
                ->select('id_dathang', 'id_khachhang', 'tongtien')
                ->orderByDesc('id_dathang')
                ->limit(30)
                ->get();

            $fallbackCustomers = User::whereNotIn('email', $emails)
                ->where('role', '!=', 'admin')
                ->pluck('id')
                ->values();

            $profileStatuses = ['active', 'active', 'active', 'pending', 'active', 'suspended', 'active', 'pending', 'active', 'rejected'];
            $commissionStatuses = ['pending', 'approved', 'paid', 'cancelled'];
            $withdrawStatuses = ['pending', 'approved', 'paid', 'rejected'];

            foreach (range(1, 10) as $index) {
                $user = User::updateOrCreate(
                    ['email' => sprintf('affiliate.test%02d@nextgen.test', $index)],
                    [
                        'name' => sprintf('Publisher Test %02d', $index),
                        'phone' => sprintf('090%07d', 8800000 + $index),
                        'role' => 'user',
                        'status' => 'active',
                        'password' => Hash::make('12345678'),
                        'email_verified_at' => now(),
                        'last_active_at' => now()->subMinutes($index * 3),
                    ]
                );

                $earned = 0;
                $paid = 0;
                $code = sprintf('NGP%04d', $index);

                $profile = AffiliateProfile::create([
                    'user_id' => $user->id,
                    'affiliate_code' => $code,
                    'commission_rate' => 4 + ($index % 5),
                    'status' => $profileStatuses[$index - 1],
                    'total_earned' => 0,
                    'total_paid' => 0,
                ]);

                for ($ref = 1; $ref <= 2; $ref++) {
                    $order = $orders->get((($index - 1) * 2 + $ref - 1) % max(1, $orders->count()));
                    $customerId = $order?->id_khachhang ?: $fallbackCustomers->get((($index - 1) * 2 + $ref - 1) % max(1, $fallbackCustomers->count()));

                    if ($customerId && (int) $customerId !== (int) $user->id) {
                        AffiliateReferral::create([
                            'affiliate_user_id' => $user->id,
                            'referred_user_id' => $customerId,
                            'ref_code' => $code,
                            'registered_at' => now()->subDays($index + $ref),
                        ]);
                    }

                    $amount = round(((float) ($order?->tongtien ?? (12000000 + $index * 1500000))) * ($profile->commission_rate / 100));
                    $status = $commissionStatuses[($index + $ref) % count($commissionStatuses)];

                    AffiliateCommission::create([
                        'affiliate_user_id' => $user->id,
                        'referred_user_id' => $customerId,
                        'order_id' => $order?->id_dathang,
                        'amount' => $amount,
                        'status' => $status,
                        'approved_at' => in_array($status, ['approved', 'paid'], true) ? now()->subDays($ref) : null,
                        'paid_at' => $status === 'paid' ? now()->subHours($index) : null,
                        'note' => 'Du lieu test affiliate admin',
                        'created_at' => now()->subDays($index + $ref),
                        'updated_at' => now()->subDays($ref),
                    ]);

                    if (in_array($status, ['approved', 'paid'], true)) {
                        $earned += $amount;
                    }

                    if ($status === 'paid') {
                        $paid += $amount;
                    }
                }

                $withdrawAmount = max(10000, min($earned ?: 250000, 500000 + $index * 75000));
                AffiliateWithdrawRequest::create([
                    'affiliate_user_id' => $user->id,
                    'amount' => $withdrawAmount,
                    'bank_name' => ['Vietcombank', 'Techcombank', 'MB Bank', 'ACB'][$index % 4],
                    'bank_account_name' => strtoupper($user->name),
                    'bank_account_number' => sprintf('9704%08d', 550000 + $index),
                    'status' => $withdrawStatuses[$index % count($withdrawStatuses)],
                    'note' => 'Yeu cau rut tien test',
                    'approved_at' => in_array($withdrawStatuses[$index % count($withdrawStatuses)], ['approved', 'paid'], true) ? now()->subDays(1) : null,
                    'paid_at' => $withdrawStatuses[$index % count($withdrawStatuses)] === 'paid' ? now() : null,
                ]);

                $profile->update([
                    'total_earned' => $earned,
                    'total_paid' => $paid,
                ]);
            }
        });
    }
}
