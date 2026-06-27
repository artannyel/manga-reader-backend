<?php

declare(strict_types=1);

namespace App\Domains\Users\DTOs;

use App\Domains\Users\Requests\LoginRequest;

class LoginDto
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        return new self(
            email: $request->validated('email'),
            password: $request->validated('password'),
        );
    }
}
