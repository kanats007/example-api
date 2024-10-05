<?php
declare(strict_types=1);

namespace App\Domain\Repository;

use App\Models\User;

interface UserRepository
{
    public function findBySub(string $sub): ?User;

    public function create(
        string $sub,
        string $name = null,
        string $email = null,
    ): User;
}