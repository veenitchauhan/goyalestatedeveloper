@extends('layouts.admin')
@section('title','Roles and permissions')
@section('content')<p class="eyebrow">ACCESS CONTROL</p><h1>Roles & permissions.</h1><p>Choose which actions each role can perform. Changes apply immediately to users assigned to that role. Permissions for future modules become usable when those modules are built.</p>
@can('roles.manage')<p><a class="button" href="{{ route('admin.roles.create') }}">Create a role</a></p>@endcan
<div class="cards">@foreach($roles as $role)<section><h2>{{ $role->label }}</h2><p>{{ $role->users_count }} assigned users</p>@if($role->name==='super-admin')<p>Protected role: complete access and user/role administration cannot be removed.</p>@else @can('roles.manage')<p><a href="{{ route('admin.roles.edit',$role) }}">Edit permissions →</a></p>@endcan @endif
<details><summary>View allowed actions</summary><ul>@foreach($role->permissions as $permission)<li>{{ str($permission->name)->replace('.',' · ')->replace('-',' ')->headline() }}</li>@endforeach</ul></details></section>@endforeach</div>@endsection
