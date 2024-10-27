<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\UserRepository;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EloquentUserRepository implements UserRepository
{
    public function findByOidcUserId(string $sub): ?User
    {
        return User::whereOidcUserId($sub)->first();
    }

    public function findByUserId(string $userId): ?User
    {
        return User::whereUserId($userId)->first();
    }

    public function create(
        string $sub,
        string $name = null,
        string $email = null,
        string $idToken = null,
        string $accessToken = null,
        string $refreshToken = null,
    ): User {
        $userId = Str::uuid();
        $user = new User();
        $user->user_id = $userId;
        $user->oidc_user_id = $sub;
        $user->name = $name;
        $user->email = $email;
        $user->id_token = $idToken;
        $user->access_token = $accessToken;
        $user->refresh_token = $refreshToken;

        try {
            DB::beginTransaction();
            $user->save();
            DB::commit();
            return $user;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function updateToken(
        string $userId,
        string $idToken = null,
        string $accessToken = null,
        string $refreshToken = null,
    ): ?User {
        $user = $this->findByUserId($userId);
        if ($user === null) {
            return null;
        }
        $user->id_token = $idToken;
        $user->access_token = $accessToken;
        $user->refresh_token = $refreshToken;

        try {
            DB::beginTransaction();
            $user->save();
            DB::commit();
            return $user;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }
}
