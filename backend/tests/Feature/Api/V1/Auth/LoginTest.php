<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_login_with_valid_credentials_and_receives_token(): void
    {
        $user = User::factory()->create([
            'email' => 'employee.test@geoattend.test',
            'password' => 'secret123',
            'role' => Role::EMPLOYEE,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'employee.test@geoattend.test',
            'password' => 'secret123',
            'device_name' => 'Pixel 8 Phone',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'work_location_id',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'message',
            ])
            ->assertJson([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => 'employee.test@geoattend.test',
                        'role' => 'employee',
                        'is_active' => true,
                    ],
                ],
                'message' => 'Login berhasil',
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_admin_receives_admin_role_in_payload(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin.test@geoattend.test',
            'password' => 'admin123',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin.test@geoattend.test',
            'password' => 'admin123',
            'device_name' => 'Admin Laptop',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.role', 'admin');
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'device_name']);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'valid@geoattend.test',
            'password' => 'correct-password',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'valid@geoattend.test',
            'password' => 'wrong-password',
            'device_name' => 'Mobile Device',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
            ]);
    }

    public function test_login_fails_for_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'nonexistent@geoattend.test',
            'password' => 'somepassword',
            'device_name' => 'Mobile Device',
        ]);

        $response->assertStatus(401);
    }

    public function test_inactive_user_cannot_login_even_with_valid_password(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@geoattend.test',
            'password' => 'valid-pass',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'inactive@geoattend.test',
            'password' => 'valid-pass',
            'device_name' => 'Mobile Device',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Akun Anda dinonaktifkan. Silakan hubungi administrator.',
            ]);
    }
}
