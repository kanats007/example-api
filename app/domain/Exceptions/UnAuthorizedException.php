<?php

namespace App\domain\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UnAuthorizedException extends Exception
{

    public function __construct(string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * 例外をHTTPレスポンスへレンダ
     * @see https://readouble.com/laravel/11.x/ja/errors.html#renderable-exceptions
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => 'unauthorized.'], Response::HTTP_UNAUTHORIZED, [], JSON_UNESCAPED_UNICODE);
    }
}