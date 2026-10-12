<?php

namespace Tests\Feature\Api\V1\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ConcurrencyAndSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected WorkLocation $location;
    protected WorkSchedule $schedule;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = WorkLocation::factory()->create([
            'latitude' => -6.1753924,
            'longitude' => 106.8271528,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->schedule = WorkSchedule::factory()->create([
            'work_location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'work_location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $this->token = $this->user->createToken('Device')->plainTextToken;
    }

    public function test_concurrent_check_in_unique_constraint_violation_returns_409_not_500(): void
    {
        // Simulate a pre-existing record inserted by a simultaneous race-condition thread
        AttendanceRecord::create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => now()->toDateString(),
            'check_in_at' => now(),
            'check_in_latitude' => -6.1753924,
            'check_in_longitude' => 106.8271528,
            'check_in_accuracy_meters' => 10.0,
            'check_in_distance_meters' => 0.0,
            'status' => 'present',
        ]);

        // Attempt check-in on the same date
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ]);

        // Must return 409 Conflict, never 500 Internal Server Error
        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Anda sudah melakukan check-in hari ini.',
            ]);

        // Verify rejected audit log exists
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $this->user->id,
            'action' => 'attendance.check_in_rejected',
        ]);
    }

    public function test_database_seeder_does_not_seed_demo_accounts_in_production(): void
    {
        // Temporarily simulate production environment
        $originalEnv = $this->app->environment();
        $this->app->detectEnvironment(fn () => 'production');

        $initialUserCount = User::whereIn('email', ['admin@geoattend.test', 'employee@geoattend.test'])->count();

        $seeder = new DatabaseSeeder();
        $seeder->run();

        $afterUserCount = User::whereIn('email', ['admin@geoattend.test', 'employee@geoattend.test'])->count();

        $this->assertEquals($initialUserCount, $afterUserCount, 'Seeder must not create demo accounts in production.');

        // Restore environment
        $this->app->detectEnvironment(fn () => $originalEnv);
    }
}
