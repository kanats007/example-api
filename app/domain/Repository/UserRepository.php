<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Models\User;

interface UserRepository
{
    public function findByOidcUserId(string $sub): ?User;

    public function findByUserId(string $userId): ?User;

    public function create(
        string $sub,
        string $name = null,
        string $email = null,
        string $idToken = null,
        string $accessToken = null,
        string $refreshToken = null,
    ): User;

    public function updateToken(
        string $userId,
        string $idToken = null,
        string $accessToken = null,
        string $refreshToken = null,
    ): ?User;
}
