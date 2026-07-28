<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class CheckoutThrottle
{
    public function handle(Request $request, Closure $next): Response
    {
        $userKey = 'checkout:user:'.$request->user()->getAuthIdentifier();
        $ipKey = 'checkout:ip:'.$request->ip();
        $phone = preg_replace('/\D+/', '', (string) $request->input('phone'));
        $phoneKey = 'checkout:phone:'.hash('sha256', $phone);

        $limits = [[$userKey, 5, 600], [$ipKey, 12, 600]];
        if ($phone !== '') $limits[] = [$phoneKey, 5, 3600];

        foreach ($limits as [$key, $maxAttempts, $decaySeconds]) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $retryAfter = RateLimiter::availableIn($key);
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn thao tác đặt hàng quá nhiều lần. Vui lòng thử lại sau.',
                    'retry_after' => $retryAfter,
                ], 429, ['Retry-After' => $retryAfter]);
            }
        }

        foreach ($limits as [$key, , $decaySeconds]) RateLimiter::hit($key, $decaySeconds);
        return $next($request);
    }
}
