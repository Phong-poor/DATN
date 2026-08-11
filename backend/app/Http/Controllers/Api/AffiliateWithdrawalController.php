<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AffiliateWithdrawalRequest;
use App\Http\Resources\AffiliateWithdrawalResource;
use App\Models\AffiliateWallet;
use App\Models\AffiliateWithdrawal;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AffiliateWithdrawalController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['data' => []]);
            }

            $rows = AffiliateWithdrawal::where('user_id', $user->id)
                ->latest()
                ->paginate((int) $request->input('per_page', 10));

            return AffiliateWithdrawalResource::collection($rows);
        } catch (\Throwable $e) {
            return response()->json(['data' => []]);
        }
    }

    public function store(AffiliateWithdrawalRequest $request, SmsService $smsService)
    {
        try {
            $user = $request->user();
            $validated = $request->validated();
            $created = false;

            $withdrawal = DB::transaction(function () use ($user, $validated, &$created) {
                $wallet = AffiliateWallet::where('user_id', $user->id)->lockForUpdate()->first();
                if (!$wallet) {
                    $wallet = AffiliateWallet::create([
                        'user_id' => $user->id,
                        'balance' => 0,
                        'pending_balance' => 0,
                        'total_withdrawn' => 0,
                    ]);
                    $wallet = AffiliateWallet::where('user_id', $user->id)->lockForUpdate()->first();
                }

                $existing = AffiliateWithdrawal::where('user_id', $user->id)
                    ->where('idempotency_key', $validated['idempotency_key'])
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $amount = (int) $validated['amount'];
                if ($amount > (float) $wallet->balance) {
                    abort(response()->json([
                        'message' => 'Số dư ví affiliate không đủ để rút.',
                        'available_balance' => (float) $wallet->balance,
                    ], 422));
                }

                $balanceBefore = (float) $wallet->balance;
                $balanceAfter = $balanceBefore - $amount;

                $wallet->balance = $balanceAfter;
                $wallet->total_withdrawn = (float) $wallet->total_withdrawn + $amount;
                $wallet->save();

                $created = true;

                return AffiliateWithdrawal::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'bank_name' => $validated['bank_name'],
                    'phone_account' => $validated['phone_account'],
                    'account_name' => $validated['account_name'],
                    'transaction_code' => $this->makeTransactionCode(),
                    'idempotency_key' => $validated['idempotency_key'],
                    'status' => 'success',
                    'sms_status' => 'pending',
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'completed_at' => now(),
                ]);
            });

            if ($created) {
                $this->sendSmsWithoutRollback($withdrawal, $smsService);
            }

            return response()->json([
                'message' => $created ? 'Rút tiền demo thành công' : 'Giao dịch đã được xử lý trước đó',
                'demo_notice' => 'Không phát sinh chuyển khoản ngân hàng thật',
                'remaining_balance' => (float) $withdrawal->fresh()->balance_after,
                'withdrawal' => new AffiliateWithdrawalResource($withdrawal->fresh()),
            ], $created ? 201 : 200);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Không thể thực hiện rút tiền.'], 500);
        }
    }

    private function sendSmsWithoutRollback(AffiliateWithdrawal $withdrawal, SmsService $smsService): void
    {
        try {
            $result = $smsService->sendWithdrawalSuccess($withdrawal);

            $withdrawal->sms_status = $result['success'] ? 'sent' : 'failed';
            $withdrawal->sms_message_id = $result['message_id'] ?? null;
            $withdrawal->sms_error = $result['success'] ? null : Str::limit((string) ($result['error'] ?? 'SMS failed'), 1000);
            $withdrawal->save();

            if (!$result['success']) {
                Log::warning('Affiliate demo withdrawal SMS failed', [
                    'withdrawal_id' => $withdrawal->id,
                    'error' => $withdrawal->sms_error,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Affiliate demo withdrawal SMS exception', [
                'withdrawal_id' => $withdrawal->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function makeTransactionCode(): string
    {
        do {
            $code = 'WD-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
        } while (AffiliateWithdrawal::where('transaction_code', $code)->exists());

        return $code;
    }
}
