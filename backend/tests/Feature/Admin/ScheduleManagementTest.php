<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected WorkLocation $location;
    protected WorkSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => Role::ADMIN,
            'is_active' => true,
        ]);

        $this->location = WorkLocation::factory()->create([
            'is_active' => true,
        ]);

        $this->schedule = WorkSchedule::factory()->create([
            'work_location_id' => $this->location->id,
            'name' => 'Jadwal Reguler',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'late_tolerance_minutes' => 15,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_schedules_index(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get(route('admin.schedules.index'));

        $response->assertStatus(200);
        $response->assertSee('Jadwal Reguler');
        $response->assertSee('08:00');
        $response->assertSee('17:00');
    }

    public function test_admin_can_view_schedule_edit_form(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.schedules.edit', $this->schedule));

        $response->assertStatus(200);
        $response->assertSee('Perbarui Jadwal: Jadwal Reguler');
        $response->assertSee('08:00');
    }

    public function test_admin_can_update_schedule(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->put(route('admin.schedules.update', $this->schedule), [
                'name' => 'Jadwal Pagi Diperbarui',
                'start_time' => '07:30',
                'end_time' => '16:30',
                'late_tolerance_minutes' => 10,
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('admin.schedules.index'));
        $this->assertDatabaseHas('work_schedules', [
            'id' => $this->schedule->id,
            'name' => 'Jadwal Pagi Diperbarui',
            'start_time' => '07:30:00',
            'end_time' => '16:30:00',
            'late_tolerance_minutes' => 10,
        ]);
    }

    public function test_update_schedule_validates_end_time_is_after_start_time(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->from(route('admin.schedules.edit', $this->schedule))
            ->put(route('admin.schedules.update', $this->schedule), [
                'name' => 'Invalid Times',
                'start_time' => '18:00',
                'end_time' => '08:00', // before start_time
                'late_tolerance_minutes' => 15,
            ]);

        $response->assertRedirect(route('admin.schedules.edit', $this->schedule));
        $response->assertSessionHasErrors('end_time');
    }

    public function test_update_schedule_validates_tolerance_is_positive_or_zero(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->from(route('admin.schedules.edit', $this->schedule))
            ->put(route('admin.schedules.update', $this->schedule), [
                'name' => 'Invalid Tolerance',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'late_tolerance_minutes' => -5,
            ]);

        $response->assertRedirect(route('admin.schedules.edit', $this->schedule));
        $response->assertSessionHasErrors('late_tolerance_minutes');
    }
}
