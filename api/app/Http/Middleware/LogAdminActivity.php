<?php

namespace App\Http\Middleware;

use App\Services\AdminActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActivity
{
    public function __construct(private readonly AdminActivityLogger $logger) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $statusCode = $response->getStatusCode();

        if ($this->logger->shouldLog($request, $statusCode)) {
            $this->logger->log($request, $statusCode, response: $response);
        }

        return $response;
    }
}
