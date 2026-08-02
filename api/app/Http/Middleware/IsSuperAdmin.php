<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Sesi Habis: Sesi masuk tidak valid. Silakan masuk kembali.'], 401);
        }

        if (($user->role ?? null) !== 'super_admin' || ! (bool) ($user->is_active ?? true)) {
            return response()->json([
                'message' => 'Gagal: Tindakan gagal diproses. Hanya Super Admin yang dapat mengelola RSA Key.',
            ], 403);
        }

        return $next($request);
    }
}
