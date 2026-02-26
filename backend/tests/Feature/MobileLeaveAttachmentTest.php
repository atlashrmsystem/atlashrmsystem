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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileLeaveAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_leave_request_persists_uploaded_attachment(): void
    {
        Storage::fake('public');

        $permission = Permission::findOrCreate('request leave', 'web');
        $role = Role::findOrCreate('staff', 'web');
        $role->givePermissionTo([$permission]);

        $brand = Brand::create([
            'name' => 'Milestones '.uniqid(),
            'slug' => 'milestones-'.uniqid(),
        ]);
        $area = BrandArea::create([
            'brand_id' => $brand->id,
            'name' => 'Main Area',
        ]);
        $store = Store::create([
            'name' => 'Main Store '.uniqid(),
            'brand_id' => $brand->id,
            'brand_area_id' => $area->id,
            'address' => 'Dubai',
            'latitude' => 25.2048,
            'longitude' => 55.2708,
            'radius_meters' => 200,
            'is_active' => true,
        ]);

        $employee = Employee::factory()->create(['store_id' => $store->id]);
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->assignRole($role);

        $leaveType = LeaveType::create([
            'name' => 'Sick',
            'default_days' => 10,
            'requires_attachment' => true,
            'is_paid' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->post('/api/leave-requests', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-03-04',
            'end_date' => '2026-03-06',
            'reason' => 'Medical rest',
            'attachment' => UploadedFile::fake()->create('medical.pdf', 120, 'application/pdf'),
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertCreated();

        $leave = LeaveRequest::query()->latest('id')->first();
        $this->assertNotNull($leave);
        $this->assertNotNull($leave->attachment_path);
        $this->assertStringStartsWith('leave-attachments/', $leave->attachment_path);
        Storage::disk('public')->assertExists($leave->attachment_path);
    }
}

