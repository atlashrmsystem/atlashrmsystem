<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Store;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use App\Services\AuditService;
use App\Support\DepartmentAssignmentPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EmployeeController extends Controller
{
    protected EmployeeRepository $repository;

    protected AuditService $audit;

    public function __construct(EmployeeRepository $repository, AuditService $audit)
    {
        $this->repository = $repository;
        $this->audit = $audit;
    }

    private function setMobileRole(User $user, string $mobileRole): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role = Role::findOrCreate($mobileRole, 'web');

        // Preserve existing non-mobile roles (e.g., admin/super-admin), replace only mobile role bucket.
        // Keep legacy shift-supervisor in cleanup list so old assignments are removed
        // when a new supported mobile role is saved.
        $mobileRoles = ['employee', 'staff', 'supervisor', 'shift-supervisor', 'manager', 'sales-team'];
        $existingRoles = $user->getRoleNames()->toArray();
        foreach ($mobileRoles as $name) {
            if (in_array($name, $existingRoles, true)) {
                $user->removeRole($name);
            }
        }
        $user->assignRole($role);
    }

    private function generateEmployeePin(): string
    {
        do {
            $pin = 'EMP'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (Employee::where('employee_pin', $pin)->exists());

        return $pin;
    }

    private function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        if (count($parts) === 0) {
            return ['', ''];
        }

        if (count($parts) === 1) {
            return [$parts[0], ''];
        }

        $lastName = array_pop($parts);
        $firstName = implode(' ', $parts);

        return [$firstName, $lastName];
    }

    private function normalizeEmployeePayload(array $validated, ?Employee $employee = null): array
    {
        if (array_key_exists('status', $validated) && ! is_null($validated['status'])) {
            $validated['status'] = strtolower((string) $validated['status']);
        }

        $fullName = trim((string) ($validated['full_name'] ?? $employee?->full_name ?? ''));
        $firstName = trim((string) ($validated['first_name'] ?? $employee?->first_name ?? ''));
        $lastName = trim((string) ($validated['last_name'] ?? $employee?->last_name ?? ''));

        if ($fullName === '' && ($firstName !== '' || $lastName !== '')) {
            $fullName = trim($firstName.' '.$lastName);
            $validated['full_name'] = $fullName;
        }

        if ($fullName !== '' && ($firstName === '' || $lastName === '')) {
            [$guessFirst, $guessLast] = $this->splitFullName($fullName);
            if ($firstName === '' && $guessFirst !== '') {
                $validated['first_name'] = $guessFirst;
            }
            if ($lastName === '' && $guessLast !== '') {
                $validated['last_name'] = $guessLast;
            }
        }

        if (is_null($employee) && empty($validated['employee_pin'])) {
            $validated['employee_pin'] = $this->generateEmployeePin();
        }

        if (is_null($employee) && empty($validated['status'])) {
            $validated['status'] = 'active';
        }

        if (is_null($employee) && ! array_key_exists('basic_salary', $validated)) {
            // Keep employee creation valid while salary entry is moved to Salary tab.
            $validated['basic_salary'] = 0;
        }

        return $validated;
    }

    private function validateDepartmentAssignment(array $validated, ?Employee $employee = null): void
    {
        $effectiveDepartment = $validated['department'] ?? $employee?->department;
        $policy = DepartmentAssignmentPolicy::forDepartment($effectiveDepartment);

        // For updates, do not block unrelated edits (e.g., photo-only changes)
        // when assignment fields are not being changed.
        if ($employee) {
            $hasDepartmentInput = array_key_exists('department', $validated);
            $departmentChanged = $hasDepartmentInput
                && DepartmentAssignmentPolicy::normalize((string) $validated['department']) !== DepartmentAssignmentPolicy::normalize((string) $employee->department);
            $assignmentTouched = $departmentChanged
                || array_key_exists('store_id', $validated)
                || array_key_exists('brand_id', $validated);

            if (! $assignmentTouched) {
                return;
            }
        }

        $hasStoreInput = array_key_exists('store_id', $validated);
        $hasBrandInput = array_key_exists('brand_id', $validated);

        $effectiveStoreId = $hasStoreInput ? ($validated['store_id'] ?? null) : ($employee?->store_id ?? null);
        $effectiveBrandId = $hasBrandInput ? ($validated['brand_id'] ?? null) : null;

        $store = null;
        if (! empty($effectiveStoreId)) {
            $store = Store::query()->select(['id', 'brand_id'])->find((int) $effectiveStoreId);
            if (! $store) {
                throw ValidationException::withMessages([
                    'store_id' => ['Selected store does not exist.'],
                ]);
            }
        }

        if (! empty($effectiveBrandId) && $store && (int) $store->brand_id !== (int) $effectiveBrandId) {
            throw ValidationException::withMessages([
                'store_id' => ['Selected store does not belong to the selected brand.'],
                'brand_id' => ['Selected brand does not match the store brand.'],
            ]);
        }

        if (empty($effectiveBrandId) && $store) {
            $effectiveBrandId = (int) $store->brand_id;
        }

        if ($policy['requires_brand'] && empty($effectiveBrandId)) {
            throw ValidationException::withMessages([
                'brand_id' => ['Brand is required for the selected department.'],
            ]);
        }

        if ($policy['requires_store'] && empty($effectiveStoreId)) {
            throw ValidationException::withMessages([
                'store_id' => ['Store is required for the selected department.'],
            ]);
        }
    }

    public function assignmentRules()
    {
        $roleBasedPositions = collect(['Staff', 'Supervisor', 'Manager', 'Sales Team']);
        $existingJobTitles = Employee::query()
            ->whereNotNull('job_title')
            ->where('job_title', '!=', '')
            ->distinct()
            ->orderBy('job_title')
            ->pluck('job_title');

        $positions = $roleBasedPositions
            ->merge($existingJobTitles)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $positionsByDepartment = [
            'Food & Beverage' => ['Staff', 'Supervisor', 'Manager'],
        ];

        return response()->json([
            'departments' => DepartmentAssignmentPolicy::options(),
            'default_policy' => DepartmentAssignmentPolicy::forDepartment(null),
            'positions' => $positions,
            'positions_by_department' => $positionsByDepartment,
        ]);
    }

    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $search = trim((string) $request->input('q', ''));
        $employees = $this->repository->getPaginated($perPage, $search)->toArray();
        $employees['active_contracts'] = $this->repository->countEmployeesWithActiveContract();
        $employees['expiring_soon_contracts'] = $this->repository->countEmployeesWithContractsExpiringSoon(30);

        return response()->json($employees);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'employee_pin' => 'nullable|string|max:50|unique:employees,employee_pin',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => ['required', 'email', 'max:255', 'unique:employees,email', 'unique:users,email'],
            'photo' => 'nullable|image|max:2048',
            'phone' => 'nullable|string',
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'ACTIVE', 'INACTIVE'])],
            'date_of_birth' => 'nullable|date|before:today',
            'nationality' => 'nullable|string|max:255',
            'passport_number' => 'required|string',
            'passport_issue_date' => 'nullable|date|before_or_equal:passport_expiry',
            'passport_expiry' => 'required|date|after_or_equal:passport_issue_date',
            'visa_status' => 'nullable|string',
            'visa_issue_date' => 'nullable|date|before_or_equal:visa_expiry',
            'visa_expiry' => 'nullable|date|after_or_equal:visa_issue_date',
            'emirates_id' => 'required|string',
            'emirates_id_issue_date' => 'nullable|date|before_or_equal:emirates_id_expiry_date',
            'emirates_id_expiry_date' => 'nullable|date|after_or_equal:emirates_id_issue_date',
            'insurance_start_date' => 'nullable|date|before_or_equal:insurance_end_date',
            'insurance_end_date' => 'nullable|date|after_or_equal:insurance_start_date',
            'job_title' => 'required|string',
            'department' => 'required|string',
            'basic_salary' => 'nullable|numeric',
            'allowances' => 'nullable|array',
            'joining_date' => 'required|date',
            'manager_id' => 'nullable|exists:employees,id',
            'brand_id' => 'nullable|exists:brands,id',
            'store_id' => 'nullable|exists:stores,id',
            'permanent_address' => 'nullable|string',
            'permanent_city' => 'nullable|string|max:255',
            'permanent_country' => 'nullable|string|max:255',
            'present_address' => 'nullable|string',
            'present_city' => 'nullable|string|max:255',
            'present_country' => 'nullable|string|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'x_url' => 'nullable|url|max:255',
            'create_account' => 'nullable|boolean',
            'password' => 'required_if:create_account,true|string|min:8|confirmed',
            'mobile_role' => ['required_if:create_account,true', Rule::in(['staff', 'supervisor', 'manager', 'sales-team'])],
        ]);

        $this->validateDepartmentAssignment($validated);

        $payload = $this->normalizeEmployeePayload($validated);

        $employee = DB::transaction(function () use ($request, $payload) {
            $employee = $this->repository->create(
                collect($payload)->except(['create_account', 'password', 'password_confirmation', 'brand_id'])->toArray()
            );

            if ($request->boolean('create_account')) {
                $user = User::create([
                    'name' => $employee->full_name,
                    'email' => $employee->email,
                    'password' => Hash::make($request->password),
                    'employee_id' => $employee->id,
                ]);

                $mobileRole = $request->input('mobile_role', 'staff');
                $this->setMobileRole($user, $mobileRole);

                $this->audit->log(
                    $request->user(),
                    'employee.account.created',
                    Employee::class,
                    $employee->id,
                    [
                        'user_id' => $user->id,
                        'mobile_role' => $mobileRole,
                        'email' => $user->email,
                    ]
                );
            }

            return $employee;
        });

        return response()->json($employee->load('user.roles'), 201);
    }

    public function me(Request $request)
    {
        $employee = $request->user()->employee;
        if (! $employee) {
            return response()->json(['message' => 'Employee profile not found'], 404);
        }
        $employee->loadMissing(['store.brand:id,name', 'store.brandArea:id,name']);

        return response()->json([
            'data' => [
                ...$employee->toArray(),
                'assigned_store' => $employee->store ? [
                    'id' => $employee->store->id,
                    'name' => $employee->store->name,
                    'brand_name' => $employee->store->brand?->name,
                    'brand_area_name' => $employee->store->brandArea?->name,
                ] : null,
            ],
        ]);
    }

    public function updateMe(Request $request)
    {
        $employee = $request->user()->employee;
        if (! $employee) {
            return response()->json(['message' => 'Employee profile not found'], 404);
        }

        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'first_name' => 'sometimes|nullable|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'phone' => 'nullable|string',
            'nationality' => 'sometimes|string',
            'present_address' => 'sometimes|nullable|string',
            'present_city' => 'sometimes|nullable|string|max:255',
            'present_country' => 'sometimes|nullable|string|max:255',
        ]);

        $validated = $this->normalizeEmployeePayload($validated, $employee);

        DB::transaction(function () use ($employee, $request, $validated) {
            $employee->update($validated);

            if (array_key_exists('full_name', $validated)) {
                $request->user()->update(['name' => $validated['full_name']]);
            }
        });

        return response()->json(['data' => $employee]);
    }

    public function show(int $id)
    {
        $employee = $this->repository->findById($id);

        return response()->json($employee);
    }

    public function update(Request $request, int $id)
    {
        $employee = $this->repository->findById($id);
        $linkedUserId = $employee->user?->id;

        $emailRules = ['sometimes', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($id)];
        if ($linkedUserId) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($linkedUserId);
        } else {
            $emailRules[] = Rule::unique('users', 'email');
        }

        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'employee_pin' => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('employees', 'employee_pin')->ignore($id)],
            'first_name' => 'sometimes|nullable|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'email' => $emailRules,
            'photo' => 'nullable|image|max:2048',
            'phone' => 'nullable|string',
            'gender' => ['sometimes', 'nullable', Rule::in(['male', 'female', 'other'])],
            'status' => ['sometimes', 'nullable', Rule::in(['active', 'inactive', 'ACTIVE', 'INACTIVE'])],
            'date_of_birth' => 'sometimes|nullable|date|before:today',
            'nationality' => 'sometimes|nullable|string|max:255',
            'passport_number' => 'sometimes|string',
            'passport_issue_date' => 'sometimes|nullable|date|before_or_equal:passport_expiry',
            'passport_expiry' => 'sometimes|date|after_or_equal:passport_issue_date',
            'visa_status' => 'nullable|string',
            'visa_issue_date' => 'nullable|date|before_or_equal:visa_expiry',
            'visa_expiry' => 'nullable|date|after_or_equal:visa_issue_date',
            'emirates_id' => 'sometimes|string',
            'emirates_id_issue_date' => 'sometimes|nullable|date|before_or_equal:emirates_id_expiry_date',
            'emirates_id_expiry_date' => 'sometimes|nullable|date|after_or_equal:emirates_id_issue_date',
            'insurance_start_date' => 'sometimes|nullable|date|before_or_equal:insurance_end_date',
            'insurance_end_date' => 'sometimes|nullable|date|after_or_equal:insurance_start_date',
            'job_title' => 'sometimes|string',
            'department' => 'sometimes|string',
            'basic_salary' => 'sometimes|numeric',
            'allowances' => 'nullable|array',
            'joining_date' => 'sometimes|date',
            'manager_id' => 'nullable|exists:employees,id',
            'brand_id' => 'sometimes|nullable|exists:brands,id',
            'store_id' => 'sometimes|nullable|exists:stores,id',
            'permanent_address' => 'sometimes|nullable|string',
            'permanent_city' => 'sometimes|nullable|string|max:255',
            'permanent_country' => 'sometimes|nullable|string|max:255',
            'present_address' => 'sometimes|nullable|string',
            'present_city' => 'sometimes|nullable|string|max:255',
            'present_country' => 'sometimes|nullable|string|max:255',
            'linkedin_url' => 'sometimes|nullable|url|max:255',
            'facebook_url' => 'sometimes|nullable|url|max:255',
            'x_url' => 'sometimes|nullable|url|max:255',
            'mobile_role' => ['sometimes', 'nullable', Rule::in(['staff', 'supervisor', 'manager', 'sales-team'])],
        ]);

        $validated = $this->normalizeEmployeePayload($validated, $employee);
        $this->validateDepartmentAssignment($validated, $employee);

        $employee = DB::transaction(function () use ($employee, $validated) {
            $mobileRole = $validated['mobile_role'] ?? null;
            unset($validated['mobile_role']);
            unset($validated['brand_id']);

            $employee = $this->repository->update($employee, $validated);

            if ($employee->user) {
                $userUpdate = [];
                if (array_key_exists('full_name', $validated)) {
                    $userUpdate['name'] = $validated['full_name'];
                }
                if (array_key_exists('email', $validated)) {
                    $userUpdate['email'] = $validated['email'];
                }
                if (! empty($userUpdate)) {
                    $employee->user->update($userUpdate);
                }

                if ($mobileRole) {
                    $this->setMobileRole($employee->user, $mobileRole);
                }
            } elseif ($mobileRole) {
                abort(422, 'Create app account first before assigning role.');
            }

            return $employee;
        });

        return response()->json($employee->load('user.roles'));
    }

    public function destroy(Request $request, int $id)
    {
        $employee = $this->repository->findById($id);
        DB::transaction(function () use ($request, $employee) {
            $employee->load('user');

            if ($employee->user) {
                $user = $employee->user;
                $user->tokens()->delete();
                $user->syncRoles([]);
                $user->delete();

                $this->audit->log(
                    $request->user(),
                    'employee.account.deleted',
                    Employee::class,
                    $employee->id,
                    [
                        'email' => $user->email,
                        'user_id' => $user->id,
                    ]
                );
            }

            $this->repository->delete($employee);
        });

        return response()->json(null, 204);
    }

    public function createUserAccount(Request $request, int $id)
    {
        $employee = $this->repository->findById($id);

        if ($employee->user) {
            return response()->json(['message' => 'Employee already has a user account'], 422);
        }

        if (User::where('email', $employee->email)->exists()) {
            return response()->json([
                'message' => 'A user with this employee email already exists. Please update employee email first.',
            ], 422);
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
            'mobile_role' => ['nullable', Rule::in(['staff', 'supervisor', 'manager', 'sales-team'])],
        ]);

        $user = DB::transaction(function () use ($request, $employee) {
            $user = User::create([
                'name' => $employee->full_name,
                'email' => $employee->email,
                'password' => Hash::make($request->password),
                'employee_id' => $employee->id,
            ]);

            $mobileRole = $request->input('mobile_role', 'staff');
            $this->setMobileRole($user, $mobileRole);

            $this->audit->log(
                $request->user(),
                'employee.account.created',
                Employee::class,
                $employee->id,
                [
                    'user_id' => $user->id,
                    'mobile_role' => $mobileRole,
                    'email' => $user->email,
                ]
            );

            return $user;
        });

        return response()->json([
            'message' => 'User account created successfully',
            'user' => $user->load('roles'),
        ], 201);
    }

    public function resetUserCredentials(Request $request, int $id)
    {
        $employee = $this->repository->findById($id);

        if (! $employee->user) {
            return response()->json(['message' => 'Employee does not have a user account'], 422);
        }

        $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employee->user->id),
                Rule::unique('employees', 'email')->ignore($employee->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'mobile_role' => ['nullable', Rule::in(['staff', 'supervisor', 'manager', 'sales-team'])],
        ]);

        DB::transaction(function () use ($request, $employee) {
            $updateData = ['email' => $request->email];
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $employee->update(['email' => $request->email]);
            $employee->user->update($updateData);

            if ($request->filled('mobile_role')) {
                $this->setMobileRole($employee->user, $request->mobile_role);
            }

            $this->audit->log(
                $request->user(),
                'employee.account.updated',
                Employee::class,
                $employee->id,
                [
                    'user_id' => $employee->user->id,
                    'email' => $employee->user->email,
                    'mobile_role' => $request->input('mobile_role'),
                    'password_reset' => $request->filled('password'),
                ]
            );
        });

        return response()->json([
            'message' => 'Account updated successfully',
            'user' => $employee->fresh()->user,
        ]);
    }
}
