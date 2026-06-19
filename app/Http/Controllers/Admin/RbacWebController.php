<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RbacWebController extends Controller
{
    public function index(): Response
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);
            return $parts[0] ?? 'other';
        });
        $users = User::with('roles')->select('id', 'name', 'email')->get();

        return Inertia::render('Admin/Rbac', [
            'roles' => $roles,
            'permissions' => $permissions,
            'users' => $users,
            'permissionModules' => $permissions->keys(),
            'canModifyAdmin' => false,
        ]);
    }

    public function updateRolePermissions(Request $request, Role $role): Response
    {
        if ($role->name === 'admin') {
            return back()->with('error', 'The Admin role cannot be modified.');
        }

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($validated['permissions']);

        $this->logAudit('role_permissions_updated', [
            'role' => $role->name,
            'permissions' => $validated['permissions'],
        ]);

        app(PermissionRegistrar::class)->forgetCache();

        return back()->with('success', "Permissions updated for {$role->name} role.");
    }

    public function updateUserRoles(Request $request, User $user): Response
    {
        $validated = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->syncRoles($validated['roles']);

        $this->logAudit('user_roles_updated', [
            'user' => $user->email,
            'roles' => $validated['roles'],
        ]);

        app(PermissionRegistrar::class)->forgetCache();

        return back()->with('success', "Roles updated for {$user->name}.");
    }

    public function storeRole(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        $this->logAudit('role_created', [
            'role' => $role->name,
            'permissions' => $validated['permissions'] ?? [],
        ]);

        return redirect()->route('admin.rbac.index')->with('success', "Role {$role->name} created.");
    }

    public function deleteRole(Role $role): Response
    {
        if ($role->name === 'admin') {
            return back()->with('error', 'The Admin role cannot be deleted.');
        }

        $role->delete();

        $this->logAudit('role_deleted', [
            'role' => $role->name,
        ]);

        return redirect()->route('admin.rbac.index')->with('success', 'Role deleted.');
    }

    private function logAudit(string $action, array $data): void
    {
        // Audit logging placeholder - could integrate with Spatie Activitylog
    }
}