<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Guard demo accounts from ever being seeded in production
        if (! app()->environment('local', 'testing')) {
            $this->command?->warn('Demo seeder dilewati karena lingkungan bukan local atau testing.');

            return;
        }

        $office = \App\Models\WorkLocation::firstOrCreate(
            ['name' => 'Kantor Pusat Jakarta'],
            [
                'address' => 'Jl. Medan Merdeka Barat No. 1, Jakarta Pusat',
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'radius_meters' => 100,
                'max_accuracy_meters' => 50,
                'is_active' => true,
            ]
        );

        \App\Models\WorkSchedule::firstOrCreate(
            [
                'work_location_id' => $office->id,
                'name' => 'Jadwal Normal Reguler',
            ],
            [
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'late_tolerance_minutes' => 15,
                'is_active' => true,
            ]
        );

        \App\Models\User::firstOrCreate(
            ['email' => 'admin@geoattend.test'],
            [
                'name' => 'Demo Admin',
                'password' => 'password',
                'role' => \App\Enums\Role::ADMIN,
                'work_location_id' => $office->id,
                'is_active' => true,
            ]
        );

        \App\Models\User::firstOrCreate(
            ['email' => 'employee@geoattend.test'],
            [
                'name' => 'Demo Employee',
                'password' => 'password',
                'role' => \App\Enums\Role::EMPLOYEE,
                'work_location_id' => $office->id,
                'is_active' => true,
            ]
        );
    }
}
