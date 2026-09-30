<?php

use App\Http\Middleware\RequireBbhApiToken;
use App\Http\Middleware\SetPublicLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'bbh.auth' => RequireBbhApiToken::class,
            'public.locale' => SetPublicLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (ConnectionException $exception, Request $request) {
            $message = 'Layanan data belum dapat diakses. Silakan coba lagi.';

            if ($request->isMethod('GET')) {
                return response($message, 503);
            }

            $redirect = back()->withInput($request->except([
                'password', 'password_confirmation', 'current_password', 'token',
            ]));

            return $request->is('aktifkan-akun/undangan')
                ? $redirect->withErrors(['email' => $message])
                : $redirect->with('adminApiStatus', $message);
        });
    })->create();
