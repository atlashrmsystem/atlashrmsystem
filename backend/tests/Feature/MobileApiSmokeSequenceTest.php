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
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileApiSmokeSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_smoke_sequence_login_leave_submit_and_approval_queue_fetch(): void
    {
        Storage::fake('public');

        $requestLeavePermission = Permission::findOrCreate('request leave', 'web');

        $staffRole = Role::findOrCreate('staff', 'web');
        $staffRole->givePermissionTo([$requestLeavePermission]);
        $supervisorRole = Role::findOrCreate('supervisor', 'web');

        // Ensure optional roles used by notification fanout do not crash if referenced.
        Role::findOrCreate('shift-supervisor', 'web');
        Role::findOrCreate('manager', 'web');
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('super-admin', 'web');

        $brand = Brand::create([
            'name' => 'Smoke Brand '.uniqid(),
            'slug' => 'smoke-brand-'.uniqid(),
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

        $staffEmployee = Employee::factory()->create(['store_id' => $store->id]);
        $staffUser = User::factory()->create([
            'employee_id' => $staffEmployee->id,
            'password' => bcrypt('password'),
        ]);
        $staffUser->assignRole($staffRole);

        $supervisorEmployee = Employee::factory()->create(['store_id' => $store->id]);
        $supervisorUser = User::factory()->create([
            'employee_id' => $supervisorEmployee->id,
            'password' => bcrypt('password'),
        ]);
        $supervisorUser->assignRole($supervisorRole);
        $supervisorUser->stores()->attach($store->id, [
            'role_in_store' => 'supervisor',
            'is_primary' => true,
        ]);

        $leaveType = LeaveType::create([
            'name' => 'Sick',
            'default_days' => 10,
            'requires_attachment' => true,
            'is_paid' => true,
        ]);

        // 1) Login as staff
        $staffLogin = $this->postJson('/api/login', [
            'email' => $staffUser->email,
            'password' => 'password',
        ]);
        $staffLogin->assertOk();
        $staffToken = (string) $staffLogin->json('access_token');
        $this->assertNotSame('', $staffToken);

        // 2) Submit leave with file as staff
        $leaveSubmit = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$staffToken,
                'Accept' => 'application/json',
            ])
            ->post('/api/leave-requests', [
                'leave_type_id' => $leaveType->id,
                'start_date' => '2026-03-10',
                'end_date' => '2026-03-12',
                'reason' => 'Medical leave',
                'attachment' => UploadedFile::fake()->create('medical.pdf', 120, 'application/pdf'),
            ]);

        $leaveSubmit->assertCreated();
        $leaveSubmit->assertJsonPath('data.workflow_status', LeaveRequest::WORKFLOW_PENDING_SUPERVISOR);
        $leaveId = (int) $leaveSubmit->json('data.id');
        $this->assertGreaterThan(0, $leaveId);

        $leave = LeaveRequest::query()->find($leaveId);
        $this->assertNotNull($leave);
        $this->assertNotNull($leave->attachment_path);
        Storage::disk('public')->assertExists($leave->attachment_path);

        // 3) Login as supervisor and fetch approval queue
        $supervisorLogin = $this->postJson('/api/login', [
            'email' => $supervisorUser->email,
            'password' => 'password',
        ]);
        $supervisorLogin->assertOk();
        $supervisorToken = (string) $supervisorLogin->json('access_token');
        $this->assertNotSame('', $supervisorToken);

        $approvalQueue = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$supervisorToken,
                'Accept' => 'application/json',
            ])
            ->getJson('/api/leave-requests');

        $approvalQueue->assertOk();

        $queueIds = collect($approvalQueue->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains($leaveId, $queueIds);
    }
}

