<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles', ['roles' => Role::with('permissions')->withCount('users')->orderBy('label')->get()]);
    }

    public function create(): View
    {
        return $this->editor(new Role);
    }

    public function edit(Role $role): View
    {
        abort_if($role->name === 'super-admin', 403);

        return $this->editor($role);
    }

    private function editor(Role $role): View
    {
        return view('admin.role-edit', ['role' => $role, 'permissions' => Permission::whereNotIn('name', ['users.manage', 'roles.manage'])->orderBy('name')->get()->groupBy(fn ($permission) => explode('.', $permission->name)[0])]);
    }

    public function store(SaveRoleRequest $request, AuditRecorder $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $audit) {
            $role = Role::create($request->safe()->only(['name', 'label']));
            $role->permissions()->sync(Permission::whereIn('name', $request->input('permissions'))->pluck('id'));
            $audit->record('role.created', $role, ['after_label' => $role->label, 'after_permissions' => $role->permissions()->orderBy('name')->pluck('name')->all()]);
        });

        return redirect()->route('admin.roles')->with('status', 'Role created. Assign it to a user from Users.');
    }

    public function update(SaveRoleRequest $request, Role $role, AuditRecorder $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $role, $audit) {
            $role = Role::lockForUpdate()->findOrFail($role->id);
            $this->guardRole($role, $request->integer('revision'));
            $before = $role->permissions()->orderBy('name')->pluck('name')->all();
            $label = $role->label;
            $role->label = $request->string('label')->toString();
            $role->revision++;
            $role->save();
            $role->permissions()->sync(Permission::whereIn('name', $request->input('permissions'))->pluck('id'));
            $audit->record('role.updated', $role, ['before_label' => $label, 'after_label' => $role->label, 'before_permissions' => $before, 'after_permissions' => $role->permissions()->orderBy('name')->pluck('name')->all()]);
        });

        return back()->with('status', 'Role updated. Permissions apply immediately to assigned users.');
    }

    public function destroy(Request $request, Role $role, AuditRecorder $audit): RedirectResponse
    {
        abort_unless($request->user()->hasRole('super-admin'), 403);
        $request->validate(['revision' => 'required|integer']);
        DB::transaction(function () use ($request, $role, $audit) {
            $role = Role::lockForUpdate()->findOrFail($role->id);
            $this->guardRole($role, $request->integer('revision'));
            if (array_key_exists($role->name, config('permissions')) || $role->users()->exists()) {
                throw ValidationException::withMessages(['role' => 'Only unassigned custom roles can be deleted.']);
            }
            $audit->record('role.deleted', $role, ['before_label' => $role->label, 'before_permissions' => $role->permissions()->pluck('name')->all()]);
            $role->delete();
        });

        return redirect()->route('admin.roles')->with('status', 'Unused custom role deleted.');
    }

    private function guardRole(Role $role, int $revision): void
    {
        abort_if($role->name === 'super-admin', 403);
        if ($role->revision !== $revision) {
            throw ValidationException::withMessages(['revision' => 'This role was changed by another administrator. Reload before saving.']);
        }
    }
}
