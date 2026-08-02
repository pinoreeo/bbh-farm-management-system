<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Sesi Habis: Sesi masuk tidak valid. Silakan masuk kembali.'], 401);
        }

        $role = $user->role ?? null;
        if (! in_array($role, ['super_admin', 'admin'], true) || ! (bool) ($user->is_active ?? true)) {
            return response()->json(['message' => 'Gagal: Tindakan gagal diproses. Akun Anda tidak memiliki izin untuk mengakses layanan ini.'], 403);
        }

        return $next($request);
    }
}
