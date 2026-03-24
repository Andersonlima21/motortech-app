<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationId
{
    /**
     * Adiciona correlation ID (X-Request-Id) para rastreamento de requisições.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header('X-Request-Id', Str::uuid()->toString());

        $request->headers->set('X-Request-Id', $correlationId);

        $response = $next($request);

        $response->headers->set('X-Request-Id', $correlationId);

        return $response;
    }
}
