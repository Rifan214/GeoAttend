<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LocationManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => Role::ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_locations_index(): void
    {
        $location = WorkLocation::factory()->create([
            'name' => 'Gedung Wisma 46',
            'radius_meters' => 150,
        ]);

        $response = $this->actingAs($this->admin, 'web')->get(route('admin.locations.index'));

        $response->assertStatus(200);
        $response->assertSee('Gedung Wisma 46');
        $response->assertSee('150 meter');
    }

    public function test_admin_can_create_work_location_with_default_schedule(): void
    {
        $response = $this->actingAs($this->admin, 'web')->post(route('admin.locations.store'), [
            'name' => 'Kantor Cabang Bandung',
            'address' => 'Jl. Asia Afrika No. 10',
            'latitude' => -6.917464,
            'longitude' => 107.619123,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => '1',
            'start_time' => '08:30',
            'end_time' => '17:30',
            'late_tolerance_minutes' => 20,
        ]);

        $response->assertRedirect(route('admin.locations.index'));

        $this->assertDatabaseHas('work_locations', [
            'name' => 'Kantor Cabang Bandung',
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
        ]);

        $location = WorkLocation::where('name', 'Kantor Cabang Bandung')->first();
        $this->assertNotNull($location);

        $this->assertDatabaseHas('work_schedules', [
            'work_location_id' => $location->id,
            'start_time' => '08:30:00',
            'end_time' => '17:30:00',
            'late_tolerance_minutes' => 20,
            'is_active' => true,
        ]);
    }

    public function test_create_work_location_validates_coordinate_bounds_and_radius(): void
    {
        // Invalid latitude (> 90), invalid longitude (< -180), invalid radius (<= 0)
        $response = $this->actingAs($this->admin, 'web')
            ->from(route('admin.locations.create'))
            ->post(route('admin.locations.store'), [
                'name' => 'Invalid Bounds',
                'latitude' => 95.0,
                'longitude' => -190.0,
                'radius_meters' => -10,
                'max_accuracy_meters' => 0,
            ]);

        $response->assertRedirect(route('admin.locations.create'));
        $response->assertSessionHasErrors(['latitude', 'longitude', 'radius_meters', 'max_accuracy_meters']);
    }

    public function test_admin_can_update_location_coordinates_and_radius(): void
    {
        $location = WorkLocation::factory()->create([
            'name' => 'Old Name',
            'latitude' => -6.200000,
            'longitude' => 106.800000,
            'radius_meters' => 80,
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->put(route('admin.locations.update', $location), [
                'name' => 'Updated Location Name',
                'latitude' => -6.210000,
                'longitude' => 106.820000,
                'radius_meters' => 120,
                'max_accuracy_meters' => 45,
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('admin.locations.index'));

        $this->assertDatabaseHas('work_locations', [
            'id' => $location->id,
            'name' => 'Updated Location Name',
            'radius_meters' => 120,
        ]);
    }

    public function test_location_updates_preserve_historical_attendance_records(): void
    {
        $location = WorkLocation::factory()->create([
            'latitude' => -6.200000,
            'longitude' => 106.800000,
            'radius_meters' => 100,
        ]);

        $user = User::factory()->create(['work_location_id' => $location->id]);

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'work_location_id' => $location->id,
            'check_in_latitude' => -6.200050,
            'check_in_longitude' => 106.800050,
            'check_in_distance_meters' => 15.5,
        ]);

        // Update location coordinates
        $this->actingAs($this->admin, 'web')->put(route('admin.locations.update', $location), [
            'name' => $location->name,
            'latitude' => -6.500000,
            'longitude' => 107.000000,
            'radius_meters' => 200,
            'max_accuracy_meters' => 50,
            'is_active' => '1',
        ]);

        // The historical record's check-in coordinates and distance must remain unchanged
        $attendance->refresh();
        $this->assertEquals(-6.200050, (float) $attendance->check_in_latitude);
        $this->assertEquals(106.800050, (float) $attendance->check_in_longitude);
        $this->assertEquals(15.5, (float) $attendance->check_in_distance_meters);
    }

    public function test_admin_can_toggle_location_active_status(): void
    {
        $location = WorkLocation::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin, 'web')
            ->post(route('admin.locations.toggle-status', $location));

        $response->assertStatus(302);
        $this->assertDatabaseHas('work_locations', [
            'id' => $location->id,
            'is_active' => false,
        ]);
    }
}
