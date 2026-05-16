<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * UpdateLastActive
 *
 * Updates the authenticated user's `last_active_at` timestamp on every
 * authenticated API request. Uses a 5-minute throttle to avoid hammering
 * the database on high-frequency requests (e.g., chat polling).
 */
class UpdateLastActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Throttle: only update once every 5 minutes per user
            $needsUpdate = is_null($user->last_active_at)
                || $user->last_active_at->diffInMinutes(now()) >= 5;

            if ($needsUpdate) {
                // Use DB update to avoid firing model events / Observer overhead
                $user->timestamps = false;
                $user->updateQuietly(['last_active_at' => now()]);
                $user->timestamps = true;
            }
        }

        return $next($request);
    }
}
