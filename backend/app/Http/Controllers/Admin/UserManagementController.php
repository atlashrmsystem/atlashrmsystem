<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserManagementController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index()
    {
        $users = User::with('roles')->orderBy('id')->paginate(20);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $roles = Role::whereIn('name', $validated['roles'])->where('guard_name', 'web')->get();
        $user->syncRoles($roles);

        $this->audit->log(
            $request->user(),
            'user.created',
            User::class,
            $user->id,
            ['roles' => $roles->pluck('name')->values()]
        );

        return response()->json($user->load('roles'), 201);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['sometimes', 'required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }
        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }
        if (! empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        $user->save();

        if (array_key_exists('roles', $validated)) {
            $currentIsSuperAdmin = $user->hasRole('super-admin');
            $nextHasSuperAdmin = in_array('super-admin', $validated['roles'], true);

            if ($currentIsSuperAdmin && ! $nextHasSuperAdmin) {
                $totalSuperAdmins = User::role('super-admin')->count();
                if ($totalSuperAdmins <= 1) {
                    return response()->json(['message' => 'Cannot remove the last super-admin role.'], 422);
                }
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $roles = Role::whereIn('name', $validated['roles'])->where('guard_name', 'web')->get();
            $user->syncRoles($roles);
        }

        $this->audit->log(
            $request->user(),
            'user.updated',
            User::class,
            $user->id,
            [
                'roles' => $user->getRoleNames()->values(),
                'email' => $user->email,
            ]
        );

        return response()->json($user->load('roles'));
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        if ($user->hasRole('super-admin')) {
            $totalSuperAdmins = User::role('super-admin')->count();
            if ($totalSuperAdmins <= 1) {
                return response()->json(['message' => 'Cannot delete the last super-admin account.'], 422);
            }
        }

        $deletedUserId = $user->id;
        $deletedEmail = $user->email;
        $user->delete();

        $this->audit->log(
            $request->user(),
            'user.deleted',
            User::class,
            $deletedUserId,
            ['email' => $deletedEmail]
        );

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
