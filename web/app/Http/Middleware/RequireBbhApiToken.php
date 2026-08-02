<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireBbhApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! is_string(session('bbh_api_token')) || session('bbh_api_token') === '') {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
