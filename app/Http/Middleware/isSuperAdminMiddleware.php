<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class isSuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->isSuperAdmin()) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Bạn không có quyền truy cập tính năng này.'
        ], Response::HTTP_FORBIDDEN);
    }
}