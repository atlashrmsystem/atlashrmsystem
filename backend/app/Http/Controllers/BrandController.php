<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    private function ensureAdmin(Request $request): void
    {
        if (! $request->user()->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'Unauthorized');
        }
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()->hasAnyRole(['manager', 'admin', 'super-admin'])) {
            abort(403, 'Unauthorized');
        }
    }

    public function index(Request $request)
    {
        $this->ensureCanView($request);
        $user = $request->user();

        $brands = Brand::query()
            ->forManager($user)
            ->with([
                'manager:id,name,email',
                'stores:id,name,address,brand_id,brand_area_id',
                'areas:id,brand_id,name,manager_user_id',
                'areas.manager:id,name,email',
                'areas.stores:id,name,address,brand_id,brand_area_id',
            ])
            ->withCount('stores')
            ->orderBy('name')
            ->get();

        return response()->json($brands);
    }

    public function managers(Request $request)
    {
        $this->ensureAdmin($request);

        $managers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'manager'))
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json($managers);
    }

    public function management(Request $request)
    {
        $this->ensureCanView($request);
        $user = $request->user();

        $brands = Brand::query()
            ->forManager($user)
            ->with([
                'manager:id,name,email',
                'areas:id,brand_id,name,manager_user_id',
                'areas.manager:id,name,email',
                'areas.stores:id,name,address,brand_id,brand_area_id',
                'areas.stores.employees:id,store_id,full_name,first_name,last_name',
                'areas.stores.users:id,name,email,employee_id',
                'areas.stores.userStores:id,user_id,store_id,role_in_store',
            ])
            ->orderBy('name')
            ->get();

        $brands->each(function (Brand $brand): void {
            $brand->areas->each(function ($area): void {
                $area->stores->each(function ($store): void {
                    $supervisorRoles = [
                        'supervisor',
                        'shift-supervisor',
                        'shift supervisor',
                        'store-supervisor',
                        'store supervisor',
                    ];
                    $storeRoleByUserId = $store->userStores
                        ->pluck('role_in_store', 'user_id')
                        ->map(fn ($value) => $value ? strtolower((string) $value) : null);

                    $employeeNames = $store->employees
                        ->map(function ($employee): ?string {
                            $fullName = trim((string) ($employee->full_name ?? ''));
                            if ($fullName !== '') {
                                return $fullName;
                            }
                            $fallback = trim((string) (($employee->first_name ?? '').' '.($employee->last_name ?? '')));

                            return $fallback !== '' ? $fallback : null;
                        })
                        ->filter()
                        ->values();

                    // Include explicitly linked store users (non-supervisor) as assigned staff.
                    $linkedStaffNames = $store->users
                        ->filter(function ($user) use ($storeRoleByUserId, $supervisorRoles): bool {
                            $storeRole = $storeRoleByUserId->get($user->id);
                            return !in_array((string) $storeRole, $supervisorRoles, true);
                        })
                        ->pluck('name')
                        ->filter();

                    $assignedNames = $employeeNames
                        ->concat($linkedStaffNames)
                        ->map(fn ($name) => trim((string) $name))
                        ->filter()
                        ->unique()
                        ->values();

                    $supervisorNames = $store->users
                        ->filter(function ($user) use ($storeRoleByUserId, $supervisorRoles): bool {
                            $storeRole = $storeRoleByUserId->get($user->id);
                            return in_array((string) $storeRole, $supervisorRoles, true);
                        })
                        ->pluck('name')
                        ->filter()
                        ->values();

                    $store->setAttribute('employee_count', $assignedNames->count());
                    $store->setAttribute('employee_names', $assignedNames->all());
                    $store->setAttribute(
                        'supervisor_names',
                        $supervisorNames->all()
                    );

                    unset($store->employees, $store->users, $store->userStores);
                });
            });
        });

        return response()->json($brands);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:brands,name'],
            'manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $managerUserId = $validated['manager_user_id'] ?? null;
        $this->assertValidManager($managerUserId);

        $brand = Brand::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'manager_user_id' => $managerUserId,
        ]);

        return response()->json(
            $brand->load(['manager:id,name,email', 'stores:id,name,brand_id', 'areas.manager:id,name,email'])->loadCount('stores'),
            201
        );
    }

    public function update(Request $request, Brand $brand)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($brand->id)],
            'manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $managerUserId = $validated['manager_user_id'] ?? null;
        $this->assertValidManager($managerUserId);

        $brand->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'manager_user_id' => $managerUserId,
        ]);

        return response()->json(
            $brand->load(['manager:id,name,email', 'stores:id,name,brand_id', 'areas.manager:id,name,email'])->loadCount('stores')
        );
    }

    public function destroy(Request $request, Brand $brand)
    {
        $this->ensureAdmin($request);

        if ($brand->stores()->exists()) {
            abort(422, 'Brand has stores assigned. Reassign stores before deleting the brand.');
        }

        $brand->delete();

        return response()->json(['message' => 'Brand deleted successfully.']);
    }

    public function storeStaff(Request $request, Brand $brand, Store $store)
    {
        $this->ensureCanView($request);
        $user = $request->user();

        if ((int) $store->brand_id !== (int) $brand->id) {
            abort(404);
        }

        $canAccess = $user->hasAnyRole(['super-admin', 'admin'])
            || Brand::query()->forManager($user)->whereKey($brand->id)->exists()
            || Store::query()->forSupervisor($user)->whereKey($store->id)->exists();

        if (! $canAccess) {
            abort(403, 'Unauthorized');
        }

        $store->load([
            'employees:id,store_id,full_name,first_name,last_name,email',
            'employees.user:id,employee_id,name,email',
            'employees.user.roles:id,name',
            'users:id,name,email',
            'users.roles:id,name',
            'userStores:id,user_id,store_id,role_in_store',
        ]);

        $storeRoleByUserId = $store->userStores
            ->pluck('role_in_store', 'user_id')
            ->map(fn ($value) => $value ? strtolower((string) $value) : null);

        $staff = collect();

        foreach ($store->employees as $employee) {
            $displayName = $employee->full_name ?: trim(($employee->first_name ?? '').' '.($employee->last_name ?? ''));
            $linkedUser = $employee->user;
            $key = $linkedUser ? 'user:'.$linkedUser->id : 'employee:'.$employee->id;

            $roles = $linkedUser
                ? $linkedUser->roles->pluck('name')->map(fn ($r) => strtolower((string) $r))->values()->all()
                : [];

            $storeRole = $linkedUser ? $storeRoleByUserId->get($linkedUser->id) : null;
            if ($storeRole && ! in_array($storeRole, $roles, true)) {
                $roles[] = $storeRole;
            }

            $staff->put($key, [
                'employee_id' => $employee->id,
                'user_id' => $linkedUser?->id,
                'name' => $displayName ?: ($linkedUser?->name ?: 'Unknown'),
                'email' => $employee->email ?: ($linkedUser?->email),
                'roles' => array_values(array_unique(array_filter($roles))),
                'store_role' => $storeRole,
            ]);
        }

        foreach ($store->users as $linkedUser) {
            $key = 'user:'.$linkedUser->id;
            if ($staff->has($key)) {
                continue;
            }

            $roles = $linkedUser->roles->pluck('name')->map(fn ($r) => strtolower((string) $r))->values()->all();
            $storeRole = $storeRoleByUserId->get($linkedUser->id);
            if ($storeRole && ! in_array($storeRole, $roles, true)) {
                $roles[] = $storeRole;
            }

            $staff->put($key, [
                'employee_id' => null,
                'user_id' => $linkedUser->id,
                'name' => $linkedUser->name,
                'email' => $linkedUser->email,
                'roles' => array_values(array_unique(array_filter($roles))),
                'store_role' => $storeRole,
            ]);
        }

        return response()->json([
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'brand_id' => $store->brand_id,
                'brand_area_id' => $store->brand_area_id,
            ],
            'staff' => $staff->values()->sortBy('name')->values(),
        ]);
    }

    private function assertValidManager(?int $managerUserId): void
    {
        if ($managerUserId === null) {
            return;
        }

        $user = User::query()->find($managerUserId);
        if (! $user || ! $user->hasRole('manager')) {
            abort(422, 'Selected manager must have the manager role.');
        }
    }
}
