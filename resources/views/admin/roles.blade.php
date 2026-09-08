@extends('layouts.admin')
@section('title','Roles and permissions')
@section('content')<p class="eyebrow">PERMISSION CATALOGUE</p><h1>Clear boundaries.</h1><p>These predefined roles are enforced on the server. Permissions for later modules become usable as those modules are implemented.</p><div class="cards">@foreach($roles as $role)<section><h2>{{ $role->label }}</h2><ul>@foreach($role->permissions as $permission)<li>{{ $permission->name }}</li>@endforeach</ul></section>@endforeach</div>@endsection
