<?php

declare(strict_types=1);

namespace App\Domains\Users\Controllers;

use App\Domains\Users\DTOs\LoginDto;
use App\Domains\Users\DTOs\RegisterDto;
use App\Domains\Users\Services\AuthService;
use App\Domains\Users\Requests\LoginRequest;
use App\Domains\Users\Requests\RegisterRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register a new user.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = RegisterDto::fromRequest($request);
        $result = $this->authService->register($dto);

        return response()->json([
            'user' => $result['user']->toArray(),
            'token' => $result['token'],
        ], 201);
    }

    /**
     * Authenticate and login a user.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $dto = LoginDto::fromRequest($request);
        $result = $this->authService->login($dto);

        return response()->json([
            'user' => $result['user']->toArray(),
            'token' => $result['token'],
        ], 200);
    }

    /**
     * Logout user by revoking all their tokens.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var \App\Domains\Users\Models\User|null $user */
        $user = $request->user();

        if ($user !== null) {
            $this->authService->logout($user);
        }

        return response()->json([
            'message' => 'Successfully logged out',
        ], 200);
    }
}
