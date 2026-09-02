<?php

return [
    'default_commission_rate' => (float) env('AFFILIATE_DEFAULT_COMMISSION_RATE', 5),
    'max_commission_rate' => (float) env('AFFILIATE_MAX_COMMISSION_RATE', 30),
    'minimum_withdrawal' => (float) env('AFFILIATE_MINIMUM_WITHDRAWAL', 100000),
    'payout_provider' => env('AFFILIATE_PAYOUT_PROVIDER', 'mock'),
];
