<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }

        /** @var User $user */
        $user = Auth::user();

        // Admin-only routes
        if ($request->routeIs('admin.tickets.*', 'admin.users.*')) {
            if (!$user->isAdmin()) {
                abort(403, 'Access denied.');
            }
        }

        // Main reports + SMS (not daily): admin and operator only
        if ($request->routeIs('admin.reports.index', 'admin.reports.csv', 'admin.reports.pdf', 'admin.reports.sms', 'admin.reports.sms.retry')) {
            if ($user->isCustomerCare()) {
                abort(403, 'Access denied.');
            }
        }

        return $next($request);
    }
}
