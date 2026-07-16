<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class SalesClosedMiddleware
{
    const CUTOFF = '2026-07-17 23:45:00';
    const TZ     = 'Asia/Dhaka';

    public function handle(Request $request, Closure $next)
    {
        if (Carbon::now(self::TZ)->lt(Carbon::parse(self::CUTOFF, self::TZ))) {
            return $next($request);
        }

        $allowed = [
            'my-ticket',
            'ticket/download',
            'admin',
            'clear-cache',
            'sms-notify',
            'api/blink-notify',
            'callback',
        ];

        $path = ltrim($request->getPathInfo(), '/');

        foreach ($allowed as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/') || str_starts_with($path, $prefix . '?')) {
                return $next($request);
            }
        }

        return redirect('/my-ticket');
    }
}
