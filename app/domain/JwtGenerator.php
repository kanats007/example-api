<?php
declare(strict_types=1);

namespace App\domain;

use DateTimeImmutable;
use Godruoyi\Snowflake\Sonyflake;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;

class JwtGenerator {

    public static function generateToken(string $privateKeyPath, string $publicKeyPath, string $sub, DateTimeImmutable $expiresAt): Token
    {
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::file($privateKeyPath),
            InMemory::file($publicKeyPath),
        );

        $now   = new DateTimeImmutable();

        return $config->builder()
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
            ->getToken($config->signer(), $config->signingKey());
    }
}

