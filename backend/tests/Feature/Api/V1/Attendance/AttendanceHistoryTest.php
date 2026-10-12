<?php

namespace Tests\Feature\Api\V1\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AttendanceHistoryTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user1;
    protected User $user2;
    protected WorkLocation $location;
    protected string $token1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = WorkLocation::factory()->create();

        $this->user1 = User::factory()->create([
            'work_location_id' => $this->location->id,
            'is_active' => true,
        ]);
        $this->user2 = User::factory()->create([
            'work_location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $this->token1 = $this->user1->createToken('App1')->plainTextToken;
    }

    public function test_user_only_retrieves_their_own_attendance_records(): void
    {
        // User 1 records (2 records)
        AttendanceRecord::factory()->create([
            'user_id' => $this->user1->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => Carbon::parse('2026-10-10')->toDateString(),
        ]);
        AttendanceRecord::factory()->create([
            'user_id' => $this->user1->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => Carbon::parse('2026-10-11')->toDateString(),
        ]);

        // User 2 records (1 record)
        AttendanceRecord::factory()->create([
            'user_id' => $this->user2->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => Carbon::parse('2026-10-10')->toDateString(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/v1/attendance/history');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $recordUserIds = collect($response->json('data'))->pluck('user_id');
        // Ensure none of user 2's data appears
        foreach ($response->json('data') as $item) {
            $this->assertDatabaseHas('attendance_records', [
                'id' => $item['id'],
                'user_id' => $this->user1->id,
            ]);
        }
    }

    public function test_history_filtered_by_date_range(): void
    {
        AttendanceRecord::factory()->create([
            'user_id' => $this->user1->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => '2026-10-01',
        ]);
        AttendanceRecord::factory()->create([
            'user_id' => $this->user1->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => '2026-10-05',
        ]);
        AttendanceRecord::factory()->create([
            'user_id' => $this->user1->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => '2026-10-10',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/v1/attendance/history?from=2026-10-02&to=2026-10-06');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attendance_date', '2026-10-05');
    }

    public function test_history_returns_paginated_meta_structure(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            AttendanceRecord::factory()->create([
                'user_id' => $this->user1->id,
                'work_location_id' => $this->location->id,
                'attendance_date' => "2026-09-0{$i}",
            ]);
        }

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/v1/attendance/history?per_page=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 2,
                    'total' => 5,
                    'last_page' => 3,
                ],
                'message' => 'Success',
            ]);
    }

    public function test_unauthenticated_request_to_history_returns_401(): void
    {
        $response = $this->getJson('/api/v1/attendance/history');

        $response->assertStatus(401);
    }
}
