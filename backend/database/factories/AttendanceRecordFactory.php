<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'work_location_id' => WorkLocation::factory(),
            'attendance_date' => now()->toDateString(),
            'check_in_at' => now()->setTime(8, 5, 0),
            'check_in_latitude' => -6.1753924,
            'check_in_longitude' => 106.8271528,
            'check_in_accuracy_meters' => 12.5,
            'check_in_distance_meters' => 15.0,
            'check_out_at' => null,
            'check_out_latitude' => null,
            'check_out_longitude' => null,
            'check_out_accuracy_meters' => null,
            'check_out_distance_meters' => null,
            'status' => AttendanceStatus::PRESENT,
        ];
    }

    public function checkedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_out_at' => now()->setTime(17, 5, 0),
            'check_out_latitude' => -6.1753924,
            'check_out_longitude' => 106.8271528,
            'check_out_accuracy_meters' => 10.0,
            'check_out_distance_meters' => 12.0,
        ]);
    }

    public function late(): static
    {
        return $this->state(fn () => [
            'status' => AttendanceStatus::LATE,
            'check_in_at' => now()->setTime(8, 30, 0),
        ]);
    }
}
