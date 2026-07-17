<?php

namespace App\Services;

use App\Models\AffiliateWithdrawal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsService
{
    public function sendWithdrawalSuccess(AffiliateWithdrawal $withdrawal): array
    {
        $message = $this->buildMessage($withdrawal);
        return $this->send($withdrawal->phone_account, $message, 'DEMO-' . $withdrawal->transaction_code, [
            'withdrawal_id' => $withdrawal->id,
        ]);
    }

    public function sendAffiliatePayoutDemo(string $phone, float $amount, string $transactionCode): array
    {
        $message = sprintf(
            'NEXTGEN: Yeu cau rut hoa hong demo %s VND da duoc xu ly. Ma GD: %s. Day la giao dich demo, khong chuyen tien ngan hang that.',
            number_format($amount, 0, '.', '.'),
            $transactionCode
        );

        return $this->send($phone, $message, 'DEMO-' . $transactionCode, [
            'transaction_code' => $transactionCode,
        ]);
    }

    private function send(string $phone, string $message, string $demoMessageId, array $context = []): array
    {
        $phone = $this->normalizePhone($phone);

        if (!config('services.sms.enabled')) {
            // Demo mode: khong goi nha cung cap SMS that. Muon gui ve SIM that thi bat SMS_ENABLED=true.
            Log::info('Demo SMS withdrawal notification', [
                ...$context,
                'phone_masked' => $this->maskPhone($phone),
                'message' => $message,
            ]);

            return [
                'success' => true,
                'message_id' => $demoMessageId,
                'error' => null,
            ];
        }

        try {
            $response = $this->provider() === 'speedsms'
                ? $this->sendSpeedSms($phone, $message)
                : Http::timeout(15)
                    ->retry(2, 300)
                    ->acceptJson()
                    ->asJson()
                    ->post((string) config('services.sms.endpoint'), $this->buildPayload($phone, $message));

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message_id' => null,
                    'error' => 'SMS HTTP ' . $response->status(),
                ];
            }

            return $this->parseResponse($response->json() ?: []);
        } catch (Throwable $e) {
            Log::warning('SMS send failed', [
                ...$context,
                'phone_masked' => $this->maskPhone($phone),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+|-|\./', '', trim($phone));
        if (str_starts_with($phone, '+84')) {
            return '0' . substr($phone, 3);
        }

        return $phone;
    }

    public function maskPhone(string $phone): string
    {
        $phone = $this->normalizePhone($phone);
        if (strlen($phone) <= 6) {
            return $phone;
        }

        return substr($phone, 0, 3) . str_repeat('*', max(3, strlen($phone) - 6)) . substr($phone, -3);
    }

    public function buildMessage(AffiliateWithdrawal $withdrawal): string
    {
        return sprintf(
            'NEXTGEN: Ban da rut demo thanh cong %s VND ve tai khoan %s. So du con lai %s VND. Ma GD: %s. Day la giao dich demo, khong chuyen tien that.',
            number_format((float) $withdrawal->amount, 0, '.', '.'),
            $this->maskPhone($withdrawal->phone_account),
            number_format((float) $withdrawal->balance_after, 0, '.', '.'),
            $withdrawal->transaction_code
        );
    }

    private function buildPayload(string $phone, string $message): array
    {
        // Dieu chinh field payload theo tai lieu nha cung cap SMS that nhu SpeedSMS/eSMS/Stringee.
        return [
            'to' => $this->normalizePhone($phone),
            'message' => $message,
            'brandname' => config('services.sms.brandname', 'NEXTGEN'),
            'api_key' => config('services.sms.api_key'),
            'secret_key' => config('services.sms.secret_key'),
        ];
    }

    private function sendSpeedSms(string $phone, string $message)
    {
        $endpoint = config('services.sms.endpoint') ?: 'https://api.speedsms.vn/index.php/sms/send';
        $accessToken = config('services.sms.api_key') ?: config('services.sms.secret_key');

        return Http::timeout(15)
            ->retry(2, 300)
            ->withBasicAuth((string) $accessToken, '')
            ->acceptJson()
            ->asJson()
            ->post($endpoint, $this->buildSpeedSmsPayload($phone, $message));
    }

    private function buildSpeedSmsPayload(string $phone, string $message): array
    {
        $payload = [
                'to' => [$this->toSpeedSmsPhone($phone)],
                'content' => $message,
                'sms_type' => (int) config('services.sms.type', 4),
        ];

        $smsType = (int) config('services.sms.type', 4);
        $sender = trim((string) config('services.sms.brandname', ''));
        if ($smsType === 2) {
            $payload['sender'] = '';
        } elseif ($sender !== '') {
            $payload['sender'] = $sender;
        }

        return $payload;
    }

    private function parseResponse(array $data): array
    {
        if ($this->provider() === 'speedsms') {
            $success = ($data['status'] ?? null) === 'success' && (string) ($data['code'] ?? '') === '00';

            return [
                'success' => $success,
                'message_id' => $data['data']['tranId'] ?? null,
                'error' => $success ? null : trim(sprintf(
                    '%s%s%s',
                    $data['message'] ?? ('SpeedSMS error ' . ($data['code'] ?? 'unknown')),
                    isset($data['code']) ? ' (code: ' . $data['code'] . ')' : '',
                    !empty($data['invalidPhone']) ? ' invalidPhone: ' . implode(',', (array) $data['invalidPhone']) : ''
                )),
            ];
        }

        // Dieu chinh mapping response theo provider SMS that.
        $success = (bool) ($data['success'] ?? ($data['status'] ?? false));

        return [
            'success' => $success,
            'message_id' => $data['message_id'] ?? $data['id'] ?? null,
            'error' => $success ? null : ($data['error'] ?? $data['message'] ?? 'SMS provider rejected request'),
        ];
    }

    private function provider(): string
    {
        return strtolower((string) config('services.sms.provider', 'demo'));
    }

    private function toSpeedSmsPhone(string $phone): string
    {
        $phone = $this->normalizePhone($phone);
        if (str_starts_with($phone, '0')) {
            return '84' . substr($phone, 1);
        }

        return $phone;
    }
}
