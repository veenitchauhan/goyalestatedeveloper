<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserAccessRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users', ['users' => User::with('roles')->orderBy('name')->paginate(20), 'roles' => Role::orderBy('label')->get()]);
    }

    public function store(StoreUserRequest $request, AuditRecorder $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $audit) {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));
            $user->roles()->sync([$request->integer('role_id')]);
            $audit->record('user.created', $user, ['after_roles' => $user->roles()->pluck('name')->all()]);
        });

        return back()->with('status', 'User created. Share their credentials through your approved private channel.');
    }

    public function update(UpdateUserAccessRequest $request, User $user, AuditRecorder $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $user, $audit) {
            Role::where('name', 'super-admin')->lockForUpdate()->firstOrFail();
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $role = Role::findOrFail($request->integer('role_id'));
            if ($user->is($request->user()) && (! $request->boolean('is_active') || $role->name !== 'super-admin')) {
                throw ValidationException::withMessages(['role_id' => 'You cannot remove your own administrator access.']);
            }
            if ($user->is_active && $user->hasRole('super-admin') && (! $request->boolean('is_active') || $role->name !== 'super-admin') && User::where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->count() <= 1) {
                throw ValidationException::withMessages(['role_id' => 'Keep at least one active Super Admin.']);
            }
            $changes = ['before_roles' => $user->roles()->pluck('name')->all(), 'after_roles' => [$role->name], 'before_active' => $user->is_active, 'after_active' => $request->boolean('is_active')];
            $user->is_active = $request->boolean('is_active');
            $user->save();
            $user->roles()->sync([$role->id]);
            $audit->record('user.access_updated', $user, $changes);
        });

        return back()->with('status', 'User access updated.');
    }
}
