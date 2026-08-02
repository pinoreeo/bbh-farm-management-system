<?php

namespace App\Http\Middleware;

use App\Support\BbhApiClient;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireBbhApiToken
{
    public function __construct(private readonly BbhApiClient $api) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = session('bbh_api_token');

        if (! is_string($token) || $token === '') {
            return redirect()->route('login');
        }

        try {
            $response = $this->api->get('auth/me', [], $token);
        } catch (ConnectionException) {
            session()->forget(['bbh_api_token', 'bbh_admin_user']);

            return redirect()->route('login')
                ->with('status', 'Sesi tidak dapat divalidasi karena layanan API tidak merespons. Silakan masuk kembali.');
        }

        if (! $response->successful()) {
            session()->forget(['bbh_api_token', 'bbh_admin_user']);

            return redirect()->route('login')
                ->with('status', 'Sesi Anda sudah berakhir. Silakan masuk kembali.');
        }

        session(['bbh_admin_user' => $response->json()]);

        return $next($request);
    }
}
