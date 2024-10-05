<?php

namespace App\Http\Middleware;

use App\Domain\Exceptions\UnAuthorizedException;
use App\Domain\JwtValidator;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\HttpFoundation\Response;

class Authentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $jwtValidator = new JwtValidator(
            config('app.name'),
            config('app.url'),
            InMemory::file(base_path('storage/jwt/rsa256.pub')),
        );
        try {
            $jwtValidator->validate($request->bearerToken());
        } catch (Exception $e) {
            throw new UnAuthorizedException($e->getMessage());
        }
        return $next($request);
    }
}
