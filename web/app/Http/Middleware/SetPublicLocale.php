<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetPublicLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $segment = (string) $request->route('locale');
        $locales = config('public.locales', []);
        abort_unless(is_array($locales) && array_key_exists($segment, $locales), 404);

        App::setLocale((string) ($locales[$segment]['app_locale'] ?? config('app.locale')));
        URL::defaults(['locale' => $segment]);

        return $next($request);
    }
}
