<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_can_view_admin_login_page(): void
    {
        $response = $this->get(route('admin.login'));

        $response->assertStatus(200);
        $response->assertSee('GeoAttend Admin');
        $response->assertSee('Masuk');
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $admin = User::factory()->create([
            'role' => Role::ADMIN,
            'is_active' => true,
            'password' => bcrypt('AdminPassword123!'),
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'email' => $admin->email,
            'password' => 'AdminPassword123!',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'web');
    }

    public function test_admin_login_fails_with_invalid_password(): void
    {
        $admin = User::factory()->create([
            'role' => Role::ADMIN,
            'is_active' => true,
            'password' => bcrypt('AdminPassword123!'),
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login.submit'), [
            'email' => $admin->email,
            'password' => 'WrongPassword!',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    public function test_inactive_admin_is_rejected_at_login(): void
    {
        $inactiveAdmin = User::factory()->create([
            'role' => Role::ADMIN,
            'is_active' => false,
            'password' => bcrypt('AdminPassword123!'),
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login.submit'), [
            'email' => $inactiveAdmin->email,
            'password' => 'AdminPassword123!',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    public function test_employee_cannot_login_to_admin_panel(): void
    {
        $employee = User::factory()->create([
            'role' => Role::EMPLOYEE,
            'is_active' => true,
            'password' => bcrypt('EmployeePassword123!'),
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login.submit'), [
            'email' => $employee->email,
            'password' => 'EmployeePassword123!',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    public function test_admin_can_logout(): void
    {
        $admin = User::factory()->create([
            'role' => Role::ADMIN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'web')->post(route('admin.logout'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('web');
    }

    public function test_unauthenticated_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_authenticated_employee_is_forbidden_from_admin_dashboard(): void
    {
        $employee = User::factory()->create([
            'role' => Role::EMPLOYEE,
            'is_active' => true,
        ]);

        $response = $this->actingAs($employee, 'web')->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }
}
