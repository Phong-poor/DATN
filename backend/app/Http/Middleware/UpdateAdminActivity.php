<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UpdateAdminActivity
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->role === 'admin') {
            // Performance optimization: Only update the database once every 1 minute instead of on every API call
            if (!$user->last_active_at || Carbon::parse($user->last_active_at)->diffInMinutes(now()) >= 1) {
                $user->last_active_at = now();
                $user->saveQuietly(); // Saves without triggering User model Eloquent events
            }
        }
        return $next($request);
    }
}
