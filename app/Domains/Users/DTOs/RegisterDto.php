<?php

declare(strict_types=1);

namespace App\Domains\Users\DTOs;

use App\Domains\Users\Requests\RegisterRequest;

class RegisterDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}

    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
        );
    }
}
