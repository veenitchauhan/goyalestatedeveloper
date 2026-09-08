@extends('layouts.admin')
@section('title','Overview')
@section('content')<p class="eyebrow">ADMINISTRATION</p><h1>Welcome, {{ auth()->user()->name }}.</h1><p>Choose a workspace below. Only tools available to your role are shown.</p><div class="cards">
@can('pages.edit')<section><h2>Homepage</h2><p>Edit the homepage copy, section order and contact details. Preview changes before publishing.</p><a href="{{ route('admin.homepage.edit') }}">Edit homepage →</a></section>@endcan
@can('pages.view')<section><h2>Pages & reusable content</h2><p>Manage pages, shared text, statistics and navigation links.</p><a href="{{ route('admin.content.index') }}">Open content →</a></section>@endcan
@can('media.manage')<section><h2>Media library</h2><p>Manage images and documents, visibility and branded image versions.</p><a href="{{ route('admin.media.index') }}">Open media →</a></section>@endcan
@can('leads.view')<section><h2>Website enquiries</h2><p>Read enquiries submitted through the website.</p><a href="{{ route('admin.enquiries') }}">View enquiries →</a></section>@endcan
@can('users.manage')<section><h2>Users</h2><p>Create users, assign roles and manage account access.</p><a href="{{ route('admin.users.index') }}">Manage users →</a></section>@endcan
@can('roles.view')<section><h2>Roles & permissions</h2><p>Review access boundaries and manage permitted actions.</p><a href="{{ route('admin.roles') }}">View roles →</a></section>@endcan
@can('audit.view')<section><h2>Activity history</h2><p>Review who changed access, content and publication settings.</p><a href="{{ route('admin.audit') }}">View activity →</a></section>@endcan
<section><h2>Account security</h2><p>{{ auth()->user()->two_factor_confirmed_at ? 'Your authenticator is connected.' : 'An authenticator has not been connected.' }}</p><a href="{{ route('admin.security') }}">Manage security →</a></section></div>
<p>Dedicated projects, recruitment, Knowledge Bank, CRM and analytics workspaces are still under development. The current tools above are ready to test.</p>@endsection
