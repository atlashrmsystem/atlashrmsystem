<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Shift;
use App\Models\Store;
use App\Models\User;
use App\Models\UserStore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // User and access management
            'manage users',
            'assign roles',
            'view audit logs',
            'manage system settings',

            // HR operations
            'view employees',
            'manage employees',
            'view payroll',
            'manage payroll',
            'view attendance',
            'manage attendance',
            'view leaves',
            'manage leaves',
            'view reports',
            'view analytics',
            'manage recruitment',
            'manage performance',
            'manage benefits',
            'view compliance',
            'manage compliance',
        ];

        $mobilePermissions = [
            'view own profile',
            'edit own profile',
            'clock in/out',
            'request leave',
            'request payslip',
            'view own pay slips',
            'request salary certificate',
            'view own schedule',
            'enter sales',
            'manage schedules',
            'approve leave supervisor',
            'approve leave manager',
            'view store attendances',
            'view store sales',
            'view store reports',
        ];

        foreach (array_merge($permissions, $mobilePermissions) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $platformAdminOnlyPermissions = [
            'manage users',
            'assign roles',
            'view audit logs',
            'manage system settings',
        ];

        // Create roles
        $superAdminRole = Role::findOrCreate('super-admin', 'web');
        $adminRole = Role::findOrCreate('admin', 'web');
        $employeeRole = Role::findOrCreate('employee', 'web');
        $staffRole = Role::findOrCreate('staff', 'web');
        $supervisorRole = Role::findOrCreate('supervisor', 'web');
        $shiftSupervisorRole = Role::findOrCreate('shift-supervisor', 'web');
        $managerRole = Role::findOrCreate('manager', 'web');
        $salesTeamRole = Role::findOrCreate('sales-team', 'web');

        // Super-admin: full platform control
        $superAdminRole->syncPermissions(Permission::where('guard_name', 'web')->get());

        // Admin: full access except platform-admin-only capabilities.
        $adminRole->syncPermissions(
            Permission::where('guard_name', 'web')
                ->whereNotIn('name', $platformAdminOnlyPermissions)
                ->get()
        );

        $staffRole->syncPermissions(Permission::whereIn('name', [
            'view own profile',
            'edit own profile',
            'clock in/out',
            'request leave',
            'request payslip',
            'view own pay slips',
            'request salary certificate',
            'view own schedule',
            'enter sales',
        ])->get());

        $supervisorPermissions = Permission::whereIn('name', [
            'view own profile',
            'edit own profile',
            'clock in/out',
            'request leave',
            'request payslip',
            'view own pay slips',
            'request salary certificate',
            'view own schedule',
            'enter sales',
            'manage schedules',
            'approve leave supervisor',
            'view store sales',
        ])->get();
        $supervisorRole->syncPermissions($supervisorPermissions);
        $shiftSupervisorRole->syncPermissions($supervisorPermissions);

        $managerRole->syncPermissions(Permission::whereIn('name', [
            'view own profile',
            'edit own profile',
            'clock in/out',
            'request leave',
            'request payslip',
            'view own pay slips',
            'request salary certificate',
            'view own schedule',
            'enter sales',
            'approve leave manager',
            'view store attendances',
            'view store sales',
            'view store reports',
        ])->get());

        $salesTeamRole->syncPermissions(Permission::whereIn('name', [
            'view own profile',
            'edit own profile',
            'clock in/out',
            'request leave',
            'request payslip',
            'view own pay slips',
            'request salary certificate',
            'view own schedule',
            'view store sales',
            'view store reports',
        ])->get());

        // Assign super-admin/admin accounts if present
        $superAdminUser = User::where('email', 'superadmin@atlas.org')->first();
        if ($superAdminUser) {
            $superAdminUser->syncRoles([$superAdminRole]);
        }

        $adminCandidates = ['robin@atlas.org', 'robin@atlas.com'];
        foreach ($adminCandidates as $email) {
            $adminUser = User::where('email', $email)->first();
            if ($adminUser && ! $adminUser->hasRole('super-admin')) {
                $adminUser->syncRoles([$adminRole]);
            }
        }

        // Default non-privileged users with no roles get employee role.
        User::with('roles')->get()->each(function (User $user) use ($employeeRole) {
            if ($user->roles->isEmpty()) {
                $user->assignRole($employeeRole);
            }
        });

        $hasMobileSchema = Schema::hasTable('stores')
            && Schema::hasTable('shifts')
            && Schema::hasTable('user_store')
            && Schema::hasTable('employees');

        if ($hasMobileSchema) {
            $storeDefaults = [
                'latitude' => 25.2048,
                'longitude' => 55.2708,
                'radius_meters' => 200,
                'is_active' => true,
            ];
            if (Schema::hasColumn('stores', 'address')) {
                $storeDefaults['address'] = 'Dubai, UAE';
            }

            $demoStore = Store::firstOrCreate(
                ['name' => 'Atlas Demo Store'],
                $storeDefaults
            );

            Shift::firstOrCreate(
                ['store_id' => $demoStore->id, 'name' => 'Morning'],
                ['start_time' => '09:00', 'end_time' => '17:00']
            );
            Shift::firstOrCreate(
                ['store_id' => $demoStore->id, 'name' => 'Evening'],
                ['start_time' => '13:00', 'end_time' => '21:00']
            );

            $mobileUsers = [
                [
                    'email' => 'staff@atlas.org',
                    'name' => 'Atlas Staff',
                    'role' => $staffRole,
                    'store_role' => 'staff',
                    'pin' => 'EMP900001',
                    'job_title' => 'Store Staff',
                ],
                [
                    'email' => 'supervisor@atlas.org',
                    'name' => 'Atlas Shift Supervisor',
                    'role' => $supervisorRole,
                    'store_role' => 'shift-supervisor',
                    'pin' => 'EMP900002',
                    'job_title' => 'Shift Supervisor',
                ],
                [
                    'email' => 'manager@atlas.org',
                    'name' => 'Atlas Manager',
                    'role' => $managerRole,
                    'store_role' => 'manager',
                    'pin' => 'EMP900003',
                    'job_title' => 'Store Manager',
                ],
                [
                    'email' => 'sales@atlas.org',
                    'name' => 'Atlas Sales Team',
                    'role' => $salesTeamRole,
                    'store_role' => 'sales-team',
                    'pin' => 'EMP900004',
                    'job_title' => 'Sales Analyst',
                ],
            ];

            foreach ($mobileUsers as $mobileUser) {
                $employee = Employee::firstOrCreate(
                    ['email' => $mobileUser['email']],
                    [
                        'employee_pin' => $mobileUser['pin'],
                        'first_name' => explode(' ', $mobileUser['name'])[0] ?? $mobileUser['name'],
                        'last_name' => explode(' ', $mobileUser['name'])[1] ?? '',
                        'full_name' => $mobileUser['name'],
                        'phone' => '+971500000000',
                        'passport_number' => 'P'.substr(md5($mobileUser['email']), 0, 8),
                        'passport_expiry' => now()->addYears(5)->toDateString(),
                        'visa_status' => 'Active',
                        'visa_issue_date' => now()->subYear()->toDateString(),
                        'visa_expiry' => now()->addYears(2)->toDateString(),
                        'emirates_id' => '784-1990-1234567-1',
                        'job_title' => $mobileUser['job_title'],
                        'department' => 'Operations',
                        'status' => 'active',
                        'basic_salary' => 6000,
                        'joining_date' => now()->subMonths(6)->toDateString(),
                        'store_id' => $demoStore->id,
                    ]
                );

                if ((int) $employee->store_id !== (int) $demoStore->id) {
                    $employee->update(['store_id' => $demoStore->id]);
                }

                $user = User::firstOrCreate(
                    ['email' => $mobileUser['email']],
                    [
                        'name' => $mobileUser['name'],
                        'password' => Hash::make('Password@123'),
                        'employee_id' => $employee->id,
                    ]
                );

                $user->forceFill([
                    'name' => $mobileUser['name'],
                    'employee_id' => $employee->id,
                ])->save();

                $user->syncRoles([$mobileUser['role']]);

                UserStore::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'store_id' => $demoStore->id,
                    ],
                    [
                        'role_in_store' => $mobileUser['store_role'],
                        'is_primary' => true,
                    ]
                );
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
