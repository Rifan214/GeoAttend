<?php

namespace Tests\Feature\Api\V1\Attendance;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuditLogPersistenceTest extends TestCase
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

    public function test_audit_log_persists_when_check_in_rejected_for_out_of_geofence(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.200000, // Outside office
                'longitude' => 106.800000,
                'accuracy_meters' => 10.0,
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $this->user->id,
            'action' => 'attendance.check_in_rejected',
        ]);
    }

    public function test_audit_log_persists_when_check_in_rejected_for_poor_accuracy(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 80.0, // > 50m
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $this->user->id,
            'action' => 'attendance.check_in_rejected',
        ]);
    }

    public function test_audit_log_persists_when_check_in_rejected_for_duplicate_conflict_after_transaction_rollback(): void
    {
        // First check in (success)
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ])
            ->assertStatus(201);

        // Attempt duplicate check in (triggers conflict)
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ]);

        $response->assertStatus(409);

        // Verify rejected audit log exists in database even though attendance transaction rolled back
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $this->user->id,
            'action' => 'attendance.check_in_rejected',
        ]);
    }

    public function test_audit_log_persists_when_check_out_rejected_without_prior_check_in(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-out', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ]);

        $response->assertStatus(409);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $this->user->id,
            'action' => 'attendance.check_out_rejected',
        ]);
    }

    public function test_audit_log_persists_when_check_out_rejected_for_duplicate_checkout(): void
    {
        // Check in
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ])
            ->assertStatus(201);

        // First checkout (success)
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-out', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ])
            ->assertStatus(200);

        // Second checkout (conflict)
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-out', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ]);

        $response->assertStatus(409);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $this->user->id,
            'action' => 'attendance.check_out_rejected',
        ]);
    }
}
