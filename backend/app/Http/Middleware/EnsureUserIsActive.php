<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Akun dinonaktifkan. Silakan hubungi administrator.',
                ], Response::HTTP_FORBIDDEN);
            }

            auth()->guard('web')->logout();

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Akun dinonaktifkan. Silakan hubungi administrator.',
            ]);
        }

        return $next($request);
    }
}
