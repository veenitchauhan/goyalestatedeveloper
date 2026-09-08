<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (config('permissions') as $name => $definition) {
                $role = Role::firstOrCreate(['name' => $name], ['label' => $definition['label']]);
                $ids = collect($definition['permissions'])->map(fn ($permission) => Permission::firstOrCreate(['name' => $permission])->id);
                if ($role->wasRecentlyCreated || $role->name === 'super-admin') {
                    $role->permissions()->sync($ids);
                }
            }
        });
    }
}
