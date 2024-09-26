<?php
declare(strict_types=1);

namespace App\domain;

use DateTimeImmutable;
use Godruoyi\Snowflake\Sonyflake;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\UnencryptedToken;

class JwtGenerator {

    public static function generateToken(string $keyPath, string $sub, DateTimeImmutable $expiresAt): UnencryptedToken
    {
        $tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));
        $algorithm    = new Sha256();
        $signingKey   = InMemory::plainText($keyPath);

        $now   = new DateTimeImmutable();

        return $tokenBuilder
            // Configures the issuer (iss claim)
            ->issuedBy(config('app.name'))
            // Configures the audience (aud claim)
            ->permittedFor(config('app.url'))
            // Configures the subject of the token (sub claim)
            ->relatedTo($sub)
            // Configures the id (jti claim)
            ->identifiedBy((new Sonyflake())->id())
            // Configures the time that the token was issue (iat claim)
            ->issuedAt($now)
            // Configures the time that the token can be used (nbf claim)
            ->canOnlyBeUsedAfter($now)
            // Configures the expiration time of the token (exp claim)
            ->expiresAt($expiresAt)
            // Builds a new token
            ->getToken($algorithm, $signingKey);
    }
}

