<?php

namespace App\Http\Controllers;

use App\Events\OrderPlaced;
use App\Models\DatHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SepayController extends Controller
{
    public function webhook(Request $request): JsonResponse
    {
        $configuredKey = (string) config('services.sepay.webhook_api_key');
        $providedKey = preg_replace('/^Apikey\s+/i', '', trim((string) $request->header('Authorization')));
        if ($configuredKey === '' || ! hash_equals($configuredKey, (string) $providedKey)) {
            Log::warning('SePay webhook rejected: invalid API key');
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $payload = $request->validate([
            'id' => 'required', 'gateway' => 'required|string', 'transactionDate' => 'nullable|string',
            'accountNumber' => 'required|string', 'code' => 'nullable|string', 'content' => 'nullable|string',
            'transferType' => 'required|string|in:in,out', 'transferAmount' => 'required|numeric|min:0',
            'referenceCode' => 'nullable|string', 'description' => 'nullable|string',
        ]);

        if ($payload['transferType'] !== 'in') {
            return response()->json(['success' => true, 'message' => 'Ignored outgoing transaction']);
        }

        $expectedAccount = preg_replace('/\s+/', '', (string) config('services.sepay.account_number'));
        $receivedAccount = preg_replace('/\s+/', '', (string) $payload['accountNumber']);
        if ($expectedAccount === '' || ! hash_equals($expectedAccount, $receivedAccount)) {
            return response()->json(['success' => false, 'message' => 'Bank account does not match'], 422);
        }

        $prefix = preg_quote((string) config('services.sepay.payment_prefix', 'DH'), '/');
        $paymentText = trim(($payload['code'] ?? '').' '.($payload['content'] ?? ''));
        if (! preg_match('/(?:^|\s)'.$prefix.'(\d+)(?:\s|$)/i', $paymentText, $matches)) {
            return response()->json(['success' => true, 'message' => 'No matching order code']);
        }

        $orderId = (int) $matches[1];
        $transactionId = (string) $payload['id'];
        $becamePaid = false;
        DB::transaction(function () use ($orderId, $transactionId, $payload, &$becamePaid) {
            $order = DatHang::where('id_dathang', $orderId)->lockForUpdate()->first();
            if (! $order || $order->nha_cung_cap_thanh_toan !== 'sepay') return;

            $paymentData = $order->du_lieu_thanh_toan ?: [];
            if ((string) data_get($paymentData, 'sepay.transaction_id') === $transactionId || $order->trang_thai_thanh_toan === 'paid') return;
            if ((float) $payload['transferAmount'] < (float) $order->tongtien) {
                Log::warning('SePay payment amount is insufficient', ['order_id' => $orderId, 'transaction_id' => $transactionId]);
                return;
            }

            $paymentData['sepay'] = [
                'transaction_id' => $transactionId, 'reference_code' => $payload['referenceCode'] ?? null,
                'gateway' => $payload['gateway'], 'account_number' => $payload['accountNumber'],
                'amount' => (float) $payload['transferAmount'], 'content' => $payload['content'] ?? null,
                'paid_at' => $payload['transactionDate'] ?? now()->toDateTimeString(),
            ];
            $paymentData['status_history']['paid'] = now()->toDateTimeString();
            $order->update(['trang_thai_thanh_toan' => 'paid', 'du_lieu_thanh_toan' => $paymentData]);
            $becamePaid = true;
        });

        if ($becamePaid && ($order = DatHang::find($orderId))) broadcast(new OrderPlaced($order));
        return response()->json(['success' => true]);
    }

    public function status(Request $request, int $id): JsonResponse
    {
        $order = DatHang::where('id_dathang', $id)
            ->where('id_khachhang', $request->user()->getAuthIdentifier())
            ->firstOrFail();
        abort_unless($order->nha_cung_cap_thanh_toan === 'sepay', 404);
        return response()->json(['success' => true, 'order_id' => $id, 'payment_status' => $order->trang_thai_thanh_toan, 'amount' => (float) $order->tongtien]);
    }
}
