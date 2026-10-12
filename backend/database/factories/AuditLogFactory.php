<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_user_id' => User::factory(),
            'action' => 'attendance.check_in',
            'subject_type' => null,
            'subject_id' => null,
            'metadata' => [
                'distance_meters' => 15.2,
                'accuracy_meters' => 10.0,
            ],
            'ip_address' => '127.0.0.1',
        ];
    }
}
