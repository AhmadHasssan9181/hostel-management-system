<?php

namespace Tests\Feature;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\CreatesHmsData;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use CreatesHmsData, RefreshDatabase;

    public function test_registration_is_disabled_and_accounts_are_admin_provisioned(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();

        $this->postJson('/api/v1/auth/register', [])->assertNotFound();
    }

    public function test_a_user_can_login_and_logout(): void
    {
        $student = $this->makeStudent(['email' => 'student@hms.test']);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'student@hms.test',
            'password' => 'Password@123',
        ]);

        $login->assertOk()->assertJsonStructure(['token', 'user']);

        $token = $login->json('token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $student->id);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->makeStudent(['email' => 'student@hms.test']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'student@hms.test',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }

    public function test_students_cannot_access_staff_routes(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/students')
            ->assertForbidden();
    }

    public function test_employees_can_access_staff_routes_but_not_admin_routes(): void
    {
        $employee = $this->makeUser(Role::Employee);

        $this->actingAs($employee, 'sanctum')
            ->getJson('/api/v1/students')
            ->assertOk();

        $this->actingAs($employee, 'sanctum')
            ->getJson('/api/v1/users')
            ->assertForbidden();
    }

    public function test_a_user_can_reset_a_forgotten_password(): void
    {
        $student = $this->makeStudent(['email' => 'reset@hms.test']);

        $token = Password::createToken($student);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'reset@hms.test',
            'token' => $token,
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewPassword@123', $student->fresh()->password));
    }
}
