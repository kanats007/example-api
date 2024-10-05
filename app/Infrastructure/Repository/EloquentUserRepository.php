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
    ): User
    {
        $userId = Str::uuid();
        $user = new User();
        $user->user_id = $userId;
        $user->oidc_user_id = $sub;
        $user->name = $name;
        $user->email = $email;

        try {
            DB::beginTransaction();
            $user->save();
            return $user;
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw new Exception($e->getMessage());
        }
    }
}