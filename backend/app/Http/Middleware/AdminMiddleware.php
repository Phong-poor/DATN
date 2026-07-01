<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Chưa đăng nhập',
            ], 401);
        }

        if ($user->trangthai === 'locked') {
            return response()->json([
                'message' => 'Tài khoản của bạn đã bị khóa.',
                'code' => 'ACCOUNT_LOCKED',
            ], 423);
        }

        if ($user->vaitro === 'user') {
            return response()->json([
                'message' => 'Bạn không có quyền vào trang admin',
                'code' => 'ADMIN_ACCESS_REVOKED',
            ], 403);
        }

        return $next($request);
    }
}
