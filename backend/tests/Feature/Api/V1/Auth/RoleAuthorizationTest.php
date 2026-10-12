<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_access_admin_protected_endpoint(): void
    {
        $admin = User::factory()->admin()->create([
            'is_active' => true,
        ]);

        $token = $admin->createToken('AdminDevice')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/ping');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => 'admin-access-granted',
                ],
                'message' => 'Success',
            ]);
    }

    public function test_employee_cannot_access_admin_protected_endpoint(): void
    {
        $employee = User::factory()->employee()->create([
            'is_active' => true,
        ]);

        $token = $employee->createToken('EmployeeDevice')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/ping');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses resource ini.',
            ]);
    }

    public function test_inactive_user_with_token_is_blocked_by_active_middleware(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $token = $user->createToken('Device')->plainTextToken;

        // Deactivate user after token creation
        $user->update(['is_active' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Akun dinonaktifkan. Silakan hubungi administrator.',
            ]);
    }
}
