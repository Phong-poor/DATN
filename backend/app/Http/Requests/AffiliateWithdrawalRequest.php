<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AffiliateWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'phone_account' => ['required', 'string', 'regex:/^(\+84|0)(3|5|7|8|9)[0-9]{8}$/'],
            'account_name' => ['required', 'string', 'max:120'],
            'bank_name' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'integer', 'min:100000'],
            'idempotency_key' => ['required', 'string', 'max:80'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_account.regex' => 'So dien thoai phai dung dinh dang Viet Nam, vi du 0987654321 hoac +84987654321.',
            'amount.integer' => 'So tien rut phai la so nguyen.',
            'amount.min' => 'So tien rut toi thieu la 100.000d.',
            'idempotency_key.required' => 'Thieu ma chong tao giao dich trung.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');
        $phone = $this->input('phone_account');

        $this->merge([
            'amount' => is_string($amount) && preg_match('/^\d+$/', $amount) ? (int) $amount : $amount,
            'phone_account' => $this->normalizePhone((string) $phone),
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+|-|\./', '', trim($phone));
        if (str_starts_with($phone, '+84')) {
            return '0' . substr($phone, 3);
        }

        return $phone;
    }
}
