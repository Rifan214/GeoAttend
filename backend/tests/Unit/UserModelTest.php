<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserModelTest extends TestCase
{
    public function test_user_role_methods_for_admin(): void
    {
        $user = new User([
            'role' => Role::ADMIN,
            'is_active' => true,
        ]);

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isEmployee());
    }

    public function test_user_role_methods_for_employee(): void
    {
        $user = new User([
            'role' => Role::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isEmployee());
    }
}
