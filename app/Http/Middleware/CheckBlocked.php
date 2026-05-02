<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckBlocked
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->is_blocked) {
            
            Auth::guard('web')->logout();
            
            // Delete tokens if using Sanctum
            if (method_exists(Auth::user(), 'tokens')) {
                Auth::user()->tokens()->delete();
            }

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Akun Anda telah diblokir. Silakan hubungi administrator.',
                    'reason' => Auth::user()->blocked_reason
                ], 403);
            }

            return redirect('/login')->withErrors(['email' => 'Akun Anda telah diblokir.']);
        }

        return $next($request);
    }
}
