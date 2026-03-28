<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $lang = $request->header('Accept-Language');
        
        // Cek jika header bahasa dikirim dan didukung (id atau en)
        if ($lang && in_array($lang, ['id', 'en'])) {
            App::setLocale($lang);
        } else {
            // Default bahasa jika tidak ada header
            App::setLocale(config('app.locale', 'id'));
        }

        return $next($request);
    }
}
