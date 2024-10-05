<?php
declare(strict_types=1);

namespace App\Domain;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;

class JwtPerser {

    public static function parse(string $token): UnencryptedToken
    {
        $parser = new Parser(new JoseEncoder());
        return $parser->parse($token);
    }
}

