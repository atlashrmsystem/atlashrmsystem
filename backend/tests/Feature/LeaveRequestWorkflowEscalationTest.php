<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\BrandArea;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaveRequestWorkflowEscalationTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_leave_request_starts_at_pending_manager(): void
    {
        $permission = Permission::findOrCreate('request leave', 'web');
        $role = Role::findOrCreate('supervisor', 'web');
        $role->givePermissionTo([$permission]);
        Role::findOrCreate('manager', 'web');

        $brand = Brand::create([
            'name' => 'Ferdinand '.uniqid(),
            'slug' => 'ferdinand-'.uniqid(),
        ]);
        $area = BrandArea::create([
            'brand_id' => $brand->id,
            'name' => 'Area 1',
        ]);
        $store = \App\Models\Store::create([
            'name' => 'Ferdinand Store '.uniqid(),
            'brand_id' => $brand->id,
            'brand_area_id' => $area->id,
            'address' => 'Abu Dhabi',
            'latitude' => 24.4539,
            'longitude' => 54.3773,
            'radius_meters' => 200,
            'is_active' => true,
        ]);

        $employee = Employee::factory()->create(['store_id' => $store->id]);
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->assignRole($role);

        $leaveType = LeaveType::create([
            'name' => 'Sick',
            'default_days' => 10,
            'requires_attachment' => false,
            'is_paid' => true,
        ]);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/leave-requests', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-03-03',
            'end_date' => '2026-03-03',
            'reason' => 'Medical leave',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.workflow_status', 'pending_manager');
    }
}
