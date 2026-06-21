<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class UpdateAdminActivity
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('sanctum')->user() ?: $request->user();
        if ($user) {
            // Performance optimization: Only update the database once every 1 minute instead of on every API call
            if (!$user->hoat_dong_cuoi_luc || Carbon::parse($user->hoat_dong_cuoi_luc)->diffInMinutes(now()) >= 1) {
                $user->hoat_dong_cuoi_luc = now();
                $user->saveQuietly(); // Saves without triggering User model Eloquent events
            }
        }
        return $next($request);
    }
}
