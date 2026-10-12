<?php

namespace Tests\Feature\Api\V1\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CheckOutTest extends TestCase
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
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'work_location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $this->token = $this->user->createToken('MobileApp')->plainTextToken;
    }

    public function test_check_out_success_after_check_in(): void
    {
        // First check in
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ])
            ->assertStatus(201);

        // Check out later
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-out', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 12.0,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'check_out_accuracy_meters' => 12.0,
                    'check_out_distance_meters' => 0.0,
                ],
                'message' => 'Check-out berhasil',
            ]);

        $this->assertNotNull($response->json('data.check_out_at'));

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $this->user->id,
            'attendance_date' => Carbon::today()->toDateString(),
        ]);
    }

    public function test_check_out_rejected_without_prior_check_in_with_409(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-out', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Belum ada catatan check-in hari ini. Anda harus melakukan check-in terlebih dahulu.',
            ]);
    }

    public function test_check_out_rejected_when_already_checked_out_with_409(): void
    {
        // Check in
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ])
            ->assertStatus(201);

        // First check out
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-out', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ])
            ->assertStatus(200);

        // Second check out
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-out', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Anda sudah melakukan check-out hari ini.',
            ]);
    }

    public function test_check_out_rejected_when_outside_geofence_with_422(): void
    {
        // Check in inside office
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ])
            ->assertStatus(201);

        // Attempt check-out outside office (Bundaran HI)
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-out', [
                'latitude' => -6.195022,
                'longitude' => 106.823055,
                'accuracy_meters' => 10.0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['location']);
    }

    public function test_check_out_rejected_when_accuracy_too_poor_with_422(): void
    {
        // Check in
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ])
            ->assertStatus(201);

        // Attempt check out with 60m accuracy
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-out', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 60.0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['location']);
    }

    public function test_check_out_creates_audit_log_entry(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ])
            ->assertStatus(201);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-out', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $this->user->id,
            'action' => 'attendance.check_out',
        ]);
    }
}
