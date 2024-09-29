<?php

namespace App\domain\Exceptions;

use Exception;
use Illuminate\Http\Request;

class JwtException extends Exception
{

    public function __construct(string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * 例外をHTTPレスポンスへレンダ
     */
    public function render(Request $request): void
    {
        // ...
    }
}