<?php
declare(strict_types=1);

namespace App\domain;

use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Validator;
use Symfony\Component\Clock\NativeClock;

class JwtValidator {

    private Validator $validator;
    private SignedWith $signedWith;
    private PermittedFor $permittedFor;
    private IssuedBy $issuedBy;
    private LooseValidAt $looseValidAt;

    public function __construct(
        string $iss,
        string $aud,
        string $publickeyPath,
    ) {
        $this->validator = new Validator();
        $this->signedWith = new SignedWith(
            new Sha256(),
            InMemory::file($publickeyPath),
        );
        $this->permittedFor = new PermittedFor($aud);
        $this->issuedBy = new IssuedBy($iss);
        $this->looseValidAt = new LooseValidAt((new NativeClock()));
    }

    public function validate(Token $token): void
    {
        $this->validator->assert($token, $this->signedWith);
        $this->validator->assert($token, $this->permittedFor); // aud
        $this->validator->assert($token, $this->issuedBy); // iss
        $this->validator->assert($token, $this->looseValidAt); // exp
    }
}

