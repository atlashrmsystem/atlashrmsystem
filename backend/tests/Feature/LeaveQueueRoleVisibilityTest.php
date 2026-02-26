<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\BrandArea;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaveQueueRoleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_dual_role_supervisor_manager_sees_supervisor_and_manager_stage_for_assigned_store(): void
    {
        $leaveType = LeaveType::create([
            'name' => 'Sick',
            'default_days' => 12,
            'requires_attachment' => false,
            'is_paid' => true,
        ]);

        $brand = Brand::create([
            'name' => 'Ferdinand '.uniqid(),
            'slug' => 'ferdinand-'.uniqid(),
        ]);
        $area = BrandArea::create([
            'brand_id' => $brand->id,
            'name' => 'Area A',
        ]);
        $assignedStore = Store::create([
            'name' => 'Ferdinand Store A '.uniqid(),
            'brand_id' => $brand->id,
            'brand_area_id' => $area->id,
            'address' => 'Abu Dhabi',
            'latitude' => 24.4539,
            'longitude' => 54.3773,
            'radius_meters' => 200,
            'is_active' => true,
        ]);
        $otherStore = Store::create([
            'name' => 'Ferdinand Store B '.uniqid(),
            'brand_id' => $brand->id,
            'brand_area_id' => $area->id,
            'address' => 'Abu Dhabi',
            'latitude' => 24.4540,
            'longitude' => 54.3774,
            'radius_meters' => 200,
            'is_active' => true,
        ]);

        $employee = Employee::factory()->create(['store_id' => $assignedStore->id]);
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->assignRole([
            Role::findOrCreate('supervisor', 'web'),
            Role::findOrCreate('manager', 'web'),
        ]);
        $user->stores()->attach($assignedStore->id, [
            'role_in_store' => 'supervisor',
            'is_primary' => true,
        ]);

        $staffA = Employee::factory()->create(['store_id' => $assignedStore->id]);
        $staffB = Employee::factory()->create(['store_id' => $assignedStore->id]);
        $staffC = Employee::factory()->create(['store_id' => $otherStore->id]);

        LeaveRequest::create([
            'employee_id' => $staffA->id,
            'store_id' => $assignedStore->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-02-25',
            'end_date' => '2026-02-25',
            'total_days' => 1,
            'status' => 'pending',
            'workflow_status' => LeaveRequest::WORKFLOW_PENDING_SUPERVISOR,
            'reason' => 'Sick leave',
        ]);
        LeaveRequest::create([
            'employee_id' => $staffB->id,
            'store_id' => $assignedStore->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-02-26',
            'end_date' => '2026-02-26',
            'total_days' => 1,
            'status' => 'pending',
            'workflow_status' => LeaveRequest::WORKFLOW_PENDING_MANAGER,
            'reason' => 'Sick leave',
        ]);
        LeaveRequest::create([
            'employee_id' => $staffC->id,
            'store_id' => $otherStore->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-02-27',
            'end_date' => '2026-02-27',
            'total_days' => 1,
            'status' => 'pending',
            'workflow_status' => LeaveRequest::WORKFLOW_PENDING_SUPERVISOR,
            'reason' => 'Sick leave',
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/leave-requests');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $statuses = collect($response->json('data'))->pluck('workflow_status')->sort()->values()->all();
        $this->assertSame(
            [LeaveRequest::WORKFLOW_PENDING_MANAGER, LeaveRequest::WORKFLOW_PENDING_SUPERVISOR],
            $statuses
        );
    }
}

