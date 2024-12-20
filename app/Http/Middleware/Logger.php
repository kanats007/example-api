<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class Logger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = uniqid('req-', false);

        Log::debug('BEGIN ' . $requestId . ' ' . $request->method() . ':' . $request->path(), [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        $response = $next($request);

        if (get_class($response) === JsonResponse::class) {
            /** @var JsonResponse $response */
            Log::debug('END   ' . $requestId . ' ' . $request->method() . ':' . $request->path(), [
                'status' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
                'body' => $response->getData(true)
            ]);
        } else {
            Log::debug('END   ' . $requestId . ' ' . $request->method() . ':' . $request->path(), [
                'status' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
                'body' => $response->getContent()
            ]);
        }

        return $response;
    }
}
