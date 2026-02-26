<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\BrandArea;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScheduleClosingShiftEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_normalizes_to_single_closer_for_the_day(): void
    {
        $ctx = $this->createStoreContext();
        $user = $this->createSupervisorUserForStore($ctx['store']->id);
        Sanctum::actingAs($user);

        $date = '2026-03-10';

        $first = $this->postJson('/api/schedules', [
            'store_id' => $ctx['store']->id,
            'employee_id' => $ctx['employees'][0]->id,
            'shift_id' => $ctx['shift']->id,
            'date' => $date,
            'is_closing_shift' => true,
        ]);
        $first->assertCreated();

        $second = $this->postJson('/api/schedules', [
            'store_id' => $ctx['store']->id,
            'employee_id' => $ctx['employees'][1]->id,
            'shift_id' => $ctx['shift']->id,
            'date' => $date,
            'is_closing_shift' => true,
        ]);
        $second->assertCreated();

        $this->assertSame(1, Schedule::query()
            ->where('store_id', $ctx['store']->id)
            ->whereDate('date', $date)
            ->where('is_closing_shift', true)
            ->count());

        $this->assertDatabaseHas('schedules', [
            'id' => $second->json('data.id'),
            'is_closing_shift' => true,
        ]);
    }

    public function test_update_rebalances_previous_day_when_closer_moves_date(): void
    {
        $ctx = $this->createStoreContext();
        $user = $this->createSupervisorUserForStore($ctx['store']->id);
        Sanctum::actingAs($user);

        $dayOne = '2026-03-11';
        $dayTwo = '2026-03-12';

        $movedSchedule = Schedule::create([
            'store_id' => $ctx['store']->id,
            'employee_id' => $ctx['employees'][0]->id,
            'shift_id' => $ctx['shift']->id,
            'date' => $dayOne,
            'is_closing_shift' => true,
        ]);

        $remainingSchedule = Schedule::create([
            'store_id' => $ctx['store']->id,
            'employee_id' => $ctx['employees'][1]->id,
            'shift_id' => $ctx['shift']->id,
            'date' => $dayOne,
            'is_closing_shift' => false,
        ]);

        $response = $this->putJson("/api/schedules/{$movedSchedule->id}", [
            'store_id' => $ctx['store']->id,
            'employee_id' => $ctx['employees'][0]->id,
            'shift_id' => $ctx['shift']->id,
            'date' => $dayTwo,
            'is_closing_shift' => false,
        ]);
        $response->assertOk();

        $this->assertDatabaseHas('schedules', [
            'id' => $remainingSchedule->id,
            'is_closing_shift' => true,
        ]);

        $this->assertSame(1, Schedule::query()
            ->where('store_id', $ctx['store']->id)
            ->whereDate('date', $dayOne)
            ->where('is_closing_shift', true)
            ->count());

        $this->assertSame(1, Schedule::query()
            ->where('store_id', $ctx['store']->id)
            ->whereDate('date', $dayTwo)
            ->where('is_closing_shift', true)
            ->count());
    }

    public function test_delete_promotes_another_schedule_as_closer(): void
    {
        $ctx = $this->createStoreContext();
        $user = $this->createSupervisorUserForStore($ctx['store']->id);
        Sanctum::actingAs($user);

        $date = '2026-03-13';
        $closing = Schedule::create([
            'store_id' => $ctx['store']->id,
            'employee_id' => $ctx['employees'][0]->id,
            'shift_id' => $ctx['shift']->id,
            'date' => $date,
            'is_closing_shift' => true,
        ]);

        $other = Schedule::create([
            'store_id' => $ctx['store']->id,
            'employee_id' => $ctx['employees'][1]->id,
            'shift_id' => $ctx['shift']->id,
            'date' => $date,
            'is_closing_shift' => false,
        ]);

        $response = $this->deleteJson("/api/schedules/{$closing->id}");
        $response->assertNoContent();

        $this->assertDatabaseHas('schedules', [
            'id' => $other->id,
            'is_closing_shift' => true,
        ]);

        $this->assertSame(1, Schedule::query()
            ->where('store_id', $ctx['store']->id)
            ->whereDate('date', $date)
            ->where('is_closing_shift', true)
            ->count());
    }

    private function createStoreContext(): array
    {
        $brand = Brand::create([
            'name' => 'Test Brand '.uniqid(),
            'slug' => 'test-brand-'.uniqid(),
        ]);

        $area = BrandArea::create([
            'brand_id' => $brand->id,
            'name' => 'Area 1',
        ]);

        $store = Store::create([
            'name' => 'Test Store '.uniqid(),
            'brand_id' => $brand->id,
            'brand_area_id' => $area->id,
            'address' => 'Abu Dhabi',
            'latitude' => 24.4539,
            'longitude' => 54.3773,
            'radius_meters' => 200,
            'is_active' => true,
        ]);

        $shift = Shift::create([
            'store_id' => $store->id,
            'name' => 'Morning',
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $employees = [
            Employee::factory()->create(['store_id' => $store->id]),
            Employee::factory()->create(['store_id' => $store->id]),
            Employee::factory()->create(['store_id' => $store->id]),
        ];

        return [
            'brand' => $brand,
            'area' => $area,
            'store' => $store,
            'shift' => $shift,
            'employees' => $employees,
        ];
    }

    private function createSupervisorUserForStore(int $storeId): User
    {
        $permission = Permission::findOrCreate('manage schedules', 'web');
        $role = Role::findOrCreate('supervisor', 'web');
        $role->givePermissionTo([$permission]);

        $employee = Employee::factory()->create(['store_id' => $storeId]);
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->assignRole($role);

        return $user;
    }
}
