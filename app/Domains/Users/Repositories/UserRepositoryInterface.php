<?php

declare(strict_types=1);

namespace App\Domains\Users\Repositories;

use App\Domains\Users\Models\User;

interface UserRepositoryInterface
{
    public function create(array $data): User;
    public function findByEmail(string $email): ?User;
}
