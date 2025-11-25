<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Operator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OperatorRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful operator registration.
     */
    public function test_operator_can_register_successfully(): void
    {
        $response = $this->postJson('/internal/auth/register', [
            'operator_name' => 'Test Company Ltd',
            'email' => 'admin@testcompany.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user' => [
                    'id',
                    'uuid',
                    'name',
                    'email',
                    'role',
                    'operator_id',
                ],
                'operator' => [
                    'id',
                    'name',
                    'slug',
                    'is_active',
                ],
            ]);

        // Verify operator was created
        $this->assertDatabaseHas('operators', [
            'name' => 'Test Company Ltd',
            'slug' => 'test-company-ltd',
            'is_active' => true,
        ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Test Company Ltd',
            'email' => 'admin@testcompany.com',
            'role' => 'operator',
        ]);

        // Verify user is linked to operator
        $user = User::where('email', 'admin@testcompany.com')->first();
        $operator = Operator::where('name', 'Test Company Ltd')->first();
        $this->assertEquals($operator->id, $user->operator_id);

        // Verify token is valid
        $token = $response->json('access_token');
        $this->assertNotEmpty($token);
    }

    /**
     * Test registration fails with duplicate email.
     */
    public function test_registration_fails_with_duplicate_email(): void
    {
        // Create an existing user
        User::factory()->create([
            'email' => 'existing@test.com',
        ]);

        $response = $this->postJson('/internal/auth/register', [
            'operator_name' => 'Another Company',
            'email' => 'existing@test.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test registration fails with short password.
     */
    public function test_registration_fails_with_short_password(): void
    {
        $response = $this->postJson('/internal/auth/register', [
            'operator_name' => 'Test Company',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test registration fails with password mismatch.
     */
    public function test_registration_fails_with_password_mismatch(): void
    {
        $response = $this->postJson('/internal/auth/register', [
            'operator_name' => 'Test Company',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'DifferentPassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test registration fails without operator name.
     */
    public function test_registration_fails_without_operator_name(): void
    {
        $response = $this->postJson('/internal/auth/register', [
            'email' => 'test@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['operator_name']);
    }

    /**
     * Test registration fails without email.
     */
    public function test_registration_fails_without_email(): void
    {
        $response = $this->postJson('/internal/auth/register', [
            'operator_name' => 'Test Company',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test registration fails with invalid email format.
     */
    public function test_registration_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/internal/auth/register', [
            'operator_name' => 'Test Company',
            'email' => 'not-an-email',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test that registered user can immediately login with JWT token.
     */
    public function test_registered_user_can_use_jwt_token(): void
    {
        $response = $this->postJson('/internal/auth/register', [
            'operator_name' => 'Test Company Ltd',
            'email' => 'admin@testcompany.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $token = $response->json('access_token');

        // Use token to access protected route
        $meResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/internal/auth/me');

        $meResponse->assertStatus(200)
            ->assertJson([
                'email' => 'admin@testcompany.com',
                'role' => 'operator',
            ]);
    }

    /**
     * Test operator slug generation with special characters.
     */
    public function test_operator_slug_generation_with_special_characters(): void
    {
        $response = $this->postJson('/internal/auth/register', [
            'operator_name' => 'Test & Company #1!',
            'email' => 'admin@test.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('operators', [
            'name' => 'Test & Company #1!',
            'slug' => 'test-company-1',
        ]);
    }

    /**
     * Test database transaction rollback on error.
     */
    public function test_transaction_rollback_on_error(): void
    {
        // Count initial records
        $initialOperatorCount = Operator::count();
        $initialUserCount = User::count();

        // Attempt registration with duplicate email (should fail)
        User::factory()->create(['email' => 'existing@test.com']);

        $this->postJson('/internal/auth/register', [
            'operator_name' => 'Test Company',
            'email' => 'existing@test.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);

        // Verify no new records were created
        $this->assertEquals($initialOperatorCount, Operator::count());
        $this->assertEquals($initialUserCount + 1, User::count()); // Only the existing user
    }
}





