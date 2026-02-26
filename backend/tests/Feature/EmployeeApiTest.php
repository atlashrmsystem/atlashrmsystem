<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_paginated_employees()
    {
        Sanctum::actingAs($this->createHrUser());
        Employee::factory()->count(20)->create();

        $response = $this->getJson('/api/employees');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'last_page']);

        $this->assertCount(15, $response->json('data')); // Default pagination is 15
    }

    public function test_can_create_employee()
    {
        Sanctum::actingAs($this->createHrUser());

        $data = [
            'full_name' => 'John Doe Test',
            'email' => 'john.test@atlas.com',
            'phone' => '+971501234567',
            'passport_number' => 'PASS123',
            'passport_expiry' => '2030-01-01',
            'emirates_id' => '784-1234-5678901-1',
            'job_title' => 'Software Engineer',
            'department' => 'IT',
            'basic_salary' => 15000,
            'joining_date' => '2024-01-01',
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['full_name' => 'John Doe Test']);

        $this->assertDatabaseHas('employees', [
            'email' => 'john.test@atlas.com',
            'department' => 'IT',
        ]);
    }

    private function createHrUser(): User
    {
        $viewEmployees = Permission::findOrCreate('view employees', 'web');
        $manageEmployees = Permission::findOrCreate('manage employees', 'web');

        $role = Role::findOrCreate('admin', 'web');
        $role->givePermissionTo([$viewEmployees, $manageEmployees]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
