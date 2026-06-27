<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful registration.
     */
    public function test_successful_registration(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    }

    /**
     * Test registration validation failure due to missing email or mismatching passwords.
     */
    public function test_registration_validation_failure(): void
    {
        // Missing email
        $payload = [
            'name' => 'John Doe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'email' => 'O campo email é obrigatório.'
            ]);

        // Mismatched password confirmation
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ];

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'password' => 'A confirmação do campo password não confere.'
            ]);
    }

    /**
     * Test registration with a duplicate email.
     */
    public function test_registration_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $payload = [
            'name' => 'Another User',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'email' => 'O valor do campo email já está em uso.'
            ]);
    }

    /**
     * Test successful login.
     */
    public function test_successful_login(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $payload = [
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/login', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ]);
    }

    /**
     * Test login validation failure due to missing fields.
     */
    public function test_login_validation_failure(): void
    {
        $payload = [
            'email' => '',
            'password' => '',
        ];

        $response = $this->postJson('/api/login', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'email' => 'O campo email é obrigatório.',
                'password' => 'O campo password é obrigatório.'
            ]);
    }

    /**
     * Test login with invalid credentials.
     */
    public function test_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $payload = [
            'email' => 'john@example.com',
            'password' => 'wrong_password',
        ];

        $response = $this->postJson('/api/login', $payload);
        $response->assertStatus(401)
            ->assertJsonFragment([
                'message' => 'Credenciais inválidas.'
            ]);
    }

    /**
     * Test successful logout.
     */
    public function test_successful_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logout realizado com sucesso',
            ]);

        // Assert token is removed from database
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * Test logout for guests / unauthenticated users.
     */
    public function test_logout_unauthenticated(): void
    {
        $response = $this->postJson('/api/logout');
        $response->assertStatus(401);
    }
}
