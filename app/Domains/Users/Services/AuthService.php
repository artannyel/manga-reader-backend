<?php

declare(strict_types=1);

namespace App\Domains\Users\Services;

use App\Domains\Users\DTOs\LoginDto;
use App\Domains\Users\DTOs\RegisterDto;
use App\Domains\Users\DTOs\UserDto;
use App\Domains\Users\Models\User;
use App\Domains\Users\Repositories\UserRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Register a new user and generate access token.
     *
     * @param RegisterDto $dto
     * @return array{user: UserDto, token: string}
     */
    public function register(RegisterDto $dto): array
    {
        $user = $this->userRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => UserDto::fromModel($user),
            'token' => $token,
        ];
    }

    /**
     * Authenticate user credentials and generate access token.
     *
     * @param LoginDto $dto
     * @return array{user: UserDto, token: string}
     * @throws AuthenticationException
     */
    public function login(LoginDto $dto): array
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw new AuthenticationException('Credenciais inválidas.');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => UserDto::fromModel($user),
            'token' => $token,
        ];
    }

    /**
     * Revoke current user's tokens (logout).
     *
     * @param User $user
     * @return void
     */
    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}
