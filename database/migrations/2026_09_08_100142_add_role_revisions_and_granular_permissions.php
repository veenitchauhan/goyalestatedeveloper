<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedInteger('revision')->default(1);
        });
        $mapping = ['pages.edit' => ['pages.create'], 'pages.publish' => ['pages.approve', 'pages.unpublish', 'pages.archive'], 'media.manage' => ['media.upload', 'media.edit', 'media.publish']];
        foreach ($mapping as $old => $newNames) {
            $roles = DB::table('permission_role')->where('permission_id', DB::table('permissions')->where('name', $old)->value('id'))->pluck('role_id');
            foreach ($newNames as $name) {
                DB::table('permissions')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
                foreach ($roles as $roleId) {
                    DB::table('permission_role')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => DB::table('permissions')->where('name', $name)->value('id')]);
                }
            }
        }
        DB::table('permissions')->insertOrIgnore(['name' => 'roles.manage', 'created_at' => now(), 'updated_at' => now()]);
        $super = DB::table('roles')->where('name', 'super-admin')->value('id');
        if ($super) {
            DB::table('permission_role')->insertOrIgnore(['role_id' => $super, 'permission_id' => DB::table('permissions')->where('name', 'roles.manage')->value('id')]);
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('revision');
        });
        DB::table('permissions')->whereIn('name', ['pages.create', 'pages.approve', 'pages.unpublish', 'pages.archive', 'media.upload', 'media.edit', 'media.publish', 'roles.manage'])->delete();
    }
};
