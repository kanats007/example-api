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

        Log::debug($requestId . ':' . $request->method() . ':' . $request->fullUrl(), [
            'user' => $request->user()?->id,
            // 'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        $response = $next($request);

        if (get_class($response) === JsonResponse::class) {
            Log::debug($requestId . ':' . $request->method() . ':' . $request->fullUrl(), [
                'status' => $response->status(),
                // 'headers' => $response->headers->all(),
                'body' => $response->getData(true)
            ]);
        }

        return $response;
    }
}
